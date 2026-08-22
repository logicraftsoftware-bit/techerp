<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerAmcTaggingRequest;
use App\Models\AmcPlan;
use App\Models\Customer;
use App\Models\CustomerAmcTagging;
use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerAmcTaggingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('super-admin', 'admin') || $request->user()->hasPermission('customer-amc-taggings.view'), 403);

        $records = CustomerAmcTagging::with(['customer', 'machine', 'amcPlan'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('customer', fn ($q) => $q->where('customer_name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"))
                        ->orWhereHas('machine', fn ($q) => $q->where('machine_name', 'like', "%{$search}%")->orWhere('machine_code', 'like', "%{$search}%"))
                        ->orWhereHas('amcPlan', fn ($q) => $q->where('plan_name', 'like', "%{$search}%")->orWhere('plan_code', 'like', "%{$search}%"));
                });
            })
            ->orderBy('end_date')
            ->paginate(15)
            ->withQueryString();

        return view('customer-amc-taggings.index', compact('records'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return $this->form(new CustomerAmcTagging);
    }

    public function store(CustomerAmcTaggingRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['created_by'] = $request->user()->id;
        CustomerAmcTagging::create($data);

        return to_route('customer-amc-taggings.index')->with('success', 'Customer AMC tagged successfully.');
    }

    public function edit(CustomerAmcTagging $customerAmcTagging): View
    {
        $this->authorizeAdmin();

        return $this->form($customerAmcTagging);
    }

    public function update(CustomerAmcTaggingRequest $request, CustomerAmcTagging $customerAmcTagging): RedirectResponse
    {
        $customerAmcTagging->update($this->payload($request));

        return to_route('customer-amc-taggings.index')->with('success', 'Customer AMC tagging updated.');
    }

    public function destroy(CustomerAmcTagging $customerAmcTagging): RedirectResponse
    {
        $this->authorizeAdmin();
        $customerAmcTagging->delete();

        return back()->with('success', 'Customer AMC tagging deleted.');
    }

    private function form(CustomerAmcTagging $customerAmcTagging): View
    {
        return view('customer-amc-taggings.form', [
            'tagging' => $customerAmcTagging,
            'customers' => Customer::orderBy('customer_name')->get(['id', 'customer_code', 'customer_name']),
            'machines' => Machine::orderBy('machine_name')->get(['id', 'customer_id', 'machine_code', 'machine_name', 'model']),
            'plans' => AmcPlan::where('status', 'active')->orderBy('plan_name')->get(['id', 'plan_code', 'plan_name', 'duration']),
        ]);
    }

    private function payload(CustomerAmcTaggingRequest $request): array
    {
        $data = $request->validated();
        $plan = AmcPlan::findOrFail($data['amc_plan_id']);
        $data['end_date'] = CustomerAmcTagging::calculateEndDate(Carbon::parse($data['start_date']), $plan->duration)->toDateString();

        return $data;
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('super-admin', 'admin'), 403);
    }
}
