<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommissionTypeRequest;
use App\Models\CommissionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:commission-types,view')->only(['index']);
        $this->middleware('permission:commission-types,create')->only(['create', 'store']);
        $this->middleware('permission:commission-types,update')->only(['edit', 'update']);
        $this->middleware('permission:commission-types,delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $records = CommissionType::query()
            ->when($request->search, fn ($query, $search) => $query->where('type_name', 'like', "%{$search}%"))
            ->orderBy('type_name')
            ->paginate(15)
            ->withQueryString();

        return view('master.commission-types.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.commission-types.form', ['commissionType' => new CommissionType]);
    }

    public function store(CommissionTypeRequest $request): RedirectResponse
    {
        CommissionType::create($request->validated());

        return to_route('commission-types.index')->with('success', 'Commission type created.');
    }

    public function edit(CommissionType $commissionType): View
    {
        return view('master.commission-types.form', compact('commissionType'));
    }

    public function update(CommissionTypeRequest $request, CommissionType $commissionType): RedirectResponse
    {
        $commissionType->update($request->validated());

        return to_route('commission-types.index')->with('success', 'Commission type updated.');
    }

    public function destroy(CommissionType $commissionType): RedirectResponse
    {
        abort_if($commissionType->technicians()->exists() || $commissionType->users()->exists(), 422, 'This commission type is assigned to staff.');
        $commissionType->delete();

        return back()->with('success', 'Commission type deleted.');
    }
}
