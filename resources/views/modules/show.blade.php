@extends('layouts.app', ['title' => $title])
@section('content')
<div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-blue-600">CRM Module</p><h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $title }}</h2><p class="mt-2 max-w-2xl text-sm text-slate-500">{{ $description }}</p></div>
    <button class="btn-primary" type="button">+ Create New</button>
</div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($features as $index => $feature)
    <article class="card p-6"><div class="mb-5 grid size-11 place-items-center rounded-2xl bg-blue-50 font-bold text-blue-700">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div><h3 class="font-bold text-slate-900">{{ $feature }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">Manage {{ str($feature)->lower() }} from this centralized workspace.</p><button type="button" class="mt-5 text-sm font-semibold text-blue-600">Open workspace →</button></article>
    @endforeach
</div>
<section class="card mt-6 overflow-hidden"><div class="border-b border-slate-100 p-6"><h3 class="font-bold text-slate-900">Recent activity</h3><p class="text-xs text-slate-400">Latest records will appear here.</p></div><div class="grid place-items-center px-6 py-16 text-center"><div class="grid size-14 place-items-center rounded-2xl bg-slate-100 text-xl">⌁</div><p class="mt-4 font-semibold text-slate-700">No records yet</p><p class="mt-1 text-sm text-slate-400">Create the first record to begin using {{ $title }}.</p></div></section>
@endsection
