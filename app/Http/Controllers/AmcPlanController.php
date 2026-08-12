<?php

namespace App\Http\Controllers;

use App\Http\Requests\AmcPlanRequest;
use App\Models\AmcPlan;
use App\Models\Brand;
use App\Models\MachineCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmcPlanController extends Controller
{
    public function index(Request $request): View
    {
        $records = AmcPlan::with(['machineCategory', 'brandMaster'])
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q->where('plan_name', 'like', "%{$search}%")->orWhere('plan_code', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.amc-plans.index', compact('records'));
    }

    public function create(): View
    {
        return $this->form(new AmcPlan);
    }

    public function store(AmcPlanRequest $request): RedirectResponse
    {
        AmcPlan::create($request->validated());

        return to_route('amc-plans.index')->with('success', 'AMC plan created.');
    }

    public function edit(AmcPlan $amcPlan): View
    {
        return $this->form($amcPlan);
    }

    public function update(AmcPlanRequest $request, AmcPlan $amcPlan): RedirectResponse
    {
        $amcPlan->update($request->validated());

        return to_route('amc-plans.index')->with('success', 'AMC plan updated.');
    }

    public function destroy(AmcPlan $amcPlan): RedirectResponse
    {
        $amcPlan->delete();

        return back()->with('success', 'AMC plan deleted.');
    }

    private function form(AmcPlan $amcPlan): View
    {
        return view('master.amc-plans.form', [
            'amcPlan' => $amcPlan,
            'categories' => MachineCategory::orderBy('category_name')->get(),
            'brands' => Brand::orderBy('brand_name')->get(),
        ]);
    }
}
