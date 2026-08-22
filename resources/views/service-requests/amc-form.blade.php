@extends('layouts.app', ['title' => 'Create AMC Service Request'])

@section('content')
<form method="POST" action="{{ route('service-requests.store') }}" class="mx-auto max-w-6xl">
    @csrf
    <input type="hidden" name="customer_amc_tagging_id" value="{{ $tagging->id }}">
    <input type="hidden" name="amc_service_number" value="{{ $serviceNumber }}">
    <input type="hidden" name="request_type" value="existing_service">
    <input type="hidden" name="service_type" value="amc">
    <input type="hidden" name="customer_id" value="{{ $tagging->customer_id }}">
    <input type="hidden" name="machine_id" value="{{ $tagging->machine_id }}">
    <input type="hidden" name="amc_plan_ids[]" value="{{ $tagging->amc_plan_id }}">
    <input type="hidden" name="contact_phone" value="{{ $tagging->customer->mobile }}">
    <input type="hidden" name="serial_number" value="{{ $tagging->machine->serial_number }}">
    <input type="hidden" name="asset_number" value="{{ $tagging->machine->asset_number }}">

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><h2 class="text-2xl font-bold text-slate-900">Create Service Request - {{ $serviceNumber }}</h2><p class="mt-1 text-sm text-slate-500">AMC service {{ $serviceNumber }} of {{ $tagging->service_count }}. Contract details are locked to this tagging.</p></div><button class="btn-primary">Save Service Request</button></div>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">1. Customer Details <span class="ml-2 text-xs font-normal text-slate-400">Read only</span></h3>
        <div class="grid gap-5 md:grid-cols-3">
            <div><label class="form-label">Customer</label><input value="{{ $tagging->customer->customer_name }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Customer Code</label><input value="{{ $tagging->customer->customer_code }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Mobile</label><input value="{{ $tagging->customer->mobile }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Email</label><input value="{{ $tagging->customer->email ?: '—' }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">City / State</label><input value="{{ collect([$tagging->customer->city, $tagging->customer->state])->filter()->join(', ') }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">PIN Code</label><input value="{{ $tagging->customer->pin_code }}" class="form-input bg-slate-50" readonly></div>
        </div>
    </section>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">2. Machine & AMC Details <span class="ml-2 text-xs font-normal text-slate-400">Read only</span></h3>
        <div class="grid gap-5 md:grid-cols-3">
            <div><label class="form-label">Machine</label><input value="{{ $tagging->machine->machine_name }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Machine Code</label><input value="{{ $tagging->machine->machine_code }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Model</label><input value="{{ $tagging->machine->model ?: '—' }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Category</label><input value="{{ $tagging->machine->machineCategory?->category_name ?: '—' }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Brand</label><input value="{{ $tagging->machine->brandMaster?->brand_name ?: '—' }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">AMC Plan</label><input value="{{ $tagging->amcPlan->plan_name }} ({{ $tagging->amcPlan->plan_code }})" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">AMC Amount</label><input value="₹{{ number_format((float) $tagging->amcPlan->price, 2) }}" class="form-input bg-slate-50 font-semibold" readonly></div>
            <div><label class="form-label">AMC Period</label><input value="{{ $tagging->start_date->format('d M Y') }} — {{ $tagging->end_date->format('d M Y') }}" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Service Slot</label><input value="Service Request - {{ $serviceNumber }} of {{ $tagging->service_count }}" class="form-input bg-slate-50" readonly></div>
        </div>
    </section>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">3. Request & Visit Details</h3>
        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2"><label class="form-label">Subject *</label><input name="subject" value="{{ old('subject') }}" class="form-input" required></div>
            <div class="md:col-span-2"><label class="form-label">Complaint / Work Required</label><textarea name="complaint" rows="3" class="form-input">{{ old('complaint') }}</textarea></div>
            <div><label class="form-label">Priority *</label><select name="priority" class="form-input" required>@foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="form-label">Status *</label><select name="status" class="form-input" required>@foreach(['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress'] as $value => $label)<option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="form-label">Preferred Date</label><input type="date" name="preferred_date" value="{{ old('preferred_date') }}" class="form-input"></div>
            <div><label class="form-label">Preferred Time</label><input type="time" name="preferred_time" value="{{ old('preferred_time') }}" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Service Address *</label><textarea name="service_address" rows="2" class="form-input" required>{{ old('service_address', $tagging->customer->address) }}</textarea></div>
            <input type="hidden" name="latitude" value="{{ old('latitude', $tagging->customer->latitude) }}">
            <input type="hidden" name="longitude" value="{{ old('longitude', $tagging->customer->longitude) }}">
            <input type="hidden" name="city" value="{{ $tagging->customer->city }}">
            <input type="hidden" name="state" value="{{ $tagging->customer->state }}">
            <input type="hidden" name="pin_code" value="{{ $tagging->customer->pin_code }}">
            <div class="md:col-span-2"><label class="form-label">Internal Notes</label><textarea name="notes" rows="3" class="form-input">{{ old('notes', $tagging->customer->notes) }}</textarea></div>
        </div>
    </section>
    <div class="flex justify-end gap-3"><a href="{{ route('customer-amc-taggings.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Service Request</button></div>
</form>
@endsection
