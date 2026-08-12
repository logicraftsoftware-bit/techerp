<?php

namespace App\Http\Controllers;

use App\Models\WorkAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobCardController extends Controller
{
    public function index(Request $request): View
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->month) ? $request->month : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $assignments = WorkAssignment::with(['serviceRequest.customer', 'serviceRequest.machine', 'technician'])->whereBetween('scheduled_date', [$start, $start->copy()->endOfMonth()])->orderBy('start_time')->get();

        return view('job-cards.index', ['month' => $month, 'start' => $start, 'assignments' => $assignments, 'byDate' => $assignments->groupBy(fn ($item) => $item->scheduled_date->format('Y-m-d'))]);
    }
}
