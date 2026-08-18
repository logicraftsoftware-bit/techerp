<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfHolidayController extends Controller
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
        $yearHolidays = Holiday::whereYear('holiday_date', $month->year)->orderBy('holiday_date')->get();
        $holidays = $yearHolidays->filter(fn ($h) => $h->holiday_date->between($month->copy()->startOfMonth(), $month->copy()->endOfMonth()))
            ->keyBy(fn ($h) => $h->holiday_date->format('Y-m-d'));

        return view('workforce.holidays', [
            'month' => $month,
            'holidays' => $holidays,
            'yearHolidays' => $yearHolidays,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'indexRoute' => 'my-holidays.index',
            'canCreate' => false,
            'canDelete' => false,
        ]);
    }
}
