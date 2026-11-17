@extends('layouts.app', ['title' => 'Customer-AMC Tagging'])

@section('content')
<style>
.tagging-filters { padding: 22px; margin-bottom: 24px; }
.tagging-filter-grid { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 18px; }
.tagging-filter-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding-top: 20px; margin-top: 20px; border-top: 1px solid #e2e8f0; }
.tagging-filter-actions .export-actions { display: flex; gap: 10px; margin-left: auto; }
.tagging-results { overflow: hidden; }
.tagging-results-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid #e2e8f0; }
.tagging-table-scroll { overflow-x: auto; }
.tagging-results table { min-width: 1280px; }
.tagging-results thead th { padding: 15px 18px; font-size: 11px; letter-spacing: .05em; white-space: nowrap; }
.tagging-results tbody td { padding: 18px; vertical-align: top; border-bottom: 1px solid #edf2f7; }
.tagging-results tbody tr:hover { background-color: #f8fafc; }
.tagging-results .status-badge, .coverage-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.tagging-results .status-muted { color: #64748b; background: #f1f5f9; }
.tagging-results .status-warning { color: #92400e; background: #fef3c7; }
.tagging-results .status-success { color: #047857; background: #d1fae5; }
.coverage-pill { color: #1d4ed8; background: #eff6ff; margin-top: 7px; }
.tagging-results .table-action { display: inline-flex; align-items: center; background: #f1f5f9; padding: 6px 9px; }
.tagging-results .customer-name { display: block; min-width: 150px; }
.dark .tagging-results tbody tr:hover { background-color: #1e293b; }
@media(max-width:1100px) { .tagging-filter-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .tagging-filter-grid { grid-template-columns: 1fr; } .tagging-filter-actions .export-actions { margin-left: 0; width: 100%; } }
</style>
@php($isAdmin = auth()->user()->hasRole('super-admin', 'admin'))
@php($canCreateServiceRequest = $isAdmin || auth()->user()->hasPermission('service-requests.create'))
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold text-slate-900">Customer-AMC Tagging</h2><p class="mt-1 text-sm text-slate-500">Manage customer coverage, machines and service plans in one place.</p></div>
    @if($isAdmin)<a href="{{ route('customer-amc-taggings.create') }}" class="btn-primary">+ Tag Customer AMC</a>@endif
</div>

<form method="GET" action="{{ route('customer-amc-taggings.index') }}" class="card tagging-filters">
    <div class="tagging-filter-grid">
        <div><label class="form-label" for="search">Search</label><input id="search" name="search" value="{{ request('search') }}" class="form-input" placeholder="Customer, mobile, machine or plan"></div>
        <div><label class="form-label" for="coverage">Customer Type</label><select id="coverage" name="customer_type" class="form-input"><option value="">All types</option>@foreach(['in_warranty' => 'In Warranty', 'amc' => 'AMC', 'out_of_warranty' => 'Out Of Warranty'] as $value => $label)<option value="{{ $value }}" @selected(request('customer_type') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="form-label" for="area">Area (Customer City)</label><select id="area" name="area" class="form-input"><option value="">All areas</option>@foreach($areas as $area)<option value="{{ $area }}" @selected(request('area') === $area)>{{ $area }}</option>@endforeach</select></div>
        <div><label class="form-label" for="machine">Machine</label><select id="machine" name="machine_id" class="form-input"><option value="">All machines</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->machine_name }} ({{ $machine->machine_code }})</option>@endforeach</select></div>
        <div><label class="form-label" for="model">Model</label><select id="model" name="model" class="form-input"><option value="">All models</option>@foreach($models as $model)<option value="{{ $model }}" @selected(request('model') === $model)>{{ $model }}</option>@endforeach</select></div>
        <div><label class="form-label" for="from-date">From Date (Created)</label><input id="from-date" type="date" name="from_date" value="{{ request('from_date') }}" class="form-input"></div>
        <div><label class="form-label" for="to-date">To Date (Created)</label><input id="to-date" type="date" name="to_date" value="{{ request('to_date') }}" class="form-input"></div>
    </div>
    <div class="tagging-filter-actions"><button class="btn-primary">Apply Filters</button><a href="{{ route('customer-amc-taggings.index') }}" class="btn-secondary">Reset</a><div class="export-actions"><button type="submit" name="export" value="csv" class="btn-secondary">Export CSV</button><button type="submit" name="export" value="pdf" class="btn-secondary">Export PDF</button></div></div>
</form>

<div class="card tagging-results" x-data="{ expanded: null }">
    <div class="tagging-results-header"><div><strong>Customer Records</strong><p class="text-xs text-slate-400">{{ number_format($records->total()) }} matching records</p></div><span class="text-xs text-slate-400">Newest first</span></div>
    <div class="tagging-table-scroll"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Customer</th><th class="p-4">Machine</th><th class="p-4">AMC Plan</th><th class="p-4">Plan Price</th><th class="p-4">Services</th><th class="p-4">Payment</th><th class="p-4">Start Date</th><th class="p-4">End Date</th><th class="p-4">Status</th>@if($isAdmin)<th class="p-4 text-right">Actions</th>@endif</tr></thead>
        <tbody class="divide-y">
        @forelse($records as $record)
            @php($daysLeft = $record->end_date ? today()->diffInDays($record->end_date, false) : null)
            <tr :class="expanded === {{ $record->id }} && 'bg-blue-50/30'">
                <td class="p-4"><span class="font-semibold text-slate-800 customer-name">{{ $record->customer->customer_name }}</span><div class="text-xs text-slate-400">{{ $record->customer->customer_code }} &middot; {{ $record->customer->city }}</div><span class="coverage-pill">{{ $record->customer_type === 'amc' ? 'AMC' : ($record->customer_type === 'in_warranty' ? 'In Warranty' : 'Out Of Warranty') }}</span></td>
                <td class="p-4">{{ $record->machine->machine_name }}<div class="text-xs text-slate-400">{{ $record->machine->machine_code }}{{ $record->machine->model ? ' · '.$record->machine->model : '' }}</div></td>
                <td class="p-4">{{ $record->amcPlan?->plan_name ?? '-' }}<div class="text-xs text-slate-400">{{ $record->amcPlan?->plan_code ?? '-' }} · {{ str($record->amcPlan?->duration)->replace('_', ' ')->title() }}</div></td>
                <td class="p-4 whitespace-nowrap font-semibold">₹{{ number_format((float) $record->amcPlan?->price, 2) }}</td>
                <td class="p-4 font-semibold">{{ $record->service_count }}</td>
                <td class="p-4"><span class="font-medium">{{ $record->payment_collected_by ? str($record->payment_collected_by)->title() : 'Not recorded' }}</span>@if($record->paid_amount !== null || $record->payment_method || $record->payment_remarks)<div class="text-xs text-slate-400">₹{{ number_format((float) $record->paid_amount, 2) }} · {{ str($record->payment_method)->replace('_', ' ')->title() }}</div><div class="max-w-48 truncate text-xs text-slate-400" title="{{ $record->payment_remarks }}">{{ $record->payment_remarks }}</div>@endif</td>
                <td class="p-4 whitespace-nowrap">{{ $record->start_date?->format('d M Y') ?? '-' }}</td>
                <td class="p-4 whitespace-nowrap font-semibold">{{ $record->end_date?->format('d M Y') ?? '-' }}</td>
                <td class="p-4">@if($record->customer_type !== 'amc')<span class="status-badge status-muted">{{ $record->customer_type === 'in_warranty' ? 'In Warranty' : 'Out Of Warranty' }}</span>@elseif($daysLeft < 0)<span class="status-badge status-muted">Expired</span>@elseif($daysLeft <= 31)<span class="status-badge status-warning">Expires in {{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }}</span>@else<span class="status-badge status-success">Active</span>@endif</td>
                @if($isAdmin)<td class="p-4"><div class="flex justify-end gap-2"><button type="button" @click="expanded = expanded === {{ $record->id }} ? null : {{ $record->id }}" class="table-action" aria-label="Show AMC service requests"><svg class="size-4 transition" :class="expanded === {{ $record->id }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button><a href="{{ route('customer-amc-taggings.edit', $record) }}" class="table-action">Edit</a><form method="POST" action="{{ route('customer-amc-taggings.destroy', $record) }}" onsubmit="return confirm('Delete this Customer-AMC tagging?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>@endif
            </tr>
            <tr x-cloak x-show="expanded === {{ $record->id }}" x-transition>
                <td colspan="10" class="border-t border-blue-100 bg-blue-50/40 p-5">
                    <div class="mb-3 flex items-center justify-between"><div><h3 class="font-semibold text-slate-900">AMC Service Requests</h3><p class="text-xs text-slate-500">{{ $record->serviceRequests->count() }} of {{ $record->service_count }} service slots created</p></div></div>
                    @if($record->service_count > 0)
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
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-5 text-center text-sm text-slate-500">No services included in this AMC plan.</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $isAdmin ? 10 : 9 }}" class="p-12 text-center text-slate-400">No Customer-AMC taggings found.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
