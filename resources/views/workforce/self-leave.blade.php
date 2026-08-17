@extends('layouts.app', ['title' => 'My Leave'])

@section('content')
<div class="mx-auto max-w-3xl">
    <h2 class="text-2xl font-bold">My Leave</h2>
    <p class="mb-5 text-sm text-slate-500">Apply for leave and track your requests.</p>

    <form method="POST" action="{{ route('my-leave.store') }}" class="card mb-6 grid gap-3 p-5 md:grid-cols-2">
        @csrf
        <div><label class="form-label">Leave Type</label><select class="form-input" name="leave_type" required>@foreach(['casual', 'sick', 'earned', 'unpaid'] as $x)<option value="{{ $x }}">{{ str($x)->title() }}</option>@endforeach</select></div>
        <div></div>
        <div><label class="form-label">From Date *</label><input class="form-input" type="date" name="from_date" required></div>
        <div><label class="form-label">To Date *</label><input class="form-input" type="date" name="to_date" required></div>
        <div class="md:col-span-2"><label class="form-label">Notes *</label><input class="form-input" name="reason" placeholder="Reason for leave" required></div>
        <div class="md:col-span-2 flex justify-end"><button class="btn-primary">Apply for Leave</button></div>
    </form>

    <div class="card divide-y">
        @forelse($records as $x)
            <div class="grid gap-3 p-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-center">
                <div><b>{{ $x->leave_code }}</b><p class="text-sm text-slate-500">{{ str($x->leave_type)->title() }}</p></div>
                <div>{{ $x->from_date->format('d M Y') }} – {{ $x->to_date->format('d M Y') }}<p class="text-sm text-slate-500">{{ $x->total_days }} day(s)</p></div>
                <div><span class="status-badge {{ $x->status === 'approved' ? 'status-success' : ($x->status === 'rejected' ? 'status-danger' : 'status-muted') }}">{{ ucfirst($x->status) }}</span><p class="text-sm text-slate-500">{{ $x->reason }}</p></div>
                @if($x->status === 'pending')
                    <div class="flex gap-3">
                        <a href="{{ route('my-leave.edit', $x) }}" class="table-action">Edit</a>
                        <form method="POST" action="{{ route('my-leave.destroy', $x) }}" onsubmit="return confirm('Withdraw this leave request?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Withdraw</button></form>
                    </div>
                @else
                    <div></div>
                @endif
            </div>
        @empty
            <p class="p-12 text-center text-slate-400">No leave requests yet.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
</div>
@endsection
