<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-700" x-data="{ sidebar: false, profile: false }">
<div class="min-h-full">
    <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebar=false"></div>
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-950 text-slate-300 transition-transform lg:translate-x-0">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
            <div class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-600 text-xl font-black text-white shadow-lg shadow-blue-900/40">FS</div>
            <div><p class="font-bold tracking-wide text-white">FieldService</p><p class="text-xs text-slate-500">Operations CRM</p></div>
        </div>
        <nav class="h-[calc(100vh-5rem)] overflow-y-auto px-4 py-6 text-sm">
            <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[.18em] text-slate-600">Workspace</p>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"><span class="size-2 rounded-full {{ request()->routeIs('dashboard') ? 'bg-cyan-400' : 'bg-slate-700' }}"></span><span>Dashboard</span></a>
                @foreach(config('crm.navigation') as $group => $items)
                    @php $groupOpen = collect($items)->contains(fn($item) => request()->routeIs('modules.show') && request()->route('module') === $item[0]); @endphp
                    <div x-data="{ open: {{ $groupOpen ? 'true' : 'false' }} }" class="pt-2">
                        <button type="button" @click="open=!open" class="flex w-full items-center px-3 py-2 text-[10px] font-semibold uppercase tracking-[.16em] text-slate-600 hover:text-slate-400"><span>{{ $group }}</span><span class="ml-auto transition" :class="open && 'rotate-90'">›</span></button>
                        <div x-show="open" class="space-y-1">
                            @foreach($items as [$slug, $label, $description])
                                @php $resource = ['customers','brands','machines','technicians','skills']; $active = in_array($slug,$resource) ? request()->routeIs($slug.'.*') : (request()->routeIs('modules.show') && request()->route('module') === $slug); @endphp
                                <a href="{{ in_array($slug,$resource) ? route($slug.'.index') : route('modules.show', $slug) }}" class="nav-link {{ $active ? 'nav-link-active' : '' }}" title="{{ $description }}"><span class="size-2 rounded-full {{ $active ? 'bg-cyan-400' : 'bg-slate-700' }}"></span><span>{{ $label }}</span></a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="pt-2"><p class="px-3 py-2 text-[10px] font-semibold uppercase tracking-[.16em] text-slate-600">Administration</p><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}"><span class="size-2 rounded-full {{ request()->routeIs('users.*') ? 'bg-cyan-400' : 'bg-slate-700' }}"></span><span>Users</span></a></div>
            </div>
        </nav>
    </aside>
    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur md:px-8">
            <button class="mr-4 rounded-xl p-2 hover:bg-slate-100 lg:hidden" @click="sidebar=true" aria-label="Open navigation">☰</button>
            <div><h1 class="text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1><p class="hidden text-xs text-slate-400 sm:block">{{ now()->format('l, d F Y') }}</p></div>
            <div class="ml-auto flex items-center gap-3">
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
