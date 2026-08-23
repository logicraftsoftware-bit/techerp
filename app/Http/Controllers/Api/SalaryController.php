<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalaryRecordResource;
use App\Models\SalaryRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = SalaryRecord::where('technician_id', $request->user()->id)
            ->when($request->filled('month'), fn ($q) => $q->whereDate('salary_month', Carbon::parse($request->input('month').'-01')))
            ->orderByDesc('salary_month')
            ->paginate(15);

        return response()->json(['data' => SalaryRecordResource::collection($records)]);
    }

    public function show(Request $request, SalaryRecord $salary): JsonResponse
    {
        abort_if($salary->technician_id !== $request->user()->id, 404);

        return response()->json(['data' => new SalaryRecordResource($salary)]);
    }
}
