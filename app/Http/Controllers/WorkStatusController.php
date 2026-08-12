<?php

namespace App\Http\Controllers;

use App\Models\WorkAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkStatusController extends Controller
{
    public function index(Request $request): View
    {
        $records = WorkAssignment::with(['serviceRequest.customer', 'technician'])->when($request->status, fn ($q, $status) => $q->where('status', $status))->latest('scheduled_date')->paginate(15)->withQueryString();

        return view('work-status.index', compact('records'));
    }

    public function show(WorkAssignment $assignment): View
    {
        return view('work-status.show', ['assignment' => $assignment->load(['serviceRequest.customer', 'serviceRequest.machine', 'technician', 'statusHistories.changedBy'])]);
    }

    public function update(Request $request, WorkAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['assigned', 'accepted', 'en_route', 'in_progress', 'completed', 'cancelled'])], 'remarks' => ['nullable', 'string', 'max:1000']]);
        $old = $assignment->status;
        $assignment->update(['status' => $data['status']]);
        if ($old !== $data['status'] || filled($data['remarks'] ?? null)) {
            $assignment->statusHistories()->create(['from_status' => $old, 'to_status' => $data['status'], 'remarks' => $data['remarks'] ?? null, 'changed_by' => $request->user()->id]);
        }
        if ($data['status'] === 'completed' && ! $assignment->serviceRequest->workAssignments()->where('status', '!=', 'completed')->exists()) {
            $assignment->serviceRequest->update(['status' => 'completed']);
        } elseif (in_array($data['status'], ['en_route', 'in_progress'], true)) {
            $assignment->serviceRequest->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Work status updated.');
    }
}
