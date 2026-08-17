<?php

namespace App\Http\Controllers;

use App\Models\TechnicianLeave;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SelfLeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_if($request->user()->hasRole('super-admin'), 404);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $records = TechnicianLeave::where('user_id', $request->user()->id)->latest()->paginate(10);

        return view('workforce.self-leave', ['records' => $records]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])],
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|max:1000',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $data['leave_code'] = 'LV-'.now()->format('ymd').'-'.random_int(1000, 9999);
        TechnicianLeave::create($data);

        return back()->with('success', 'Leave request submitted.');
    }

    public function edit(Request $request, TechnicianLeave $leave): View
    {
        $this->authorizeOwnPending($request, $leave);

        return view('workforce.self-leave-edit', ['leave' => $leave]);
    }

    public function update(Request $request, TechnicianLeave $leave): RedirectResponse
    {
        $this->authorizeOwnPending($request, $leave);
        $data = $request->validate([
            'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])],
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|max:1000',
        ]);
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $leave->update($data);

        return to_route('my-leave.index')->with('success', 'Leave request updated.');
    }

    public function destroy(Request $request, TechnicianLeave $leave): RedirectResponse
    {
        $this->authorizeOwnPending($request, $leave);
        $leave->delete();

        return to_route('my-leave.index')->with('success', 'Leave request withdrawn.');
    }

    private function authorizeOwnPending(Request $request, TechnicianLeave $leave): void
    {
        abort_if($leave->user_id !== $request->user()->id, 404);
        abort_if($leave->status !== 'pending', 422, 'This leave request has already been actioned and can no longer be changed.');
    }
}
