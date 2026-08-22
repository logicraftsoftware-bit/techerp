@extends('layouts.app', ['title' => 'Customer-AMC Tagging'])

@section('content')
@php($isAdmin = auth()->user()->hasRole('super-admin', 'admin'))
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold text-slate-900">Customer-AMC Tagging</h2><p class="mt-1 text-sm text-slate-500">Link customers and their machines to AMC plans and track expiry dates.</p></div>
    @if($isAdmin)<a href="{{ route('customer-amc-taggings.create') }}" class="btn-primary">+ Tag Customer AMC</a>@endif
</div>

<form method="GET" class="mb-5 max-w-md"><label class="sr-only" for="search">Search</label><div class="flex gap-2"><input id="search" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search customer, machine or AMC plan"><button class="btn-secondary">Search</button></div></form>

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Customer</th><th class="p-4">Machine</th><th class="p-4">AMC Plan</th><th class="p-4">Plan Price</th><th class="p-4">Services</th><th class="p-4">Payment</th><th class="p-4">Start Date</th><th class="p-4">End Date</th><th class="p-4">Status</th>@if($isAdmin)<th class="p-4 text-right">Actions</th>@endif</tr></thead>
        <tbody class="divide-y">
        @forelse($records as $record)
            @php($daysLeft = today()->diffInDays($record->end_date, false))
            <tr>
                <td class="p-4"><span class="font-semibold text-slate-800">{{ $record->customer->customer_name }}</span><div class="text-xs text-slate-400">{{ $record->customer->customer_code }}</div></td>
                <td class="p-4">{{ $record->machine->machine_name }}<div class="text-xs text-slate-400">{{ $record->machine->machine_code }}{{ $record->machine->model ? ' · '.$record->machine->model : '' }}</div></td>
                <td class="p-4">{{ $record->amcPlan->plan_name }}<div class="text-xs text-slate-400">{{ $record->amcPlan->plan_code }} · {{ str($record->amcPlan->duration)->replace('_', ' ')->title() }}</div></td>
                <td class="p-4 whitespace-nowrap font-semibold">₹{{ number_format((float) $record->amcPlan->price, 2) }}</td>
                <td class="p-4 font-semibold">{{ $record->service_count }}</td>
                <td class="p-4"><span class="font-medium">{{ $record->payment_collected_by ? str($record->payment_collected_by)->title() : 'Not recorded' }}</span>@if($record->payment_collected_by === 'staff')<div class="text-xs text-slate-400">₹{{ number_format((float) $record->paid_amount, 2) }} · {{ str($record->payment_method)->replace('_', ' ')->title() }}</div><div class="max-w-48 truncate text-xs text-slate-400" title="{{ $record->payment_remarks }}">{{ $record->payment_remarks }}</div>@endif</td>
                <td class="p-4 whitespace-nowrap">{{ $record->start_date->format('d M Y') }}</td>
                <td class="p-4 whitespace-nowrap font-semibold">{{ $record->end_date->format('d M Y') }}</td>
                <td class="p-4">@if($daysLeft < 0)<span class="status-badge status-muted">Expired</span>@elseif($daysLeft <= 31)<span class="status-badge status-warning">Expires in {{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }}</span>@else<span class="status-badge status-success">Active</span>@endif</td>
                @if($isAdmin)<td class="p-4"><div class="flex justify-end gap-2"><a href="{{ route('customer-amc-taggings.edit', $record) }}" class="table-action">Edit</a><form method="POST" action="{{ route('customer-amc-taggings.destroy', $record) }}" onsubmit="return confirm('Delete this Customer-AMC tagging?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>@endif
            </tr>
        @empty
            <tr><td colspan="{{ $isAdmin ? 10 : 9 }}" class="p-12 text-center text-slate-400">No Customer-AMC taggings found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
