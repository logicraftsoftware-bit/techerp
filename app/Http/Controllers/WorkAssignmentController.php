<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkAssignmentRequest;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\WorkAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $records = WorkAssignment::with(['serviceRequest.customer', 'serviceRequest.machine', 'technician', 'skill'])
            ->when($request->search, fn ($query, $search) => $query->where(fn ($query) => $query->where('assignment_code', 'like', "%{$search}%")->orWhereHas('technician', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orWhereHas('serviceRequest', fn ($q) => $q->where('request_code', 'like', "%{$search}%"))))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('scheduled_date')->orderByDesc('start_time')->paginate(15)->withQueryString();

        return view('work-assignments.index', compact('records'));
    }

    public function create(): View
    {
        return $this->form(new WorkAssignment);
    }

    public function store(WorkAssignmentRequest $request): RedirectResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($request->integer('service_request_id'));
        $assignment = WorkAssignment::create($request->validated() + ['priority' => $serviceRequest->priority, 'assigned_by' => $request->user()->id]);
        $serviceRequest->update(['status' => $assignment->status]);
        $assignment->statusHistories()->create(['to_status' => $assignment->status, 'remarks' => 'Work assignment created.', 'changed_by' => $request->user()->id]);

        return to_route('assignments.index')->with('success', 'Work assigned successfully.');
    }

    public function show(WorkAssignment $assignment): View
    {
        return view('work-assignments.show', ['assignment' => $assignment->load(['serviceRequest.customer', 'serviceRequest.machine', 'technician.skills', 'skill', 'assigner'])]);
    }

    public function edit(WorkAssignment $assignment): View
    {
        return $this->form($assignment);
    }

    public function update(WorkAssignmentRequest $request, WorkAssignment $assignment): RedirectResponse
    {
        $oldStatus = $assignment->status;
        $serviceRequest = ServiceRequest::findOrFail($request->integer('service_request_id'));
        $assignment->update($request->validated() + ['priority' => $serviceRequest->priority]);
        $serviceRequest->update(['status' => $assignment->status]);
        if ($oldStatus !== $assignment->status) {
            $assignment->statusHistories()->create(['from_status' => $oldStatus, 'to_status' => $assignment->status, 'remarks' => 'Status changed while editing assignment.', 'changed_by' => $request->user()->id]);
        }

        return to_route('assignments.index')->with('success', 'Work assignment updated.');
    }

    public function destroy(WorkAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('success', 'Work assignment deleted.');
    }

    private function form(WorkAssignment $assignment): View
    {
        return view('work-assignments.form', [
            'assignment' => $assignment,
            'requests' => ServiceRequest::with(['customer', 'machine'])
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where(function ($query) use ($assignment): void {
                    $query->whereDoesntHave('workAssignments', fn ($q) => $q->where('status', '!=', 'cancelled'));
                    if ($assignment->exists) {
                        $query->orWhere('id', $assignment->service_request_id);
                    }
                })
                ->latest()->get(),
            'technicians' => Technician::with('skills')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }
}
