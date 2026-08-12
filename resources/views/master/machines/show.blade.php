@extends('layouts.app', ['title' => $machine->machine_name])

@section('content')
<div class="mb-6 flex justify-between">
    <div>
        <p class="text-sm text-blue-600">{{ $machine->machine_code }}</p>
        <h2 class="text-3xl font-bold">{{ $machine->machine_name }}</h2>
        <p class="text-slate-500">{{ $machine->brand }} {{ $machine->model }}</p>
    </div>
    <a class="btn-primary" href="{{ route('machines.edit', $machine) }}">Edit Machine</a>
</div>

<section class="card p-6">
    <div class="grid gap-5 sm:grid-cols-4">
        @foreach([
            'Machine Category' => $machine->machineCategory?->category_name,
            'Brand' => $machine->brand,
            'Model' => $machine->model,
            'Serial Number' => $machine->serial_number,
            'Asset Number' => $machine->asset_number,
            'Mfg Date' => $machine->manufacturing_date?->format('d M Y'),
            'Free Service Period' => str($machine->service_period)->replace('_', ' ')->title(),
            'Buying Price' => '₹'.number_format((float) $machine->buying_price, 2),
            'Selling Price' => '₹'.number_format((float) $machine->selling_price, 2),
            'Total Stock' => $machine->total_stock,
            'Location' => $machine->location_name,
            'Status' => ucfirst($machine->status),
        ] as $label => $value)
            <div><p class="detail-label">{{ $label }}</p><p class="detail-value">{{ $value ?: '—' }}</p></div>
        @endforeach
    </div>
</section>

<section class="card mt-6 p-6">
    <h3 class="font-bold">Photos & Documents</h3>
    <div class="mt-4 flex flex-wrap gap-3">
        @forelse($machine->documents as $document)
            @php
                $uploadLabel = match ($document->document_type) {
                    'photo' => 'Machine Photo',
                    'warranty' => 'Warranty Card',
                    'service_coupon' => 'Service Coupon',
                    default => str($document->document_type)->replace('_', ' ')->title(),
                };
            @endphp
            <a href="{{ route('machine-documents.show', $document) }}" target="_blank" class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-blue-300 hover:shadow-md" style="width: 170px;">
                @if(str_starts_with($document->mime_type ?? '', 'image/'))
                    <img src="{{ route('machine-documents.show', $document) }}" alt="{{ $uploadLabel }}" class="w-full bg-slate-100 object-cover transition group-hover:scale-[1.02]" style="height: 105px;" loading="lazy">
                @else
                    <div class="flex items-center justify-center bg-slate-100 text-xs font-semibold text-slate-500" style="height: 105px;">Open document</div>
                @endif
                <div class="p-2">
                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">{{ $uploadLabel }}</span>
                    <p class="mt-1 truncate text-xs text-slate-700" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-400">No files uploaded.</p>
        @endforelse
    </div>
</section>
@endsection
