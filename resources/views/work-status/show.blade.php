@extends('layouts.app', ['title' => $assignment->assignment_code.' Status'])
@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex justify-between"><div><p class="font-semibold text-blue-600">{{ $assignment->assignment_code }}</p><h2 class="text-2xl font-bold">Work Status Timeline</h2><p class="text-sm text-slate-500">{{ $assignment->serviceRequest->customer?->customer_name ?? 'Deleted customer' }} · {{ $assignment->technician->name }}</p></div><a href="{{ route('work-status.index') }}" class="btn-secondary">Back</a></div>
    <form method="POST" action="{{ route('work-status.update', $assignment) }}" class="card mb-6 grid gap-4 p-5 md:grid-cols-[240px_1fr_auto]">@csrf @method('PATCH')
        <select name="status" class="form-input">@foreach(['scheduled','in_progress','completed','cancelled'] as $status)<option value="{{ $status }}" @selected($assignment->status === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>@endforeach</select>
        <input name="remarks" class="form-input" placeholder="Status update remarks"><button class="btn-primary justify-center">Update Status</button>
    </form>
    <section class="card p-6"><div class="space-y-0">@forelse($assignment->statusHistories as $history)<div class="relative border-l-2 border-blue-200 pb-7 pl-7 last:pb-0 dark:border-blue-900"><span class="absolute -left-[7px] top-1 size-3 rounded-full bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-950"></span><div class="flex flex-wrap justify-between gap-2"><p class="font-semibold">{{ $history->from_status ? str($history->from_status)->replace('_', ' ')->title().' → ' : '' }}{{ str($history->to_status)->replace('_', ' ')->title() }}</p><time class="text-xs text-slate-400">{{ $history->created_at->format('d M Y, g:i:s A') }}</time></div><p class="mt-1 text-sm text-slate-500">Changed by {{ $history->changedBy?->name ?? 'System' }}</p>@if($history->remarks)<p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">{{ $history->remarks }}</p>@endif</div>@empty<p class="text-slate-400">No status changes recorded yet.</p>@endforelse</div></section>
</div>
@endsection
