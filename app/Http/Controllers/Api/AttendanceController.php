<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $today = Attendance::where('technician_id', $request->user()->id)->whereDate('attendance_date', today())->first();

        return response()->json([
            'data' => $today ? new AttendanceResource($today) : null,
            'check_in_deadline' => $this->checkInDeadline($request->user())?->format('H:i'),
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $technician = $request->user();
        $existing = Attendance::where('technician_id', $technician->id)->whereDate('attendance_date', today())->first();
        abort_if($existing?->check_in, 422, 'You have already checked in today.');

        $deadline = $this->checkInDeadline($technician);
        abort_if($deadline && now()->greaterThan($deadline), 422, 'Check-in window has passed for today. Contact your admin.');

        $attendance = Attendance::updateOrCreate(
            ['technician_id' => $technician->id, 'user_id' => null, 'attendance_date' => today()],
            ['attendance_status' => 'present', 'check_in' => now()->format('H:i')]
        );

        return response()->json(['message' => 'Checked in at '.now()->format('h:i A').'.', 'data' => new AttendanceResource($attendance)]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $existing = Attendance::where('technician_id', $request->user()->id)->whereDate('attendance_date', today())->first();
        abort_if(! $existing?->check_in, 422, 'Check in before checking out.');
        abort_if($existing->check_out, 422, 'You have already checked out today.');

        $existing->update(['check_out' => now()->format('H:i')]);

        return response()->json(['message' => 'Checked out at '.now()->format('h:i A').'.', 'data' => new AttendanceResource($existing)]);
    }

    public function history(Request $request): JsonResponse
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        $records = Attendance::where('technician_id', $request->user()->id)
            ->whereBetween('attendance_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('attendance_date')
            ->get();

        return response()->json(['data' => AttendanceResource::collection($records)]);
    }

    private function checkInDeadline($technician): ?Carbon
    {
        if (! $technician->expected_check_in_time) {
            return null;
        }

        return Carbon::parse(today()->format('Y-m-d').' '.$technician->expected_check_in_time)->addMinutes(30);
    }
}
