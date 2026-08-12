@extends('layouts.app', ['title' => 'AMC Plan Master'])

@section('content')
@include('master._index_header', ['heading' => 'AMC Plan Master', 'description' => 'Manage AMC plans, duration and pricing.', 'create' => route('amc-plans.create'), 'singular' => 'AMC Plan'])

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="p-4">Plan ID / Name</th>
                <th class="p-4">Category / Type</th>
                <th class="p-4">Plan Type</th>
                <th class="p-4">Duration</th>
                <th class="p-4">Price / Tax</th>
                <th class="p-4">Status</th>
                <th class="p-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($records as $plan)
                <tr>
                    <td class="p-4">
                        <a class="font-semibold text-blue-600" href="{{ route('amc-plans.edit', $plan) }}">{{ $plan->plan_code }}</a>
                        <div class="text-slate-800">{{ $plan->plan_name }}</div>
                    </td>
                    <td class="p-4">{{ $plan->machineCategory?->category_name }}<div class="text-xs text-slate-400">{{ $plan->brandMaster?->brand_name }}</div></td>
                    <td class="p-4">{{ str($plan->plan_type)->replace('_', ' ')->title() }}</td>
                    <td class="p-4">{{ str($plan->duration)->replace('_', ' ')->title() }}</td>
                    <td class="p-4">₹{{ number_format((float) $plan->price, 2) }}<div class="text-xs text-slate-400">Tax: {{ rtrim(rtrim(number_format((float) $plan->tax_percent, 2), '0'), '.') }}%</div></td>
                    <td class="p-4"><span class="status-badge {{ $plan->status === 'active' ? 'status-success' : 'status-muted' }}">{{ ucfirst($plan->status) }}</span></td>
                    <td class="p-4"><div class="flex justify-end gap-2"><a class="table-action" href="{{ route('amc-plans.edit', $plan) }}">Edit</a><form method="POST" action="{{ route('amc-plans.destroy', $plan) }}" onsubmit="return confirm('Delete this AMC plan?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-12 text-center text-slate-400">No AMC plans found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
