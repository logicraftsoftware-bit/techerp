@extends('layouts.app', ['title' => 'Issue Part'])
@section('content')
<form method="POST" action="{{ route('parts-issues.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Issue Part</h2><p class="mt-1 text-sm text-slate-500">Issue stock to a technician against a Work ID.</p></div>
        <button class="btn-primary">Issue Part</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Part *</label><select name="part_id" class="form-input" required><option value="">Part</option>@foreach($parts as $x)<option value="{{ $x->id }}" @selected(old('part_id') == $x->id)>{{ $x->part_name }} ({{ $x->current_stock }})</option>@endforeach</select></div>
            <div><label class="form-label">Technician *</label><select name="technician_id" class="form-input" required><option value="">Technician</option>@foreach($technicians as $x)<option value="{{ $x->id }}" @selected(old('technician_id') == $x->id)>{{ $x->employee_code }} — {{ $x->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Work ID</label><select name="work_assignment_id" class="form-input"><option value="">Work ID</option>@foreach($assignments as $x)<option value="{{ $x->id }}" @selected(old('work_assignment_id') == $x->id)>{{ $x->assignment_code }}</option>@endforeach</select></div>
            <div><label class="form-label">Quantity *</label><input type="number" name="issued_quantity" min="1" value="{{ old('issued_quantity') }}" class="form-input" required></div>
            <div class="md:col-span-2"><label class="form-label">Remarks</label><textarea name="remarks" rows="3" class="form-input">{{ old('remarks') }}</textarea></div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('parts-issues.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Issue Part</button></div>
</form>
@endsection
