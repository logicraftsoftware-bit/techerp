<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_if($request->user()->hasRole('super-admin'), 404);

            return $next($request);
        });
    }

    public function show(Request $request): View
    {
        $today = Attendance::where('user_id', $request->user()->id)->whereDate('attendance_date', today())->first();

        return view('workforce.self-attendance', ['today' => $today]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $existing = Attendance::where('user_id', $request->user()->id)->whereDate('attendance_date', today())->first();
        abort_if($existing?->check_in, 422, 'You have already checked in today.');

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
}
