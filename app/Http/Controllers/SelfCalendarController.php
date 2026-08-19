<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\TechnicianLeave;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SelfCalendarController extends Controller
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
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01')->startOfMonth();
        $userId = $request->user()->id;

        $holidays = Holiday::whereBetween('holiday_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()->keyBy(fn ($h) => $h->holiday_date->format('Y-m-d'));
        $attendance = Attendance::where('user_id', $userId)
            ->whereBetween('attendance_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()->keyBy(fn ($a) => $a->attendance_date->format('Y-m-d'));
        $today = Attendance::where('user_id', $userId)->whereDate('attendance_date', today())->first();
        $leaves = TechnicianLeave::where('user_id', $userId)->latest()->paginate(10);

        return view('workforce.my-calendar', [
            'month' => $month,
            'holidays' => $holidays,
            'attendance' => $attendance,
            'today' => $today,
            'leaves' => $leaves,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'checkInDeadline' => $this->checkInDeadline($request->user()),
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $existing = Attendance::where('user_id', $request->user()->id)->whereDate('attendance_date', today())->first();
        abort_if($existing?->check_in, 422, 'You have already checked in today.');

        $deadline = $this->checkInDeadline($request->user());
        abort_if($deadline && now()->greaterThan($deadline), 422, 'Check-in window has passed for today. Contact your admin.');

        Attendance::updateOrCreate(
            ['user_id' => $request->user()->id, 'technician_id' => null, 'attendance_date' => today()],
            ['attendance_status' => 'present', 'check_in' => now()->format('H:i')]
        );

        return back()->with('success', 'Checked in at '.now()->format('h:i A').'.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $existing = Attendance::where('user_id', $request->user()->id)->whereDate('attendance_date', today())->first();
        abort_if(! $existing?->check_in, 422, 'Check in before checking out.');
        abort_if($existing->check_out, 422, 'You have already checked out today.');

        $existing->update(['check_out' => now()->format('H:i')]);

        return back()->with('success', 'Checked out at '.now()->format('h:i A').'.');
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])],
            'from_date' => 'required|date|after:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|max:1000',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $data['leave_code'] = 'LV-'.now()->format('ymd').'-'.random_int(1000, 9999);
        TechnicianLeave::create($data);

        return back()->with('success', 'Leave request submitted.');
    }

    public function editLeave(Request $request, TechnicianLeave $leave): View
    {
        $this->authorizeOwnPending($request, $leave);

        return view('workforce.my-calendar-leave-edit', ['leave' => $leave]);
    }

    public function updateLeave(Request $request, TechnicianLeave $leave): RedirectResponse
    {
        $this->authorizeOwnPending($request, $leave);
        $data = $request->validate([
            'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])],
            'from_date' => 'required|date|after:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|max:1000',
        ]);
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $leave->update($data);

        return to_route('my-calendar.index')->with('success', 'Leave request updated.');
    }

    public function destroyLeave(Request $request, TechnicianLeave $leave): RedirectResponse
    {
        $this->authorizeOwnPending($request, $leave);
        $leave->delete();

        return to_route('my-calendar.index')->with('success', 'Leave request withdrawn.');
    }

    private function checkInDeadline($user): ?Carbon
    {
        if (! $user->expected_check_in_time) {
            return null;
        }

        return Carbon::parse(today()->format('Y-m-d').' '.$user->expected_check_in_time)->addMinutes(30);
    }

    private function authorizeOwnPending(Request $request, TechnicianLeave $leave): void
    {
        abort_if($leave->user_id !== $request->user()->id, 404);
        abort_if($leave->status !== 'pending', 422, 'This leave request has already been actioned and can no longer be changed.');
    }
}
