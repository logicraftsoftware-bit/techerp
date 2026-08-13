@extends('layouts.app', ['title' => 'Create Part Request'])
@section('content')
<form method="POST" action="{{ route('parts-requests.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Create Part Request</h2><p class="mt-1 text-sm text-slate-500">Request parts for a technician or Work ID.</p></div>
        <button class="btn-primary">Create Request</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Technician *</label><select name="technician_id" class="form-input" required><option value="">Technician</option>@foreach($technicians as $x)<option value="{{ $x->id }}" @selected(old('technician_id') == $x->id)>{{ $x->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Work ID</label><select name="work_assignment_id" class="form-input"><option value="">Work ID</option>@foreach($assignments as $x)<option value="{{ $x->id }}" @selected(old('work_assignment_id') == $x->id)>{{ $x->assignment_code }}</option>@endforeach</select></div>
            <div><label class="form-label">Part *</label><select name="part_id" class="form-input" required><option value="">Part</option>@foreach($parts as $x)<option value="{{ $x->id }}" @selected(old('part_id') == $x->id)>{{ $x->part_name }}</option>@endforeach</select></div>
            <div><label class="form-label">Quantity *</label><input type="number" name="quantity" min="1" value="{{ old('quantity') }}" class="form-input" required></div>
            <div><label class="form-label">Urgency</label><select name="urgency" class="form-input"><option value="normal" @selected(old('urgency', 'normal') === 'normal')>Normal</option><option value="high" @selected(old('urgency') === 'high')>High</option><option value="urgent" @selected(old('urgency') === 'urgent')>Urgent</option></select></div>
            <div class="md:col-span-2"><label class="form-label">Reason</label><input name="reason" value="{{ old('reason') }}" class="form-input"></div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('parts-requests.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Create Request</button></div>
</form>
@endsection
