<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkAssignmentResource;
use App\Models\JobPart;
use App\Models\Part;
use App\Models\WorkAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = WorkAssignment::with(['serviceRequest.customer', 'serviceRequest.machine'])
            ->where('technician_id', $request->user()->id)
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_date', $request->input('date')))
            ->when($request->filled('from') && $request->filled('to'), fn ($q) => $q->whereBetween('scheduled_date', [$request->input('from'), $request->input('to')]))
            ->orderBy('scheduled_date')->orderBy('start_time')
            ->paginate(20);

        return response()->json(['data' => WorkAssignmentResource::collection($jobs)]);
    }

    public function show(Request $request, WorkAssignment $assignment): JsonResponse
    {
        $this->authorizeOwn($request, $assignment);
        $assignment->load(['serviceRequest.customer', 'serviceRequest.machine']);

        return response()->json(['data' => new WorkAssignmentResource($assignment)]);
    }

    public function updateStatus(Request $request, WorkAssignment $assignment): JsonResponse
    {
        $this->authorizeOwn($request, $assignment);
        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'remarks' => ['nullable', 'max:1000'],
        ]);

        $oldStatus = $assignment->status;
        $assignment->update(['status' => $data['status']]);
        $assignment->serviceRequest->update(['status' => $assignment->status]);
        if ($oldStatus !== $assignment->status) {
            $assignment->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $assignment->status,
                'remarks' => $data['remarks'] ?? 'Status updated from the technician app.',
                'changed_by_technician_id' => $request->user()->id,
            ]);
        }

        return response()->json(['message' => 'Status updated.', 'data' => new WorkAssignmentResource($assignment->fresh(['serviceRequest.customer', 'serviceRequest.machine']))]);
    }

    public function storeParts(Request $request, WorkAssignment $assignment): JsonResponse
    {
        $this->authorizeOwn($request, $assignment);
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'rate' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'serial_number' => ['nullable', 'max:100'],
            'under_warranty' => ['nullable', 'boolean'],
        ]);

        $jobPart = DB::transaction(function () use ($data, $assignment) {
            $part = Part::lockForUpdate()->findOrFail($data['part_id']);
            abort_if($part->current_stock < $data['quantity'], 422, 'Insufficient stock.');
            $part->decrement('current_stock', $data['quantity']);

            return JobPart::create($data + ['work_assignment_id' => $assignment->id]);
        });

        return response()->json(['message' => 'Part usage recorded.', 'data' => $jobPart->load('part')], 201);
    }

    public function jobCard(Request $request, WorkAssignment $assignment): Response
    {
        $this->authorizeOwn($request, $assignment);
        $assignment->load(['serviceRequest.customer', 'serviceRequest.machine', 'serviceRequest.amcPlans', 'technician', 'statusHistories.changedBy', 'statusHistories.changedByTechnician']);
        $jobParts = JobPart::where('work_assignment_id', $assignment->id)->with('part')->get();
        $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/fieldservice-logo.png')));

        $pdf = Pdf::loadView('work-assignments.job-card', compact('assignment', 'jobParts', 'logo'))->setPaper('a4', 'portrait');

        return $pdf->stream($assignment->assignment_code.'-job-card.pdf');
    }

    private function authorizeOwn(Request $request, WorkAssignment $assignment): void
    {
        abort_if($assignment->technician_id !== $request->user()->id, 404);
    }
}
