@extends('layouts.app', ['title' => 'Customer-AMC Tagging'])

@section('content')
@php($isAdmin = auth()->user()->hasRole('super-admin', 'admin'))
@php($canCreateServiceRequest = $isAdmin || auth()->user()->hasPermission('service-requests.create'))
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold text-slate-900">Customer-AMC Tagging</h2><p class="mt-1 text-sm text-slate-500">Link customers and their machines to AMC plans and track expiry dates.</p></div>
    @if($isAdmin)<a href="{{ route('customer-amc-taggings.create') }}" class="btn-primary">+ Tag Customer AMC</a>@endif
</div>

<form method="GET" class="mb-5 max-w-md"><label class="sr-only" for="search">Search</label><div class="flex gap-2"><input id="search" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search customer, machine or AMC plan"><button class="btn-secondary">Search</button></div></form>

<div class="card overflow-x-auto" x-data="{ expanded: null }">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Customer</th><th class="p-4">Machine</th><th class="p-4">AMC Plan</th><th class="p-4">Plan Price</th><th class="p-4">Services</th><th class="p-4">Payment</th><th class="p-4">Start Date</th><th class="p-4">End Date</th><th class="p-4">Status</th>@if($isAdmin)<th class="p-4 text-right">Actions</th>@endif</tr></thead>
        <tbody class="divide-y">
        @forelse($records as $record)
            @php($daysLeft = today()->diffInDays($record->end_date, false))
            <tr :class="expanded === {{ $record->id }} && 'bg-blue-50/30'">
                <td class="p-4"><span class="font-semibold text-slate-800">{{ $record->customer->customer_name }}</span><div class="text-xs text-slate-400">{{ $record->customer->customer_code }}</div></td>
                <td class="p-4">{{ $record->machine->machine_name }}<div class="text-xs text-slate-400">{{ $record->machine->machine_code }}{{ $record->machine->model ? ' · '.$record->machine->model : '' }}</div></td>
                <td class="p-4">{{ $record->amcPlan->plan_name }}<div class="text-xs text-slate-400">{{ $record->amcPlan->plan_code }} · {{ str($record->amcPlan->duration)->replace('_', ' ')->title() }}</div></td>
                <td class="p-4 whitespace-nowrap font-semibold">₹{{ number_format((float) $record->amcPlan->price, 2) }}</td>
                <td class="p-4 font-semibold">{{ $record->service_count }}</td>
                <td class="p-4"><span class="font-medium">{{ $record->payment_collected_by ? str($record->payment_collected_by)->title() : 'Not recorded' }}</span>@if($record->payment_collected_by === 'staff')<div class="text-xs text-slate-400">₹{{ number_format((float) $record->paid_amount, 2) }} · {{ str($record->payment_method)->replace('_', ' ')->title() }}</div><div class="max-w-48 truncate text-xs text-slate-400" title="{{ $record->payment_remarks }}">{{ $record->payment_remarks }}</div>@endif</td>
                <td class="p-4 whitespace-nowrap">{{ $record->start_date->format('d M Y') }}</td>
                <td class="p-4 whitespace-nowrap font-semibold">{{ $record->end_date->format('d M Y') }}</td>
                <td class="p-4">@if($daysLeft < 0)<span class="status-badge status-muted">Expired</span>@elseif($daysLeft <= 31)<span class="status-badge status-warning">Expires in {{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }}</span>@else<span class="status-badge status-success">Active</span>@endif</td>
                @if($isAdmin)<td class="p-4"><div class="flex justify-end gap-2"><button type="button" @click="expanded = expanded === {{ $record->id }} ? null : {{ $record->id }}" class="table-action" aria-label="Show AMC service requests"><svg class="size-4 transition" :class="expanded === {{ $record->id }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button><a href="{{ route('customer-amc-taggings.edit', $record) }}" class="table-action">Edit</a><form method="POST" action="{{ route('customer-amc-taggings.destroy', $record) }}" onsubmit="return confirm('Delete this Customer-AMC tagging?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>@endif
            </tr>
            <tr x-cloak x-show="expanded === {{ $record->id }}" x-transition>
                <td colspan="10" class="border-t border-blue-100 bg-blue-50/40 p-5">
                    <div class="mb-3 flex items-center justify-between"><div><h3 class="font-semibold text-slate-900">AMC Service Requests</h3><p class="text-xs text-slate-500">{{ $record->serviceRequests->count() }} of {{ $record->service_count }} service slots created</p></div></div>
                    <div class="space-y-2">
                        @foreach(range(1, $record->service_count) as $serviceNumber)
                            @php($existingRequest = $record->serviceRequests->firstWhere('amc_service_number', $serviceNumber))
                            @php($completedAssignment = $existingRequest?->workAssignments->firstWhere('status', 'completed'))
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                                <span class="font-semibold text-slate-800">Service Request - {{ $serviceNumber }}</span>
                                @if($existingRequest && $completedAssignment)
                                    <span class="text-emerald-700">Completed on {{ $completedAssignment->completed_at?->format('d M Y') ?? $completedAssignment->updated_at->format('d M Y') }}</span>
                                    <span class="text-slate-300">|</span><span>Technician: <b>{{ $completedAssignment->technician->name }}</b></span>
                                    <span class="text-slate-300">|</span><span>Total Bill: <b>₹{{ number_format($completedAssignment->bill_total, 2) }}</b></span>
                                    <span class="ml-auto flex gap-2"><a href="{{ route('assignments.bill', $completedAssignment) }}" class="table-action text-blue-600">Download Bill</a><a href="{{ route('assignments.job-card', $completedAssignment) }}" class="table-action text-blue-600">Download Job Card</a><a href="{{ route('service-requests.show', $existingRequest) }}" class="table-action">View Details</a></span>
                                @elseif($existingRequest)
                                    <span class="status-badge status-warning">{{ str($existingRequest->status)->replace('_', ' ')->title() }}</span>
                                    <span class="text-slate-500">{{ $existingRequest->request_code }}</span>
                                    <a href="{{ route('service-requests.show', $existingRequest) }}" class="table-action ml-auto">View Request</a>
                                @elseif($canCreateServiceRequest)
                                    <a href="{{ route('service-requests.create', ['amc_tagging' => $record->id, 'service_number' => $serviceNumber]) }}" class="btn-primary ml-auto px-3 py-2 text-xs">Create Service Request</a>
                                @else
                                    <span class="ml-auto text-xs text-slate-400">Not created</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $isAdmin ? 10 : 9 }}" class="p-12 text-center text-slate-400">No Customer-AMC taggings found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
