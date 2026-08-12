@extends('layouts.app', ['title' => 'Parts Issue / Return'])
@section('content')
<h2 class="text-2xl font-bold">Parts Issue / Return</h2>
<p class="mb-5 text-sm text-slate-500">Issue stock to technicians against Work IDs and record usage or returns.</p>
<form method="POST" action="{{ route('parts-issues.store') }}" class="card mb-6 grid gap-3 p-5 md:grid-cols-4">
    @csrf
    <select name="part_id" class="form-input" required><option value="">Part</option>@foreach($parts as $x)<option value="{{ $x->id }}">{{ $x->part_name }} ({{ $x->current_stock }})</option>@endforeach</select>
    <select name="technician_id" class="form-input" required><option value="">Technician</option>@foreach($technicians as $x)<option value="{{ $x->id }}">{{ $x->employee_code }} — {{ $x->name }}</option>@endforeach</select>
    <select name="work_assignment_id" class="form-input"><option value="">Work ID</option>@foreach($assignments as $x)<option value="{{ $x->id }}">{{ $x->assignment_code }}</option>@endforeach</select>
    <input type="number" name="issued_quantity" min="1" class="form-input" placeholder="Quantity" required>
    <input name="remarks" class="form-input" placeholder="Remarks">
    <button class="btn-primary justify-center">Issue Part</button>
</form>
<div class="card divide-y">
@forelse($records as $x)
    <div class="grid gap-3 p-4 lg:grid-cols-[1.2fr_1fr_1fr_2fr]">
        <div><b>{{ $x->issue_code }}</b><p class="text-sm text-slate-500">{{ $x->part->part_name }} · {{ $x->technician->name }}</p></div>
        <div>Issued: {{ $x->issued_quantity }}<p class="text-sm capitalize text-slate-500">{{ $x->status }}</p></div>
        <div>Used {{ $x->used_quantity }} · Returned {{ $x->returned_quantity }} · Damaged {{ $x->damaged_quantity }}</div>
        <form method="POST" action="{{ route('parts-issues.update', $x) }}" class="flex flex-wrap gap-2">@csrf @method('PATCH')
            <input type="number" min="0" max="{{ $x->issued_quantity }}" name="used_quantity" value="{{ $x->used_quantity }}" class="form-input w-20" title="Used">
            <input type="number" min="0" max="{{ $x->issued_quantity }}" name="returned_quantity" value="{{ $x->returned_quantity }}" class="form-input w-20" title="Returned">
            <input type="number" min="0" max="{{ $x->issued_quantity }}" name="damaged_quantity" value="{{ $x->damaged_quantity }}" class="form-input w-20" title="Damaged">
            <button class="btn-primary">Update</button>
        </form>
    </div>
@empty<p class="p-12 text-center text-slate-400">No parts issued yet.</p>@endforelse
</div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
