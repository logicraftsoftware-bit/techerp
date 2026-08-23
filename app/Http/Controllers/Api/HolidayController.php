<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        $holidays = Holiday::whereBetween('holiday_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('holiday_date')
            ->get(['id', 'holiday_date', 'name']);

        return response()->json(['data' => $holidays->map(fn ($h) => ['id' => $h->id, 'date' => $h->holiday_date->format('Y-m-d'), 'name' => $h->name])]);
    }
}
