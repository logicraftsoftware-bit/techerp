@extends('layouts.app', ['title' => 'Add Part'])
@section('content')
<form method="POST" action="{{ route('parts.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Add Part</h2><p class="mt-1 text-sm text-slate-500">Catalog, pricing, warranty and stock thresholds.</p></div>
        <button class="btn-primary">Save Part</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Part Name *</label><input name="part_name" value="{{ old('part_name') }}" class="form-input" required></div>
            <div><label class="form-label">Category *</label><input name="category" value="{{ old('category') }}" class="form-input" required></div>
            <div><label class="form-label">Brand</label><select name="brand_id" class="form-input"><option value="">Any brand</option>@foreach($brands as $x)<option value="{{ $x->id }}" @selected(old('brand_id') == $x->id)>{{ $x->brand_name }}</option>@endforeach</select></div>
            <div><label class="form-label">Compatible Models</label><input name="compatible_models" value="{{ old('compatible_models') }}" class="form-input"></div>
            <div><label class="form-label">Unit</label><input name="unit" value="{{ old('unit', 'piece') }}" class="form-input"></div>
            <div><label class="form-label">Purchase Price *</label><input type="number" step=".01" name="purchase_price" value="{{ old('purchase_price') }}" class="form-input" required></div>
            <div><label class="form-label">Selling Price *</label><input type="number" step=".01" name="selling_price" value="{{ old('selling_price') }}" class="form-input" required></div>
            <div><label class="form-label">Tax %</label><input type="number" step=".01" name="tax_percent" value="{{ old('tax_percent', 0) }}" class="form-input"></div>
            <div><label class="form-label">Minimum Stock</label><input type="number" name="minimum_stock" value="{{ old('minimum_stock', 0) }}" class="form-input"></div>
            <div><label class="form-label">Warranty Months</label><input type="number" name="warranty_months" value="{{ old('warranty_months', 0) }}" class="form-input"></div>
            <div><label class="form-label">Status</label><select name="status" class="form-input"><option value="active" @selected(old('status', 'active') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select></div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('parts.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Part</button></div>
</form>
@endsection
