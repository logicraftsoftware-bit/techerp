@extends('layouts.app', ['title' => 'Add Part'])
@section('content')
<form method="POST" action="{{ route('parts.store') }}" class="mx-auto max-w-4xl" x-data="{ hasWarranty: false }">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Add Part</h2><p class="mt-1 text-sm text-slate-500">Catalog, pricing, warranty and stock thresholds.</p></div>
        <button class="btn-primary">Save Part</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Part Name *</label><input name="part_name" value="{{ old('part_name') }}" class="form-input" required></div>
            <div><label class="form-label">Category *</label><select name="machine_category_id" class="form-input" required><option value="">Select category</option>@foreach($categories as $x)<option value="{{ $x->id }}" @selected(old('machine_category_id') == $x->id)>{{ $x->category_name }}</option>@endforeach</select></div>
            <div><label class="form-label">Brand</label><select name="brand_id" class="form-input"><option value="">Any brand</option>@foreach($brands as $x)<option value="{{ $x->id }}" @selected(old('brand_id') == $x->id)>{{ $x->brand_name }}</option>@endforeach</select></div>
            <div><label class="form-label">Compatible Models</label><input name="compatible_models" value="{{ old('compatible_models') }}" class="form-input"></div>
            <div><label class="form-label">Start Date</label><input type="date" name="start_date" value="{{ old('start_date') }}" class="form-input"></div>
            <div><label class="form-label">Unit *</label><div class="flex gap-2"><select name="unit_id" class="form-input" required><option value="">Select unit</option>@foreach($units as $x)<option value="{{ $x->id }}" @selected(old('unit_id') == $x->id)>{{ $x->unit_name }}</option>@endforeach</select><a href="{{ route('units.create') }}" class="btn-secondary whitespace-nowrap">+ Unit</a></div></div>
            <div><label class="form-label">Dealer Price *</label><input type="number" step=".01" name="purchase_price" value="{{ old('purchase_price') }}" class="form-input" required></div>
            <div><label class="form-label">MRP *</label><input type="number" step=".01" name="selling_price" value="{{ old('selling_price') }}" class="form-input" required></div>
            <div><label class="form-label">Total Stock *</label><input type="number" name="current_stock" value="{{ old('current_stock', 0) }}" class="form-input" required></div>
            <div><label class="form-label">Tax %</label><input type="number" step=".01" name="tax_percent" value="{{ old('tax_percent', 0) }}" class="form-input"></div>
            <div><label class="form-label">Minimum Stock</label><input type="number" name="minimum_stock" value="{{ old('minimum_stock', 0) }}" class="form-input"></div>
            <div><label class="form-label">Status</label><select name="status" class="form-input"><option value="active" @selected(old('status', 'active') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select></div>
            <div><label class="form-label">AMC</label><label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="has_amc" value="1" @checked(old('has_amc')) class="rounded"> This part is covered under AMC</label></div>
            <div>
                <label class="form-label">Warranty</label>
                <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="has_warranty" value="1" x-model="hasWarranty" class="rounded"> This part has a warranty</label>
                <div x-show="hasWarranty" x-cloak class="mt-3"><label class="form-label">Warranty Period (Months) *</label><input type="number" min="0" name="warranty_months" value="{{ old('warranty_months') }}" class="form-input" :required="hasWarranty"></div>
            </div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('parts.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Part</button></div>
</form>
@endsection
