@extends('layouts.app', ['title' => 'My Calendar'])

@section('content')
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><h2 class="text-2xl font-bold">My Calendar</h2><p class="mt-1 text-sm text-slate-500">Attendance, leave and holidays in one place.</p></div>
    <div class="flex items-center gap-3">
        <a href="{{ route('my-calendar.index', ['month' => $prevMonth]) }}" class="btn-secondary">‹ Prev</a>
        <span class="min-w-40 text-center font-semibold text-slate-800">{{ $month->format('F Y') }}</span>
        <a href="{{ route('my-calendar.index', ['month' => $nextMonth]) }}" class="btn-secondary">Next ›</a>
    </div>
</div>

<div x-data="{ attendanceModalOpen: false, leaveModalOpen: false, holidayModalOpen: false, selectedDate: '', selectedLabel: '' }">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-4 text-xs font-medium text-slate-500">
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-full bg-emerald-400"></span> Present</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-full bg-orange-400"></span> Absent</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-full bg-amber-300"></span> Paid Leave</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-full bg-rose-400"></span> Holiday</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded-full border-2 border-blue-500"></span> Today</span>
        </div>
        <button type="button" @click="holidayModalOpen = true" class="btn-secondary">View Holiday List</button>
    </div>

    <div class="card p-5">
        @php
            $firstDayOfWeek = (int) $month->copy()->startOfMonth()->format('w');
            $daysInMonth = $month->daysInMonth;
            $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $statusStyles = [
                'present' => 'border-emerald-300 bg-emerald-50 text-emerald-700',
                'absent' => 'border-orange-300 bg-orange-50 text-orange-700',
                'leave' => 'border-amber-300 bg-amber-50 text-amber-700',
            ];
        @endphp

        <div class="grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase text-slate-400">
            @foreach($weekdays as $weekday)<div class="p-2">{{ $weekday }}</div>@endforeach
        </div>
        <div class="mt-2 grid grid-cols-7 gap-2">
            @for($i = 0; $i < $firstDayOfWeek; $i++)<div></div>@endfor
            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $dateObj = $month->copy()->day($day);
                    $dateKey = $dateObj->format('Y-m-d');
                    $holiday = $holidays->get($dateKey);
                    $att = $attendance->get($dateKey);
                    $isToday = $dateObj->isToday();
                    $isFuture = $dateObj->isFuture();
                    $cellStyle = $holiday ? 'border-rose-300 bg-rose-50 text-rose-700' : ($att ? ($statusStyles[$att->attendance_status] ?? 'border-slate-200') : 'border-slate-200');
                    $ring = $isToday ? 'ring-2 ring-offset-1 ring-blue-500' : '';
                    $statusLines = [];
                    if ($att && $att->attendance_status === 'present') {
                        $in = $att->check_in ? \Illuminate\Support\Carbon::parse($att->check_in)->format('h:i A') : null;
                        $out = $att->check_out ? \Illuminate\Support\Carbon::parse($att->check_out)->format('h:i A') : null;
                        $statusLines = $in ? array_filter(['In '.$in, $out ? 'Out '.$out : null]) : ['Present'];
                    } elseif ($att) {
                        $statusLines = [ucfirst($att->attendance_status)];
                    }
                @endphp
                @if($isToday)
                    <button type="button" @click="attendanceModalOpen = true" class="min-h-24 rounded-xl border p-2 text-left text-sm transition {{ $cellStyle }} {{ $ring }}">
                        <span class="font-semibold">{{ $day }}</span>
                        @if($holiday)<p class="mt-1 text-xs font-medium">{{ $holiday->name ?: 'Holiday' }}</p>@else @foreach($statusLines as $line)<p class="mt-0.5 text-xs font-medium leading-tight">{{ $line }}</p>@endforeach @endif
                    </button>
                @elseif($holiday)
                    <div class="min-h-24 rounded-xl border p-2 text-sm {{ $cellStyle }}">
                        <span class="font-semibold">{{ $day }}</span>
                        <p class="mt-1 text-xs font-medium">{{ $holiday->name ?: 'Holiday' }}</p>
                    </div>
                @elseif($isFuture)
                    <button type="button" @click="leaveModalOpen = true; selectedDate = '{{ $dateKey }}'; selectedLabel = '{{ $dateObj->format('d M Y') }}'" class="min-h-24 rounded-xl border p-2 text-left text-sm transition hover:border-blue-300 hover:bg-blue-50 {{ $cellStyle }}">
                        <span class="font-semibold">{{ $day }}</span>
                        @foreach($statusLines as $line)<p class="mt-0.5 text-xs font-medium leading-tight">{{ $line }}</p>@endforeach
                    </button>
                @else
                    <div class="min-h-24 rounded-xl border p-2 text-sm {{ $cellStyle }}">
                        <span class="font-semibold">{{ $day }}</span>
                        @foreach($statusLines as $line)<p class="mt-0.5 text-xs font-medium leading-tight">{{ $line }}</p>@endforeach
                    </div>
                @endif
            @endfor
        </div>
    </div>

    <div class="card mt-5 p-5">
        <h3 class="mb-3 font-bold text-slate-800">My Leave Requests</h3>
        <div class="divide-y">
            @forelse($leaves as $x)
                <div class="grid gap-3 py-3 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-center">
                    <div><b>{{ $x->leave_code }}</b><p class="text-sm text-slate-500">{{ str($x->leave_type)->title() }}</p></div>
                    <div>{{ $x->from_date->format('d M Y') }} – {{ $x->to_date->format('d M Y') }}<p class="text-sm text-slate-500">{{ $x->total_days }} day(s)</p></div>
                    <div><span class="status-badge {{ $x->status === 'approved' ? 'status-success' : ($x->status === 'rejected' ? 'status-danger' : 'status-muted') }}">{{ ucfirst($x->status) }}</span><p class="text-sm text-slate-500">{{ $x->reason }}</p></div>
                    @if($x->status === 'pending')
                        <div class="flex gap-3">
                            <a href="{{ route('my-calendar.leave.edit', $x) }}" class="table-action">Edit</a>
                            <form method="POST" action="{{ route('my-calendar.leave.destroy', $x) }}" onsubmit="return confirm('Withdraw this leave request?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Withdraw</button></form>
                        </div>
                    @else
                        <div></div>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-400">No leave requests yet. Click a future date on the calendar to apply.</p>
            @endforelse
        </div>
        @if($leaves->hasPages())<div class="mt-4">{{ $leaves->links() }}</div>@endif
    </div>

    <div x-cloak x-show="attendanceModalOpen" x-transition class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" @keydown.escape.window="attendanceModalOpen = false" @click.self="attendanceModalOpen = false">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold text-slate-900">Today's Attendance</h3>
            <p class="mt-1 text-sm text-slate-500">{{ now()->format('l, d F Y') }}</p>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div><p class="detail-label">Check In</p><p class="detail-value">{{ $today?->check_in ? \Illuminate\Support\Carbon::parse($today->check_in)->format('h:i A') : 'Not checked in' }}</p></div>
                <div><p class="detail-label">Check Out</p><p class="detail-value">{{ $today?->check_out ? \Illuminate\Support\Carbon::parse($today->check_out)->format('h:i A') : 'Not checked out' }}</p></div>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if(! $today?->check_in)
                    @if($checkInDeadline && now()->greaterThan($checkInDeadline))
                        <p class="text-sm text-rose-600">Check-in window has passed for today. Contact your admin.</p>
                    @else
                        <form method="POST" action="{{ route('my-calendar.check-in') }}">@csrf<button class="btn-primary">Check In</button></form>
                    @endif
                @elseif(! $today?->check_out)
                    <span class="badge badge-success">Checked in at {{ \Illuminate\Support\Carbon::parse($today->check_in)->format('h:i A') }}</span>
                    <form method="POST" action="{{ route('my-calendar.check-out') }}">@csrf<button class="btn-primary">Check Out</button></form>
                @else
                    <span class="badge badge-success">Attendance complete for today</span>
                @endif
            </div>
            <div class="mt-6 flex justify-end"><button type="button" class="btn-secondary" @click="attendanceModalOpen = false">Close</button></div>
        </div>
    </div>

    <div x-cloak x-show="leaveModalOpen" x-transition class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" @keydown.escape.window="leaveModalOpen = false">
        <form method="POST" action="{{ route('my-calendar.leave.store') }}" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" @click.outside="leaveModalOpen = false">
            @csrf
            <h3 class="text-lg font-bold text-slate-900">Apply for Leave</h3>
            <p class="mt-1 text-sm text-slate-500" x-text="selectedLabel"></p>
            <div class="mt-4 grid gap-4">
                <div><label class="form-label">Leave Type</label><select class="form-input" name="leave_type" required>@foreach(['casual', 'sick', 'earned', 'unpaid'] as $x)<option value="{{ $x }}">{{ str($x)->title() }}</option>@endforeach</select></div>
                <div><label class="form-label">From Date *</label><input class="form-input" type="date" name="from_date" :value="selectedDate" required></div>
                <div><label class="form-label">To Date *</label><input class="form-input" type="date" name="to_date" :value="selectedDate" required></div>
                <div><label class="form-label">Notes *</label><input class="form-input" name="reason" placeholder="Reason for leave" required></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="btn-secondary" @click="leaveModalOpen = false">Cancel</button>
                <button class="btn-primary">Save</button>
            </div>
        </form>
    </div>

    <div x-cloak x-show="holidayModalOpen" x-transition class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" @keydown.escape.window="holidayModalOpen = false" @click.self="holidayModalOpen = false">
        <div class="w-full max-w-lg max-h-[80vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between"><h3 class="text-lg font-bold text-slate-900">{{ $month->year }} Holidays</h3><button type="button" class="text-slate-400 hover:text-slate-600" @click="holidayModalOpen = false">✕</button></div>
            @php $groupedHolidays = $yearHolidays->groupBy(fn ($h) => $h->holiday_date->format('F')); @endphp
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @forelse($groupedHolidays as $monthName => $monthHolidays)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $monthName }}</p>
                        <div class="mt-1 divide-y">
                            @foreach($monthHolidays as $holiday)
                                <p class="py-1.5 text-sm"><span class="font-semibold text-rose-600">{{ $holiday->holiday_date->format('d M') }}</span> <span class="text-slate-500">— {{ $holiday->name ?: 'Holiday' }}</span></p>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400 sm:col-span-2">No holidays added for {{ $month->year }}.</p>
                @endforelse
            </div>
            <div class="mt-6 flex justify-end"><button type="button" class="btn-secondary" @click="holidayModalOpen = false">Close</button></div>
        </div>
    </div>
</div>
@endsection
