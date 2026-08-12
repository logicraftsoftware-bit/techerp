@extends('layouts.app', ['title' => 'Service Report '.$serviceRequest->request_code])

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex justify-between">
        <div><p class="font-semibold text-blue-600">{{ $serviceRequest->request_code }}</p><h2 class="text-2xl font-bold">{{ $serviceRequest->subject }}</h2><p class="text-sm text-slate-500">Full service lifecycle report</p></div>
        <a href="{{ route('service-reports.index') }}" class="btn-secondary">Back</a>
    </div>
    <div class="grid gap-5 md:grid-cols-3">
        <section class="card p-5"><p class="text-xs uppercase text-slate-400">Customer</p><p class="mt-2 font-semibold">{{ $serviceRequest->customer->customer_name }}</p><p class="text-sm">{{ $serviceRequest->contact_phone }}</p></section>
        <section class="card p-5"><p class="text-xs uppercase text-slate-400">Machine / Service</p><p class="mt-2 font-semibold">{{ $serviceRequest->machine?->machine_name ?? $serviceRequest->product_name }}</p><p class="text-sm">{{ str($serviceRequest->service_type)->replace('_', ' ')->title() }}</p></section>
        <section class="card p-5"><p class="text-xs uppercase text-slate-400">Current Status</p><p class="mt-2 font-semibold">{{ str($serviceRequest->status)->replace('_', ' ')->title() }}</p><p class="text-sm">Created {{ $serviceRequest->created_at->format('d M Y') }}</p></section>
    </div>
    <section class="card mt-6 p-6">
        <h3 class="mb-6 font-bold">Lifecycle Timeline</h3>
        <div class="relative border-l-2 border-blue-200 pl-7 dark:border-blue-900">
            <div class="relative pb-8"><span class="absolute -left-[35px] top-1 size-3 rounded-full bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-950"></span><p class="font-semibold">Service Request Created</p><p class="text-sm text-slate-500">By {{ $serviceRequest->creator?->name ?? 'System' }} · {{ $serviceRequest->created_at->format('d M Y, g:i:s A') }}</p><p class="mt-2 text-sm">{{ $serviceRequest->complaint ?: 'No complaint details.' }}</p></div>
            @foreach($serviceRequest->workAssignments as $assignment)
                <div class="relative pb-8"><span class="absolute -left-[35px] top-1 size-3 rounded-full bg-cyan-500 ring-4 ring-cyan-100 dark:ring-cyan-950"></span><p class="font-semibold">Work Assigned: <a href="{{ route('work-status.show', $assignment) }}" class="text-blue-600">{{ $assignment->assignment_code }}</a></p><p class="text-sm text-slate-500">{{ $assignment->technician->name }} · {{ $assignment->scheduled_date->format('d M Y') }} {{ date('g:i A', strtotime($assignment->start_time)) }}</p></div>
                @foreach($assignment->statusHistories->sortBy('created_at') as $history)
                    <div class="relative pb-8"><span class="absolute -left-[35px] top-1 size-3 rounded-full bg-violet-500 ring-4 ring-violet-100 dark:ring-violet-950"></span><p class="font-semibold">{{ $assignment->assignment_code }}: {{ str($history->to_status)->replace('_', ' ')->title() }}</p><p class="text-sm text-slate-500">By {{ $history->changedBy?->name ?? 'System' }} · {{ $history->created_at->format('d M Y, g:i:s A') }}</p>@if($history->remarks)<p class="mt-2 text-sm">{{ $history->remarks }}</p>@endif</div>
                @endforeach
            @endforeach
            @if($serviceRequest->status === 'completed')
                <div class="relative"><span class="absolute -left-[35px] top-1 size-3 rounded-full bg-emerald-500 ring-4 ring-emerald-100 dark:ring-emerald-950"></span><p class="font-semibold">Service Closed</p><p class="text-sm text-slate-500">Request marked completed.</p></div>
            @endif
        </div>
    </section>
</div>
@endsection
