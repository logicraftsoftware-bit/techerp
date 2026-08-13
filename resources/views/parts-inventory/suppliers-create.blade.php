@extends('layouts.app', ['title' => 'Add Supplier'])
@section('content')
<form method="POST" action="{{ route('suppliers.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Add Supplier</h2><p class="mt-1 text-sm text-slate-500">Supplier contacts, tax and payment details.</p></div>
        <button class="btn-primary">Save Supplier</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Company Name *</label><input name="company_name" value="{{ old('company_name') }}" class="form-input" required></div>
            <div><label class="form-label">Contact Person</label><input name="contact_person" value="{{ old('contact_person') }}" class="form-input"></div>
            <div><label class="form-label">Mobile *</label><input name="mobile" value="{{ old('mobile') }}" class="form-input" required></div>
            <div><label class="form-label">Email</label><input name="email" value="{{ old('email') }}" class="form-input"></div>
            <div><label class="form-label">GST Number</label><input name="gst_number" value="{{ old('gst_number') }}" class="form-input"></div>
            <div><label class="form-label">PAN Number</label><input name="pan_number" value="{{ old('pan_number') }}" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Address</label><input name="address" value="{{ old('address') }}" class="form-input"></div>
            <div><label class="form-label">City</label><input name="city" value="{{ old('city') }}" class="form-input"></div>
            <div><label class="form-label">State</label><input name="state" value="{{ old('state') }}" class="form-input"></div>
            <div><label class="form-label">PIN Code</label><input name="pin_code" value="{{ old('pin_code') }}" class="form-input"></div>
            <div><label class="form-label">Payment Terms (Days)</label><input type="number" name="payment_terms_days" value="{{ old('payment_terms_days', 0) }}" class="form-input"></div>
            <div><label class="form-label">Status</label><select name="status" class="form-input"><option value="active" @selected(old('status', 'active') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select></div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('suppliers.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Supplier</button></div>
</form>
@endsection
