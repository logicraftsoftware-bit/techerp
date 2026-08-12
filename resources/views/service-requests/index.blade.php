@extends('layouts.app', ['title' => 'Service Requests'])

@section('content')
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold text-slate-900">Service Requests</h2><p class="mt-1 text-sm text-slate-500">Installation, AMC, free and paid service requests.</p></div>
    <a href="{{ route('service-requests.create') }}" class="btn-primary">+ Create Service Request</a>
</div>

<form class="card mb-5 grid gap-3 p-4 md:grid-cols-4">
    <input name="search" value="{{ request('search') }}" class="form-input" placeholder="Request ID, customer or subject">
    <select name="request_type" class="form-input"><option value="">All request types</option><option value="new_installation" @selected(request('request_type') === 'new_installation')>New Installation</option><option value="existing_service" @selected(request('request_type') === 'existing_service')>Existing Machine</option></select>
    <select name="status" class="form-input"><option value="">All statuses</option>@foreach(['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
    <div class="flex gap-2"><button class="btn-primary flex-1">Filter</button><a href="{{ route('service-requests.index') }}" class="btn-secondary">Reset</a></div>
</form>

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Request</th><th class="p-4">Customer</th><th class="p-4">Service / Machine</th><th class="p-4">Priority</th><th class="p-4">Preferred Date</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr></thead>
        <tbody class="divide-y">
        @forelse($records as $record)
            <tr>
                <td class="p-4"><a href="{{ route('service-requests.show', $record) }}" class="font-semibold text-blue-600">{{ $record->request_code }}</a><div class="mt-1 text-slate-700">{{ $record->subject }}</div></td>
                <td class="p-4"><span class="font-medium">{{ $record->customer->customer_name }}</span><div class="text-xs text-slate-400">{{ $record->customer->mobile }}</div></td>
                <td class="p-4">{{ str($record->service_type)->replace('_', ' ')->title() }}<div class="text-xs text-slate-400">{{ $record->machine?->machine_name ?? $record->product_name }}</div></td>
                <td class="p-4"><span class="status-badge {{ $record->priority === 'urgent' ? 'status-danger' : ($record->priority === 'high' ? 'status-warning' : 'status-muted') }}">{{ ucfirst($record->priority) }}</span></td>
                <td class="p-4">{{ $record->preferred_date?->format('d M Y') ?? '—' }}<div class="text-xs text-slate-400">{{ $record->preferred_time ? date('g:i A', strtotime($record->preferred_time)) : '' }}</div></td>
                <td class="p-4"><span class="status-badge {{ $record->status === 'completed' ? 'status-success' : 'status-muted' }}">{{ str($record->status)->replace('_', ' ')->title() }}</span></td>
                <td class="p-4"><div class="flex justify-end gap-2"><a class="table-action" href="{{ route('service-requests.edit', $record) }}">Edit</a><form method="POST" action="{{ route('service-requests.destroy', $record) }}" onsubmit="return confirm('Delete this service request?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-12 text-center text-slate-400">No service requests found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
