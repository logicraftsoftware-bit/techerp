<?php

namespace App\Http\Controllers;

use App\Http\Requests\MachineCategoryRequest;
use App\Models\MachineCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:machine-categories,view')->only(['index']);
        $this->middleware('permission:machine-categories,create')->only(['create', 'store']);
        $this->middleware('permission:machine-categories,update')->only(['edit', 'update']);
        $this->middleware('permission:machine-categories,delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $records = MachineCategory::query()
            ->when($request->search, fn ($query, $search) => $query->where('category_name', 'like', "%{$search}%"))
            ->orderBy('category_name')
            ->paginate(15)
            ->withQueryString();

        return view('master.machine-categories.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.machine-categories.form', ['machineCategory' => new MachineCategory]);
    }

    public function store(MachineCategoryRequest $request): RedirectResponse
    {
        MachineCategory::create($request->validated());

        return to_route('machine-categories.index')->with('success', 'Machine category created.');
    }

    public function edit(MachineCategory $machineCategory): View
    {
        return view('master.machine-categories.form', compact('machineCategory'));
    }

    public function update(MachineCategoryRequest $request, MachineCategory $machineCategory): RedirectResponse
    {
        $machineCategory->update($request->validated());

        return to_route('machine-categories.index')->with('success', 'Machine category updated.');
    }

    public function destroy(MachineCategory $machineCategory): RedirectResponse
    {
        $machineCategory->delete();

        return back()->with('success', 'Machine category deleted.');
    }
}
