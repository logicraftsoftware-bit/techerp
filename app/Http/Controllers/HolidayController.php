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
        $yearHolidays = Holiday::whereYear('holiday_date', $month->year)->orderBy('holiday_date')->get();
        $holidays = $yearHolidays->filter(fn ($h) => $h->holiday_date->between($month->copy()->startOfMonth(), $month->copy()->endOfMonth()))
            ->keyBy(fn ($h) => $h->holiday_date->format('Y-m-d'));

        $isAdmin = $request->user()->hasRole('super-admin');

        return view('workforce.holidays', [
            'month' => $month,
            'holidays' => $holidays,
            'yearHolidays' => $yearHolidays,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'indexRoute' => 'holidays.index',
            'canCreate' => $isAdmin || $request->user()->hasPermission('holidays.create'),
            'canDelete' => $isAdmin || $request->user()->hasPermission('holidays.delete'),
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
