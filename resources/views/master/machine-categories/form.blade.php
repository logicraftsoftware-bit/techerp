@extends('layouts.app', ['title' => $machineCategory->exists ? 'Edit Machine Category' : 'Add Machine Category'])

@section('content')
<form method="POST" action="{{ $machineCategory->exists ? route('machine-categories.update', $machineCategory) : route('machine-categories.store') }}" class="mx-auto max-w-xl">
    @csrf
    @if($machineCategory->exists) @method('PUT') @endif
    <div class="card p-7">
        <h2 class="text-2xl font-bold">{{ $machineCategory->exists ? 'Edit' : 'Add' }} Machine Category</h2>
        <div class="mt-6">@include('master._field', ['model' => $machineCategory, 'name' => 'category_name', 'label' => 'Category Name', 'required' => true])</div>
        <div class="mt-7 flex justify-end gap-3"><a href="{{ route('machine-categories.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Machine Category</button></div>
    </div>
</form>
@endsection
