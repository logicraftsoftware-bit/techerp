@extends('layouts.app', ['title' => $serviceRequest->request_code])

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-sm font-semibold text-blue-600">{{ $serviceRequest->request_code }}</p><h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $serviceRequest->subject }}</h2><p class="mt-1 text-sm text-slate-500">Created {{ $serviceRequest->created_at->format('d M Y, g:i A') }} by {{ $serviceRequest->creator?->name ?? 'System' }}</p></div>
        <div class="flex gap-2"><a href="{{ route('service-requests.index') }}" class="btn-secondary">Back</a><a href="{{ route('service-requests.edit', $serviceRequest) }}" class="btn-primary">Edit Request</a></div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <section class="card p-6"><h3 class="mb-4 font-bold text-slate-900">Customer</h3><dl class="space-y-3 text-sm"><div><dt class="text-slate-400">Name</dt><dd class="font-medium">{{ $serviceRequest->customer->customer_name }}</dd></div><div><dt class="text-slate-400">Mobile</dt><dd>{{ $serviceRequest->customer->mobile }}</dd></div><div><dt class="text-slate-400">Email</dt><dd>{{ $serviceRequest->customer->email ?: '—' }}</dd></div><div><dt class="text-slate-400">Service Address</dt><dd>{{ $serviceRequest->service_address }}, {{ $serviceRequest->city }}, {{ $serviceRequest->state }} {{ $serviceRequest->pin_code }}</dd></div></dl></section>
        <section class="card p-6"><h3 class="mb-4 font-bold text-slate-900">Service</h3><dl class="space-y-3 text-sm"><div><dt class="text-slate-400">Request Type</dt><dd class="font-medium">{{ str($serviceRequest->request_type)->replace('_', ' ')->title() }}</dd></div><div><dt class="text-slate-400">Coverage</dt><dd>{{ str($serviceRequest->service_type)->replace('_', ' ')->title() }}</dd></div><div><dt class="text-slate-400">Product / Machine</dt><dd>{{ $serviceRequest->machine?->machine_name ?? $serviceRequest->product_name }} {{ $serviceRequest->model ? '— '.$serviceRequest->model : '' }}</dd></div><div><dt class="text-slate-400">Priority / Status</dt><dd>{{ ucfirst($serviceRequest->priority) }} / {{ str($serviceRequest->status)->replace('_', ' ')->title() }}</dd></div></dl></section>
        <section class="card p-6 md:col-span-2"><h3 class="mb-4 font-bold text-slate-900">Request Details</h3><div class="grid gap-5 md:grid-cols-2"><div><p class="text-xs text-slate-400">Complaint / Work Required</p><p class="mt-1 whitespace-pre-line text-sm">{{ $serviceRequest->complaint ?: '—' }}</p></div><div><p class="text-xs text-slate-400">Preferred Visit</p><p class="mt-1 text-sm">{{ $serviceRequest->preferred_date?->format('d M Y') ?? 'Not specified' }} {{ $serviceRequest->preferred_time ? 'at '.date('g:i A', strtotime($serviceRequest->preferred_time)) : '' }}</p></div><div class="md:col-span-2"><p class="text-xs text-slate-400">Internal Notes</p><p class="mt-1 whitespace-pre-line text-sm">{{ $serviceRequest->notes ?: '—' }}</p></div></div></section>
    </div>
</div>
@endsection
