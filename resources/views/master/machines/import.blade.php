@extends('layouts.app', ['title' => 'Bulk Upload Machines'])
@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6"><h2 class="text-2xl font-bold text-slate-900">Bulk Upload Machines</h2><p class="mt-1 text-sm text-slate-500">Upload a CSV file to create multiple machines at once.</p></div>

    <section class="card p-6">
        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div><p class="font-semibold text-slate-800">CSV format</p><p class="text-xs text-slate-400">First row must be the column headings from the sample file. Brand and Machine Category must already exist in their respective masters -- rows referencing an unknown brand or category are skipped.</p></div>
            <a href="{{ route('machines.import.sample') }}" class="btn-secondary shrink-0 whitespace-nowrap">Download Sample CSV</a>
        </div>
        <form method="POST" action="{{ route('machines.import.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="form-label">CSV File *</label>
            <input type="file" name="file" accept=".csv,text/csv" class="form-input" required>
            @error('file')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('machines.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Upload &amp; Import</button></div>
        </form>
    </section>
</div>
@endsection
