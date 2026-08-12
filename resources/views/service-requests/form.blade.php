@extends('layouts.app', ['title' => $serviceRequest->exists ? 'Edit Service Request' : 'New Service Request'])

@section('content')
<form method="POST" action="{{ $serviceRequest->exists ? route('service-requests.update', $serviceRequest) : route('service-requests.store') }}"
      class="mx-auto max-w-6xl"
      x-data="serviceRequestForm(@js($customers), @js($machines), @js(['requestType' => old('request_type', $serviceRequest->request_type ?: 'new_installation'), 'customerId' => old('customer_id', $serviceRequest->customer_id), 'machineId' => old('machine_id', $serviceRequest->machine_id)]))">
    @csrf
    @if($serviceRequest->exists) @method('PUT') @endif
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><h2 class="text-2xl font-bold text-slate-900">{{ $serviceRequest->exists ? 'Edit' : 'Create' }} Service Request</h2><p class="mt-1 text-sm text-slate-500">Tag the customer, product or machine, coverage and visit details.</p></div>
        <button class="btn-primary">Save Service Request</button>
    </div>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">1. Request Type</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border p-5" :class="requestType === 'new_installation' ? 'border-blue-500 bg-blue-50' : 'border-slate-200'"><input type="radio" name="request_type" value="new_installation" x-model="requestType" class="mr-2"><span class="font-semibold">New Installation</span><p class="ml-6 mt-1 text-sm text-slate-500">New product installation for a customer.</p></label>
            <label class="cursor-pointer rounded-2xl border p-5" :class="requestType === 'existing_service' ? 'border-blue-500 bg-blue-50' : 'border-slate-200'"><input type="radio" name="request_type" value="existing_service" x-model="requestType" class="mr-2"><span class="font-semibold">Existing Machine Service</span><p class="ml-6 mt-1 text-sm text-slate-500">AMC, free service, or paid service.</p></label>
        </div>
    </section>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">2. Customer & Product</h3>
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Customer *</label><select name="customer_id" x-model="customerId" @change="customerChanged" class="form-input" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->customer_code }} — {{ $customer->customer_name }} ({{ $customer->mobile }})</option>@endforeach</select></div>

            <div x-show="requestType === 'existing_service'" x-cloak><label class="form-label">Customer Machine *</label><select name="machine_id" x-model="machineId" class="form-input" :required="requestType === 'existing_service'"><option value="">Select machine</option><template x-for="machine in customerMachines" :key="machine.id"><option :value="machine.id" x-text="`${machine.machine_code} — ${machine.machine_name}${machine.model ? ' / '+machine.model : ''}`"></option></template></select><p x-show="customerId && !customerMachines.length" class="mt-1 text-xs text-amber-600">This customer has no active machine. Add it in Machine Master first.</p></div>

            <div x-show="requestType === 'existing_service'" x-cloak><label class="form-label">Service Coverage *</label><select name="service_type" class="form-input" :required="requestType === 'existing_service'" :disabled="requestType !== 'existing_service'"><option value="">Select coverage</option>@foreach(['amc' => 'AMC Service', 'free_service' => 'Free Service', 'paid_service' => 'Paid Service'] as $value => $label)<option value="{{ $value }}" @selected(old('service_type', $serviceRequest->service_type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <input x-show="requestType === 'new_installation'" type="hidden" name="service_type" value="installation" :disabled="requestType !== 'new_installation'">

            <div x-show="requestType === 'new_installation'" x-cloak><label class="form-label">Product Category *</label><select name="machine_category_id" class="form-input" :required="requestType === 'new_installation'"><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('machine_category_id', $serviceRequest->machine_category_id) == $category->id)>{{ $category->category_name }}</option>@endforeach</select></div>
            <div x-show="requestType === 'new_installation'" x-cloak><label class="form-label">Product Type / Brand *</label><select name="brand_id" class="form-input" :required="requestType === 'new_installation'"><option value="">Select product type</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected(old('brand_id', $serviceRequest->brand_id) == $brand->id)>{{ $brand->brand_name }}</option>@endforeach</select></div>
            <div x-show="requestType === 'new_installation'" x-cloak><label class="form-label">Product Name *</label><input name="product_name" value="{{ old('product_name', $serviceRequest->product_name) }}" class="form-input" :required="requestType === 'new_installation'"></div>
            <div x-show="requestType === 'new_installation'" x-cloak><label class="form-label">Model</label><input name="model" value="{{ old('model', $serviceRequest->model) }}" class="form-input"></div>
            <div x-show="requestType === 'new_installation'" x-cloak><label class="form-label">Serial Number</label><input name="serial_number" value="{{ old('serial_number', $serviceRequest->serial_number) }}" class="form-input"></div>
        </div>
    </section>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">3. Request & Visit Details</h3>
        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2"><label class="form-label">Subject *</label><input name="subject" value="{{ old('subject', $serviceRequest->subject) }}" class="form-input" required></div>
            <div class="md:col-span-2"><label class="form-label">Complaint / Work Required</label><textarea name="complaint" rows="3" class="form-input">{{ old('complaint', $serviceRequest->complaint) }}</textarea></div>
            <div><label class="form-label">Priority *</label><select name="priority" class="form-input" required>@foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', $serviceRequest->priority ?: 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="form-label">Status *</label><select name="status" class="form-input" required>@foreach(['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $serviceRequest->status ?: 'open') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="form-label">Preferred Date</label><input type="date" name="preferred_date" value="{{ old('preferred_date', $serviceRequest->preferred_date?->format('Y-m-d')) }}" class="form-input"></div>
            <div><label class="form-label">Preferred Time</label><input type="time" name="preferred_time" value="{{ old('preferred_time', $serviceRequest->preferred_time) }}" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Service Address *</label><textarea name="service_address" x-model="address" rows="2" class="form-input" required></textarea></div>
            <div><label class="form-label">City *</label><input name="city" x-model="city" class="form-input" required></div>
            <div><label class="form-label">State *</label><input name="state" x-model="state" class="form-input" required></div>
            <div><label class="form-label">PIN Code *</label><input name="pin_code" x-model="pinCode" class="form-input" maxlength="10" required></div>
            <div class="md:col-span-2"><label class="form-label">Internal Notes</label><textarea name="notes" rows="3" class="form-input">{{ old('notes', $serviceRequest->notes) }}</textarea></div>
        </div>
    </section>
    <div class="flex justify-end gap-3"><a href="{{ route('service-requests.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Service Request</button></div>
</form>
@endsection

@push('scripts')
<script>
function serviceRequestForm(customers, machines, initial) {
    return {
        customers, machines,
        requestType: initial.requestType,
        customerId: initial.customerId ? String(initial.customerId) : '',
        machineId: initial.machineId ? String(initial.machineId) : '',
        address: @js(old('service_address', $serviceRequest->service_address)),
        city: @js(old('city', $serviceRequest->city)),
        state: @js(old('state', $serviceRequest->state)),
        pinCode: @js(old('pin_code', $serviceRequest->pin_code)),
        get customerMachines() { return this.machines.filter(machine => String(machine.customer_id) === String(this.customerId)); },
        customerChanged() {
            this.machineId = '';
            const customer = this.customers.find(item => String(item.id) === String(this.customerId));
            if (customer) { this.address = customer.address || ''; this.city = customer.city || ''; this.state = customer.state || ''; this.pinCode = customer.pin_code || ''; }
        }
    }
}
</script>
@endpush
