@extends('layouts.app', ['title' => 'Holiday Master'])

@section('content')
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold">Holiday Master</h2><p class="mt-1 text-sm text-slate-500">Company holiday calendar.</p></div>
    <div class="flex items-center gap-3">
        <a href="{{ route('holidays.index', ['month' => $prevMonth]) }}" class="btn-secondary">‹ Prev</a>
        <span class="min-w-40 text-center font-semibold text-slate-800">{{ $month->format('F Y') }}</span>
        <a href="{{ route('holidays.index', ['month' => $nextMonth]) }}" class="btn-secondary">Next ›</a>
    </div>
</div>

<form method="POST" action="{{ route('holidays.store') }}" class="card mb-6 flex flex-wrap items-end gap-3 p-5">
    @csrf
    <div><label class="form-label">Holiday Date *</label><input type="date" name="holiday_date" class="form-input" required></div>
    <div><label class="form-label">Name</label><input name="name" class="form-input" placeholder="e.g. Diwali"></div>
    <button class="btn-primary">+ Add Holiday</button>
    @error('holiday_date')<p class="w-full text-sm text-rose-600">{{ $message }}</p>@enderror
</form>

@php
    $firstDayOfWeek = (int) $month->copy()->startOfMonth()->format('w');
    $daysInMonth = $month->daysInMonth;
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp

<div class="card p-5">
    <div class="grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase text-slate-400">
        @foreach($weekdays as $weekday)<div class="p-2">{{ $weekday }}</div>@endforeach
    </div>
    <div class="mt-2 grid grid-cols-7 gap-2">
        @for($i = 0; $i < $firstDayOfWeek; $i++)<div></div>@endfor
        @for($day = 1; $day <= $daysInMonth; $day++)
            @php
                $dateKey = $month->copy()->day($day)->format('Y-m-d');
                $holiday = $holidays->get($dateKey);
            @endphp
            <div class="min-h-24 rounded-xl border p-2 text-sm {{ $holiday ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-200' }}">
                <div class="flex items-start justify-between">
                    <span class="font-semibold">{{ $day }}</span>
                    @if($holiday)
                        <form method="POST" action="{{ route('holidays.destroy', ['holiday' => $holiday, 'month' => $month->format('Y-m')]) }}" onsubmit="return confirm('Remove this holiday?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-500 hover:text-rose-700" title="Remove holiday">×</button>
                        </form>
                    @endif
                </div>
                @if($holiday)<p class="mt-1 text-xs font-medium">{{ $holiday->name ?: 'Holiday' }}</p>@endif
            </div>
        @endfor
    </div>
</div>
@endsection
