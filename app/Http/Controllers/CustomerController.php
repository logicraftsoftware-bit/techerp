<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $r): View
    {
        $records = Customer::withCount(['contacts', 'machines'])->when($r->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('customer_name', 'like', "%$s%")->orWhere('customer_code', 'like', "%$s%")->orWhere('mobile', 'like', "%$s%")))->latest()->paginate(15)->withQueryString();

        return view('master.customers.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.customers.form', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $r): RedirectResponse
    {
        DB::transaction(function () use ($r) {
            $data = $r->safe()->except('contacts');
            $c = Customer::create($data);
            $c->contacts()->createMany(collect($r->validated('contacts', []))->filter(fn ($x) => ! empty($x['name']))->all());
        });

        return to_route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        return view('master.customers.show', ['customer' => $customer->load(['contacts', 'machines'])]);
    }

    public function edit(Customer $customer): View
    {
        return view('master.customers.form', compact('customer'));
    }

    public function update(CustomerRequest $r, Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($r, $customer) {
            $customer->update($r->safe()->except('contacts'));
            $customer->contacts()->delete();
            $customer->contacts()->createMany(collect($r->validated('contacts', []))->filter(fn ($x) => ! empty($x['name']))->all());
        });

        return to_route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_if($customer->machines()->exists(), 422, 'Delete or reassign customer machines first.');
        $customer->delete();

        return back()->with('success','Customer deleted.');
    }
}
