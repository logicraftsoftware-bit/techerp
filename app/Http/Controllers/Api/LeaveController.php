<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianLeaveResource;
use App\Models\TechnicianLeave;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $leaves = TechnicianLeave::where('technician_id', $request->user()->id)->latest()->paginate(15);

        return response()->json(['data' => TechnicianLeaveResource::collection($leaves), 'meta' => ['current_page' => $leaves->currentPage(), 'last_page' => $leaves->lastPage(), 'total' => $leaves->total()]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['technician_id'] = $request->user()->id;
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $data['leave_code'] = 'LV-'.now()->format('ymd').'-'.random_int(1000, 9999);
        $data['status'] = 'pending';
        $leave = TechnicianLeave::create($data);

        return response()->json(['message' => 'Leave request submitted.', 'data' => new TechnicianLeaveResource($leave)], 201);
    }

    public function update(Request $request, TechnicianLeave $leave): JsonResponse
    {
        $this->authorizeOwnPending($request, $leave);
        $data = $this->validated($request);
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $leave->update($data);

        return response()->json(['message' => 'Leave request updated.', 'data' => new TechnicianLeaveResource($leave)]);
    }

    public function destroy(Request $request, TechnicianLeave $leave): JsonResponse
    {
        $this->authorizeOwnPending($request, $leave);
        $leave->delete();

        return response()->json(['message' => 'Leave request withdrawn.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])],
            'from_date' => ['required', 'date', 'after:today'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'max:1000'],
        ]);
    }

    private function authorizeOwnPending(Request $request, TechnicianLeave $leave): void
    {
        abort_if($leave->technician_id !== $request->user()->id, 404);
        abort_if($leave->status !== 'pending', 422, 'This leave request has already been actioned and can no longer be changed.');
    }
}
