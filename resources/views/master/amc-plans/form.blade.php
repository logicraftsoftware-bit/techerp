@extends('layouts.app', ['title' => $amcPlan->exists ? 'Edit AMC Plan' : 'Add AMC Plan'])

@section('content')
<form method="POST" action="{{ $amcPlan->exists ? route('amc-plans.update', $amcPlan) : route('amc-plans.store') }}" class="mx-auto max-w-5xl">
    @csrf
    @if($amcPlan->exists) @method('PUT') @endif
    <div class="mb-6 flex justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ $amcPlan->exists ? 'Edit' : 'Add' }} AMC Plan</h2>
            <p class="text-sm text-slate-500">Plan identity, coverage, duration and pricing.</p>
        </div>
        <button class="btn-primary">Save AMC Plan</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Plan ID</label><input class="form-input bg-slate-50" value="{{ $amcPlan->plan_code ?: 'Auto-generated after saving' }}" readonly></div>

            @include('master._field', ['model' => $amcPlan, 'name' => 'plan_name', 'label' => 'Plan Name', 'required' => true])

            <div>
                <label class="form-label">Product Category *</label>
                <select name="machine_category_id" class="form-input" required>
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('machine_category_id', $amcPlan->machine_category_id) == $category->id)>{{ $category->category_name }}</option>
                    @endforeach
                </select>
                <a class="mt-1 inline-block text-xs text-blue-600" href="{{ route('machine-categories.create') }}">+ Add category</a>
            </div>

            <div>
                <label class="form-label">Product Type *</label>
                <select name="brand_id" class="form-input" required>
                    <option value="">Select product type</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id', $amcPlan->brand_id) == $brand->id)>{{ $brand->brand_name }}</option>
                    @endforeach
                </select>
                <a class="mt-1 inline-block text-xs text-blue-600" href="{{ route('brands.create') }}">+ Add product type</a>
            </div>

            @include('master._field', ['model' => $amcPlan, 'name' => 'plan_type', 'label' => 'Plan Type', 'type' => 'select', 'required' => true, 'options' => ['comprehensive' => 'Comprehensive', 'semi_comprehensive' => 'Semi-Comprehensive', 'cleaning' => 'Cleaning']])

            @include('master._field', ['model' => $amcPlan, 'name' => 'duration', 'label' => 'Duration', 'type' => 'select', 'required' => true, 'options' => ['1_year' => '1 Year', '2_years' => '2 Years', '3_years' => '3 Years']])

            @include('master._field', ['model' => $amcPlan, 'name' => 'parts_included', 'label' => 'Parts Included', 'type' => 'select', 'required' => true, 'options' => ['1' => 'Yes', '0' => 'No']])

            @include('master._field', ['model' => $amcPlan, 'name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => true, 'step' => '0.01', 'min' => '0'])

            @include('master._field', ['model' => $amcPlan, 'name' => 'tax_percent', 'label' => 'Tax (%)', 'type' => 'number', 'required' => true, 'step' => '0.01', 'min' => '0', 'max' => '100'])

            @include('master._field', ['model' => $amcPlan, 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive']])

            @include('master._field', ['model' => $amcPlan, 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'wide' => true])
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('amc-plans.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save AMC Plan</button></div>
</form>
@endsection
