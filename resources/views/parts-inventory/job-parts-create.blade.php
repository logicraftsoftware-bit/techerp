@extends('layouts.app', ['title' => 'Add Job Consumption'])
@section('content')
<form method="POST" action="{{ route('job-parts.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Add Job Consumption</h2><p class="mt-1 text-sm text-slate-500">Work-wise consumption and automatic stock deduction.</p></div>
        <button class="btn-primary">Add Consumption</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Work ID *</label><select name="work_assignment_id" class="form-input" required><option value="">Work ID</option>@foreach($assignments as $x)<option value="{{ $x->id }}" @selected(old('work_assignment_id') == $x->id)>{{ $x->assignment_code }}</option>@endforeach</select></div>
            <div><label class="form-label">Part *</label><select name="part_id" class="form-input" required><option value="">Part</option>@foreach($parts as $x)<option value="{{ $x->id }}" @selected(old('part_id') == $x->id)>{{ $x->part_name }} ({{ $x->current_stock }})</option>@endforeach</select></div>
            <div><label class="form-label">Quantity *</label><input type="number" name="quantity" min="1" value="{{ old('quantity') }}" class="form-input" required></div>
            <div><label class="form-label">Rate *</label><input type="number" step=".01" name="rate" value="{{ old('rate') }}" class="form-input" required></div>
            <div><label class="form-label">Tax %</label><input type="number" step=".01" name="tax_percent" value="{{ old('tax_percent', 0) }}" class="form-input"></div>
            <div><label class="form-label">Serial Number</label><input name="serial_number" value="{{ old('serial_number') }}" class="form-input"></div>
            <div><label class="form-label">Under Warranty</label><label class="form-input flex items-center gap-2"><input type="checkbox" name="under_warranty" value="1" @checked(old('under_warranty'))> <span>Under warranty</span></label></div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('job-parts.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Add Consumption</button></div>
</form>
@endsection
