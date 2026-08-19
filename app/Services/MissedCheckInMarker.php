<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;

class MissedCheckInMarker
{
    public function run(): void
    {
        $today = today();
        if (Holiday::whereDate('holiday_date', $today)->exists()) {
            return;
        }

        foreach (Technician::where('status', 'active')->whereNotNull('expected_check_in_time')->get() as $technician) {
            $this->markIfMissed('technician', $technician, $today);
        }

        foreach (User::where('is_active', true)->whereNotNull('expected_check_in_time')->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'super-admin'))->get() as $user) {
            $this->markIfMissed('user', $user, $today);
        }
    }

    private function markIfMissed(string $type, Technician|User $staff, Carbon $today): void
    {
        $deadline = Carbon::parse($today->format('Y-m-d').' '.$staff->expected_check_in_time)->addMinutes(30);
        if (now()->lessThanOrEqualTo($deadline)) {
            return;
        }

        $technicianId = $type === 'technician' ? $staff->id : null;
        $userId = $type === 'user' ? $staff->id : null;

        $exists = Attendance::where('technician_id', $technicianId)->where('user_id', $userId)->whereDate('attendance_date', $today)->exists();
        if ($exists) {
            return;
        }

        $leaveUsed = Attendance::where('technician_id', $technicianId)->where('user_id', $userId)
            ->whereYear('attendance_date', $today->year)
            ->whereMonth('attendance_date', $today->month)
            ->where('attendance_status', 'leave')
            ->count();

        Attendance::create([
            'technician_id' => $technicianId,
            'user_id' => $userId,
            'attendance_date' => $today,
            'attendance_status' => $leaveUsed < (int) $staff->monthly_paid_leave_days ? 'leave' : 'absent',
            'remarks' => 'Auto-marked: missed check-in',
        ]);
    }
}
