@extends('layouts.app', ['title' => 'Edit Leave Request'])

@section('content')
<div class="mx-auto max-w-xl">
    <h2 class="text-2xl font-bold">Edit Leave Request</h2>
    <p class="mb-5 text-sm text-slate-500">{{ $leave->leave_code }}</p>

    <form method="POST" action="{{ route('my-leave.update', $leave) }}" class="card grid gap-4 p-6">
        @csrf
        @method('PUT')
        <div><label class="form-label">Leave Type</label><select class="form-input" name="leave_type" required>@foreach(['casual', 'sick', 'earned', 'unpaid'] as $x)<option value="{{ $x }}" @selected(old('leave_type', $leave->leave_type) === $x)>{{ str($x)->title() }}</option>@endforeach</select></div>
        <div><label class="form-label">From Date *</label><input class="form-input" type="date" name="from_date" value="{{ old('from_date', $leave->from_date->format('Y-m-d')) }}" required></div>
        <div><label class="form-label">To Date *</label><input class="form-input" type="date" name="to_date" value="{{ old('to_date', $leave->to_date->format('Y-m-d')) }}" required></div>
        <div><label class="form-label">Notes *</label><input class="form-input" name="reason" value="{{ old('reason', $leave->reason) }}" required></div>
        <div class="flex justify-end gap-3"><a href="{{ route('my-leave.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Changes</button></div>
    </form>
</div>
@endsection
