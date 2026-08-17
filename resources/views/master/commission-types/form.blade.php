@extends('layouts.app', ['title' => $commissionType->exists ? 'Edit Commission Type' : 'Add Commission Type'])
@section('content')
<form method="POST" action="{{ $commissionType->exists ? route('commission-types.update', $commissionType) : route('commission-types.store') }}" class="mx-auto max-w-xl">
    @csrf
    @if($commissionType->exists) @method('PUT') @endif
    <div class="card p-7">
        <h2 class="text-2xl font-bold">{{ $commissionType->exists ? 'Edit' : 'Add' }} Commission Type</h2>
        <div class="mt-6 grid gap-5">
            @include('master._field', ['model' => $commissionType, 'name' => 'type_name', 'label' => 'Type Name', 'required' => true])
            @include('master._field', ['model' => $commissionType, 'name' => 'calculation_type', 'label' => 'Percentage / Flat', 'type' => 'select', 'required' => true, 'options' => ['' => 'Select', 'percentage' => 'Percentage', 'flat' => 'Flat']])
            @include('master._field', ['model' => $commissionType, 'name' => 'value', 'label' => 'Amount / Percentage', 'type' => 'number', 'required' => true, 'step' => '0.01', 'min' => '0'])
        </div>
        <div class="mt-7 flex justify-end gap-3"><a href="{{ route('commission-types.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Commission Type</button></div>
    </div>
</form>
@endsection
