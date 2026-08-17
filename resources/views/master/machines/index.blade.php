@extends('layouts.app', ['title' => 'Machines'])

@section('content')
<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Master Data</p>
        <h2 class="mt-2 text-3xl font-bold">Machines</h2>
        <p class="mt-1 text-sm text-slate-500">Machine catalog, pricing, stock and service periods.</p>
    </div>
    <div class="flex gap-3">
        <a class="btn-secondary" href="{{ route('machines.import.create') }}">Bulk Upload</a>
        <a class="btn-primary" href="{{ route('machines.create') }}">+ Add Machine</a>
    </div>
</div>

@if(session('import_errors') && count(session('import_errors')))
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="font-semibold">Some rows from your last import were skipped:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form class="card mb-5 flex gap-3 p-4" method="GET" action="{{ route('machines.index') }}">
    <input class="form-input max-w-md" name="search" value="{{ request('search') }}" placeholder="Search machines...">
    <button class="btn-secondary" type="submit">Search</button>
</form>

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-4">Code / Machine</th>
                <th class="px-4 py-4">Brand / Model</th>
                <th class="px-4 py-4">Free Service</th>
                <th class="px-4 py-4">Price</th>
                <th class="px-4 py-4">Stock / Location</th>
                <th class="px-4 py-4">Status</th>
                <th class="px-4 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($records as $machine)
                <tr>
                    <td class="px-4 py-4">
                        <a class="font-semibold text-blue-600" href="{{ route('machines.show', $machine) }}">{{ $machine->machine_code }}</a>
                        <div class="font-medium">{{ $machine->machine_name }}</div>
                    </td>
                    <td class="px-4 py-4">{{ $machine->brand }}<div class="text-xs text-slate-400">{{ $machine->model }}</div><div class="text-xs text-slate-400">{{ $machine->machineCategory?->category_name }}</div></td>
                    <td class="px-4 py-4">{{ str($machine->service_period)->replace('_', ' ')->title() }}</td>
                    <td class="px-4 py-4">₹{{ number_format((float) $machine->selling_price, 2) }}<div class="text-xs text-slate-400">Buy: ₹{{ number_format((float) $machine->buying_price, 2) }}</div></td>
                    <td class="px-4 py-4">{{ $machine->total_stock }}<div class="text-xs text-slate-400">{{ $machine->location_name }}</div></td>
                    <td class="px-4 py-4"><span class="status-badge {{ $machine->status === 'active' ? 'status-success' : 'status-muted' }}">{{ ucfirst($machine->status) }}</span></td>
                    <td class="px-4 py-4">
                        <div class="flex justify-end gap-4">
                            <a href="{{ route('machines.edit', $machine) }}">Edit</a>
                            <form method="POST" action="{{ route('machines.destroy', $machine) }}" onsubmit="return confirm('Delete this machine?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td class="px-4 py-12 text-center text-slate-400" colspan="7">No machines found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">{{ $records->links() }}</div>
@endsection
