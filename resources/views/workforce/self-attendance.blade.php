@extends('layouts.app', ['title' => 'My Attendance'])

@section('content')
<div class="mx-auto max-w-xl">
    <h2 class="text-2xl font-bold">My Attendance</h2>
    <p class="mb-5 text-sm text-slate-500">{{ now()->format('l, d F Y') }}</p>

    <section class="card p-6">
        <div class="grid gap-5 sm:grid-cols-2">
            <div><p class="detail-label">Check In</p><p class="detail-value">{{ $today?->check_in ? \Illuminate\Support\Carbon::parse($today->check_in)->format('h:i A') : 'Not checked in' }}</p></div>
            <div><p class="detail-label">Check Out</p><p class="detail-value">{{ $today?->check_out ? \Illuminate\Support\Carbon::parse($today->check_out)->format('h:i A') : 'Not checked out' }}</p></div>
        </div>

        <div class="mt-6 flex gap-3">
            @if(! $today?->check_in)
                <form method="POST" action="{{ route('my-attendance.check-in') }}">@csrf<button class="btn-primary">Check In</button></form>
            @elseif(! $today?->check_out)
                <span class="badge badge-success self-center">Checked in at {{ \Illuminate\Support\Carbon::parse($today->check_in)->format('h:i A') }}</span>
                <form method="POST" action="{{ route('my-attendance.check-out') }}">@csrf<button class="btn-primary">Check Out</button></form>
            @else
                <span class="badge badge-success self-center">Attendance complete for today</span>
            @endif
        </div>
    </section>
</div>
@endsection
