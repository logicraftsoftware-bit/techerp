@extends('layouts.app', ['title' => 'Parts Master'])
@section('content')
<div class="mb-6 flex items-end justify-between gap-4">
    <div><h2 class="text-2xl font-bold">Parts Master</h2><p class="mt-1 text-sm text-slate-500">Catalog, pricing, warranty and stock thresholds.</p></div>
    <div class="flex gap-3"><a href="{{ route('parts.import.create') }}" class="btn-secondary">Bulk Upload</a><a href="{{ route('parts.create') }}" class="btn-primary">+ Add Part</a></div>
</div>

@if(session('import_errors') && count(session('import_errors')))
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="font-semibold">Some rows from your last import were skipped:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form class="card mb-5 flex gap-3 p-4" method="GET" action="{{ route('parts.index') }}">
    <input class="form-input max-w-md" name="search" value="{{ request('search') }}" placeholder="Search parts...">
    <button class="btn-secondary" type="submit">Search</button>
</form>

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-4">Code</th>
                <th class="px-4 py-4">Part</th>
                <th class="px-4 py-4">Category</th>
                <th class="px-4 py-4">Unit</th>
                <th class="px-4 py-4">Stock</th>
                <th class="px-4 py-4">Dealer Price</th>
                <th class="px-4 py-4">MRP</th>
                <th class="px-4 py-4">AMC / Warranty</th>
                <th class="px-4 py-4">Status</th>
                <th class="px-4 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($records as $part)
                <tr>
                    <td class="px-4 py-4">{{ $part->part_code }}</td>
                    <td class="px-4 py-4 font-medium">{{ $part->part_name }}</td>
                    <td class="px-4 py-4">{{ $part->machineCategory?->category_name }}</td>
                    <td class="px-4 py-4">{{ $part->unitMaster?->unit_name }}</td>
                    <td class="px-4 py-4">{{ $part->current_stock }}</td>
                    <td class="px-4 py-4">{{ $part->purchase_price }}</td>
                    <td class="px-4 py-4">{{ $part->selling_price }}</td>
                    <td class="px-4 py-4">{{ $part->has_amc ? 'AMC' : '—' }} / {{ $part->has_warranty ? $part->warranty_months.' mo' : '—' }}</td>
                    <td class="px-4 py-4"><span class="status-badge {{ $part->status === 'active' ? 'status-success' : 'status-muted' }}">{{ ucfirst($part->status) }}</span></td>
                    <td class="px-4 py-4">
                        <div class="flex justify-end gap-4">
                            <a href="{{ route('parts.edit', $part) }}">Edit</a>
                            <form method="POST" action="{{ route('parts.destroy', $part) }}" onsubmit="return confirm('Delete this part?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td class="px-4 py-12 text-center text-slate-400" colspan="10">No parts found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">{{ $records->links() }}</div>
@endsection
