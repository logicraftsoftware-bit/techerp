<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:units,view')->only(['index']);
        $this->middleware('permission:units,create')->only(['create', 'store']);
        $this->middleware('permission:units,update')->only(['edit', 'update']);
        $this->middleware('permission:units,delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $records = Unit::query()
            ->when($request->search, fn ($query, $search) => $query->where('unit_name', 'like', "%{$search}%"))
            ->orderBy('unit_name')
            ->paginate(15)
            ->withQueryString();

        return view('master.units.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.units.form', ['unit' => new Unit]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return to_route('units.index')->with('success', 'Unit created.');
    }

    public function edit(Unit $unit): View
    {
        return view('master.units.form', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return to_route('units.index')->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        abort_if($unit->parts()->exists(), 422, 'This unit is used by parts.');
        $unit->delete();

        return back()->with('success', 'Unit deleted.');
    }
}
