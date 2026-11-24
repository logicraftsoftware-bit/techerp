@extends('layouts.app', ['title' => 'Notifications'])

@section('content')
<section class="card overflow-hidden">
    <header class="border-b border-slate-200 p-5">
        <h2 class="text-lg font-bold text-slate-900">Upcoming Coverage Expiry</h2>
        <p class="text-sm text-slate-500">{{ $alerts?->total() ?? 0 }} expiring within the next month</p>
    </header>

    <ul class="divide-y divide-slate-200">
        @forelse($alerts ?? [] as $alert)
            @php($daysLeft = (int) today()->diffInDays($alert->end_date))
            <li>
                <a href="{{ route('customer-amc-taggings.edit', $alert) }}" class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $alert->customer?->customer_name ?? 'Customer unavailable' }} · {{ $alert->machine?->machine_name ?? 'Machine unavailable' }}</p>
                        <p class="text-sm text-slate-500">{{ $alert->amcPlan?->plan_name ?? str($alert->customer_type)->replace('_', ' ')->title() }} ends {{ $alert->end_date->format('d M Y') }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-semibold text-amber-700">{{ $daysLeft === 0 ? 'Expires today' : $daysLeft.' '.str('day')->plural($daysLeft).' left' }}</span>
                </a>
            </li>
        @empty
            <li class="p-8 text-center text-sm text-slate-500">No upcoming expiry alerts.</li>
        @endforelse
    </ul>

    @if($alerts?->hasPages())
        <div class="border-t border-slate-200 p-5">{{ $alerts->links() }}</div>
    @endif
</section>
@endsection
