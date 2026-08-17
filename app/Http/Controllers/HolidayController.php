<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:holidays,view')->only(['index']);
        $this->middleware('permission:holidays,create')->only(['store']);
        $this->middleware('permission:holidays,delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01')->startOfMonth();
        $holidays = Holiday::whereBetween('holiday_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()->keyBy(fn ($h) => $h->holiday_date->format('Y-m-d'));

        return view('workforce.holidays', [
            'month' => $month,
            'holidays' => $holidays,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['holiday_date' => 'required|date|unique:holidays,holiday_date', 'name' => 'nullable|max:150']);
        Holiday::create($data);

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()->route('holidays.index', ['month' => $request->input('month')])->with('success', 'Holiday removed.');
    }
}
