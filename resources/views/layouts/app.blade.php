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
            @php
                $nav = [
                    ['Dashboard','dashboard','dashboard','grid'], ['Customers',null,'customers.*','users'], ['Machines',null,'machines.*','machine'],
                    ['Technicians',null,'technicians.*','tool'], ['Attendance',null,'attendance.*','calendar'], ['Leave',null,'leave.*','leave'],
                    ['Inventory',null,'inventory.*','box'], ['Service Requests',null,'service-requests.*','ticket'], ['Job Cards',null,'jobs.*','clipboard'],
                    ['Salary',null,'salary.*','wallet'], ['Expenses',null,'expenses.*','receipt'], ['AMC & Maintenance',null,'amc.*','shield'],
                    ['Reports',null,'reports.*','chart'], ['Users','users.index','users.*','user-cog'], ['Settings',null,'settings.*','settings'],
                ];
            @endphp
            <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[.18em] text-slate-600">Workspace</p>
            <div class="space-y-1">
                @foreach($nav as [$label,$route,$active,$icon])
                    @if($route)
                    <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($active) ? 'nav-link-active' : '' }}">
                        <span class="size-2 rounded-full {{ request()->routeIs($active) ? 'bg-cyan-400' : 'bg-slate-700' }}"></span><span>{{ $label }}</span>
                    </a>
                    @else
                    <span class="nav-link cursor-not-allowed opacity-50" title="Available in a later phase"><span class="size-2 rounded-full bg-slate-700"></span><span>{{ $label }}</span><span class="ml-auto text-[9px] uppercase">Soon</span></span>
                    @endif
                @endforeach
            </div>
        </nav>
    </aside>
    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur md:px-8">
            <button class="mr-4 rounded-xl p-2 hover:bg-slate-100 lg:hidden" @click="sidebar=true" aria-label="Open navigation">☰</button>
            <div><h1 class="text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1><p class="hidden text-xs text-slate-400 sm:block">{{ now()->format('l, d F Y') }}</p></div>
            <div class="ml-auto flex items-center gap-3">
                <button class="relative grid size-10 place-items-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50" aria-label="Notifications">🔔<span class="absolute right-2 top-2 size-2 rounded-full bg-rose-500 ring-2 ring-white"></span></button>
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
            @if(session('success'))<div x-data="{show:true}" x-show="show" x-transition class="mb-5 flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><span>{{ session('success') }}</span><button class="ml-auto" @click="show=false">×</button></div>@endif
            @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
            {{ $slot ?? '' }}@yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body></html>
