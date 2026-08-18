@extends('layouts.app', ['title' => $canCreate ? 'Holiday Master' : 'Holidays'])

@section('content')
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold">{{ $canCreate ? 'Holiday Master' : 'Holidays' }}</h2><p class="mt-1 text-sm text-slate-500">Company holiday calendar.</p></div>
    <div class="flex items-center gap-3">
        <a href="{{ route($indexRoute, ['month' => $prevMonth]) }}" class="btn-secondary">‹ Prev</a>
        <span class="min-w-40 text-center font-semibold text-slate-800">{{ $month->format('F Y') }}</span>
        <a href="{{ route($indexRoute, ['month' => $nextMonth]) }}" class="btn-secondary">Next ›</a>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-[1fr_300px]" x-data="{ modalOpen: false, selectedDate: '', selectedLabel: '' }">
    <div>
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
                    @if($holiday)
                        <div class="min-h-24 rounded-xl border border-rose-300 bg-rose-50 p-2 text-sm text-rose-700">
                            <div class="flex items-start justify-between">
                                <span class="font-semibold">{{ $day }}</span>
                                @if($canDelete)
                                    <form method="POST" action="{{ route('holidays.destroy', ['holiday' => $holiday, 'month' => $month->format('Y-m')]) }}" onsubmit="return confirm('Remove this holiday?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-500 hover:text-rose-700" title="Remove holiday">×</button>
                                    </form>
                                @endif
                            </div>
                            <p class="mt-1 text-xs font-medium">{{ $holiday->name ?: 'Holiday' }}</p>
                        </div>
                    @elseif($canCreate)
                        <button type="button" @click="modalOpen = true; selectedDate = '{{ $dateKey }}'; selectedLabel = '{{ $month->copy()->day($day)->format('d M Y') }}'" class="min-h-24 rounded-xl border border-slate-200 p-2 text-left text-sm transition hover:border-blue-300 hover:bg-blue-50">
                            <span class="font-semibold">{{ $day }}</span>
                        </button>
                    @else
                        <div class="min-h-24 rounded-xl border border-slate-200 p-2 text-sm"><span class="font-semibold">{{ $day }}</span></div>
                    @endif
                @endfor
            </div>
        </div>

        <div class="card mt-5 p-5">
            <h3 class="mb-3 font-bold text-slate-800">Holidays in {{ $month->format('F Y') }}</h3>
            <div class="divide-y">
                @forelse($holidays->sortBy('holiday_date') as $holiday)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <div><span class="font-semibold text-rose-600">{{ $holiday->holiday_date->format('d M Y') }}</span> <span class="text-slate-500">— {{ $holiday->name ?: 'Holiday' }}</span></div>
                        @if($canDelete)
                            <form method="POST" action="{{ route('holidays.destroy', ['holiday' => $holiday, 'month' => $month->format('Y-m')]) }}" onsubmit="return confirm('Remove this holiday?')">
                                @csrf @method('DELETE')
                                <button class="table-action text-rose-600">Delete</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No holidays this month.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card p-5">
        <h3 class="mb-3 font-bold text-slate-800">{{ $month->year }} Holidays</h3>
        <div class="divide-y">
            @forelse($yearHolidays as $holiday)
                <div class="py-2 text-sm">
                    <span class="font-semibold text-rose-600">{{ $holiday->holiday_date->format('d M Y') }}</span>
                    <p class="text-slate-500">{{ $holiday->name ?: 'Holiday' }}</p>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-400">No holidays added for {{ $month->year }}.</p>
            @endforelse
        </div>
    </div>

    @if($canCreate)
        <div x-cloak x-show="modalOpen" x-transition class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" @keydown.escape.window="modalOpen = false">
            <form method="POST" action="{{ route('holidays.store') }}" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" @click.outside="modalOpen = false">
                @csrf
                <input type="hidden" name="holiday_date" :value="selectedDate">
                <h3 class="text-lg font-bold text-slate-900">Add Holiday</h3>
                <p class="mt-1 text-sm text-slate-500" x-text="selectedLabel"></p>
                <div class="mt-4"><label class="form-label">Holiday Name</label><input name="name" class="form-input" placeholder="e.g. Diwali" autofocus></div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="modalOpen = false">Cancel</button>
                    <button class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
