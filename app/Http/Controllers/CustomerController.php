<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Technician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:customers,view')->only(['index', 'show']);
        $this->middleware('permission:customers,create')->only(['create', 'store']);
        $this->middleware('permission:customers,update')->only(['edit', 'update']);
        $this->middleware('permission:customers,delete')->only(['destroy']);
    }

    public function index(Request $r): View
    {
        $records = Customer::with('referredBy')->withCount(['contacts', 'machines'])->when($r->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('customer_name', 'like', "%$s%")->orWhere('customer_code', 'like', "%$s%")->orWhere('mobile', 'like', "%$s%")))->latest()->paginate(15)->withQueryString();

        return view('master.customers.index', compact('records'));
    }

    public function create(): View
    {
        return $this->form(new Customer);
    }

    public function store(CustomerRequest $r): RedirectResponse
    {
        DB::transaction(function () use ($r) {
            $data = $this->customerData($r);
            $c = Customer::create($data);
            $c->contacts()->createMany(collect($r->validated('contacts', []))->filter(fn ($x) => ! empty($x['name']))->all());
        });

        return to_route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        return view('master.customers.show', ['customer' => $customer->load(['contacts', 'machines', 'referredBy'])]);
    }

    public function edit(Customer $customer): View
    {
        return $this->form($customer);
    }

    public function update(CustomerRequest $r, Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($r, $customer) {
            $customer->update($this->customerData($r));
            $customer->contacts()->delete();
            $customer->contacts()->createMany(collect($r->validated('contacts', []))->filter(fn ($x) => ! empty($x['name']))->all());
        });

        return to_route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_if($customer->machines()->exists(), 422, 'Delete or reassign customer machines first.');
        $customer->delete();

        return back()->with('success', 'Customer deleted.');
    }

    private function form(Customer $customer): View
    {
        return view('master.customers.form', [
            'customer' => $customer,
            'staff' => Technician::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    private function customerData(CustomerRequest $request): array
    {
        $data = $request->safe()->except('contacts');
        if ($data['refer_type'] === 'self') {
            $data['referred_by_technician_id'] = null;
        }

        return $data;
    }
}
