<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    <script>document.documentElement.classList.toggle('dark', localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches));</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-700" x-data="{ sidebar: false, profile: false }">
<div class="min-h-full">
    <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebar=false"></div>
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-80 overflow-hidden border-r border-cyan-400/10 bg-[#020b20] text-slate-300 shadow-2xl transition-transform lg:translate-x-0">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(37,99,235,.16),transparent_35%),radial-gradient(circle_at_100%_65%,rgba(6,182,212,.10),transparent_40%)]"></div>
        <div class="relative flex h-28 items-center gap-4 border-b border-white/10 px-7">
            <img src="{{ asset('images/fieldservice-logo.png') }}" alt="FieldService logo" class="rounded-xl object-contain shadow-lg" style="width: 118px; height: 54px;">
            <div><p class="text-xl font-bold tracking-wide text-white">FieldService</p><p class="text-sm text-slate-400">Operations CRM</p></div>
        </div>
        <nav class="relative h-[calc(100vh-7rem)] overflow-y-auto px-5 py-5 text-sm [scrollbar-width:thin] [scrollbar-color:rgba(34,211,238,.25)_transparent]">
            <div class="mb-3 flex items-center justify-between px-3"><p class="text-xs font-semibold uppercase tracking-[.14em] text-cyan-400">Workspace</p><span class="tracking-[.18em] text-cyan-400/70">•••</span></div>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-link mb-3 py-3.5 {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"><svg class="size-5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span class="text-base">Dashboard</span></a>
                @foreach(config('crm.navigation') as $group => $items)
                    @php
                        $resources = ['customers', 'brands', 'departments', 'machine-categories', 'machines', 'technicians', 'skills', 'amc-plans', 'service-requests', 'assignments', 'job-cards', 'work-status', 'service-reports', 'parts', 'suppliers', 'inventory', 'parts-issues', 'job-parts', 'parts-requests', 'attendance', 'leave', 'salary', 'expenses'];
                        $groupOpen = collect($items)->contains(function ($item) use ($resources) {
                            return in_array($item[0], $resources)
                                ? request()->routeIs($item[0].'.*')
                                : request()->routeIs('modules.show') && request()->route('module') === $item[0];
                        });
                    @endphp
                    @php
                        $groupColors = ['Master Data' => 'text-violet-400', 'Parts & Inventory' => 'text-sky-400', 'Service Operations' => 'text-cyan-400', 'Maintenance' => 'text-amber-400', 'Workforce' => 'text-rose-400', 'Insights & Control' => 'text-purple-400'];
                        $groupColor = $groupColors[$group] ?? 'text-cyan-400';
                    @endphp
                    <div x-data="{ open: {{ $groupOpen ? 'true' : 'false' }} }" class="sidebar-group">
                        <button type="button" @click="open=!open" class="sidebar-group-button"><svg class="size-6 {{ $groupColor }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v4m0 12v4M2 12h4m12 0h4M5 5l3 3m8 8 3 3M19 5l-3 3M8 16l-3 3"/></svg><span>{{ $group }}</span><svg class="ml-auto size-4 transition" :class="open && 'rotate-90'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button>
                        <div x-cloak x-show="open" x-transition class="sidebar-group-panel">
                            @foreach($items as [$slug, $label, $description])
                                @php $active = in_array($slug, $resources) ? request()->routeIs($slug.'.*') : (request()->routeIs('modules.show') && request()->route('module') === $slug); @endphp
                                <a href="{{ in_array($slug, $resources) ? route($slug.'.index') : route('modules.show', $slug) }}" class="nav-link {{ $active ? 'nav-link-active' : '' }}" title="{{ $description }}"><span class="size-1.5 rounded-full {{ $active ? 'bg-cyan-400 shadow-[0_0_8px_#22d3ee]' : 'bg-slate-700' }}"></span><span>{{ $label }}</span></a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div x-data="{ open: {{ request()->routeIs('users.*') ? 'true' : 'false' }} }" class="sidebar-group border-b-0"><button type="button" @click="open=!open" class="sidebar-group-button"><svg class="size-6 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg><span>Administration</span><svg class="ml-auto size-4 transition" :class="open && 'rotate-90'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button><div x-cloak x-show="open" x-transition class="sidebar-group-panel"><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}"><span class="size-1.5 rounded-full {{ request()->routeIs('users.*') ? 'bg-cyan-400' : 'bg-slate-700' }}"></span><svg class="size-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>Users</span></a></div></div>
            </div>
        </nav>
    </aside>
    <div class="lg:pl-80">
        <header class="sticky top-0 z-30 flex h-20 items-center border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur md:px-8">
            <button class="mr-4 rounded-xl p-2 hover:bg-slate-100 lg:hidden" @click="sidebar=true" aria-label="Open navigation">☰</button>
            <div><h1 class="text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1><p class="hidden text-xs text-slate-400 sm:block">{{ now()->format('l, d F Y') }}</p></div>
            <div class="ml-auto flex items-center gap-3">
                <button type="button" onclick="toggleTheme()" class="grid size-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-amber-300" aria-label="Toggle light and dark mode"><span class="dark:hidden">☾</span><span class="hidden dark:inline">☀</span></button>
                <a href="{{ route('modules.show', 'notifications') }}" class="relative grid size-10 place-items-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50" aria-label="Notifications">🔔<span class="absolute right-2 top-2 size-2 rounded-full bg-rose-500 ring-2 ring-white"></span></a>
                <div class="relative" @click.outside="profile=false">
                    <button @click="profile=!profile" class="flex items-center gap-3 rounded-xl p-1.5 hover:bg-slate-50">
                        <span class="grid size-9 place-items-center rounded-xl bg-blue-600 font-bold text-white">{{ str(auth()->user()->name)->substr(0,1)->upper() }}</span>
                        <span class="hidden text-left sm:block"><span class="block text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-400">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span></span>
                    </button>
                    <div x-cloak x-show="profile" x-transition class="absolute right-0 mt-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        <a href="{{ route('profile.edit') }}" class="dropdown-link">My profile</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-link w-full text-left text-rose-600">Sign out</button></form>
                    </div>
                </div>
            </div>
        </header>
        <main class="p-4 md:p-8">
            @if(session('success'))<div data-flash-success="{{ session('success') }}" hidden></div>@endif
            @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
            {{ $slot ?? '' }}@yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body></html>
