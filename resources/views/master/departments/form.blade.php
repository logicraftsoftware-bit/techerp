@extends('layouts.app', ['title' => $department->exists ? 'Edit Department' : 'Add Department'])

@section('content')
<form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}" class="mx-auto max-w-xl">
    @csrf
    @if($department->exists) @method('PUT') @endif
    <div class="card p-7">
        <h2 class="text-2xl font-bold">{{ $department->exists ? 'Edit' : 'Add' }} Department</h2>
        <div class="mt-6">@include('master._field', ['model' => $department, 'name' => 'department_name', 'label' => 'Department Name', 'required' => true])</div>
        <div class="mt-7 flex justify-end gap-3"><a href="{{ route('departments.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Department</button></div>
    </div>
</form>
@endsection
