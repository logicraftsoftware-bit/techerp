@extends('layouts.app', ['title' => $serviceRequest->exists ? 'Edit Service Request' : 'New Service Request'])

@section('content')
<form method="POST" action="{{ $serviceRequest->exists ? route('service-requests.update', $serviceRequest) : route('service-requests.store') }}"
      class="mx-auto max-w-6xl"
      x-data="serviceRequestForm(@js($customers), @js($machines), @js($amcPlans), @js([
          'requestType' => old('request_type', $serviceRequest->request_type ?: 'new_installation'),
          'customerId' => old('customer_id', $serviceRequest->customer_id),
          'machineId' => old('machine_id', $serviceRequest->machine_id),
          'amcPlanIds' => old('amc_plan_ids', $serviceRequest->amcPlans->pluck('id')),
          'phone' => old('contact_phone', $serviceRequest->contact_phone),
          'serialNumber' => old('serial_number', $serviceRequest->serial_number),
          'assetNumber' => old('asset_number', $serviceRequest->asset_number),
          'address' => old('service_address', $serviceRequest->service_address),
          'city' => old('city', $serviceRequest->city),
          'state' => old('state', $serviceRequest->state),
          'pinCode' => old('pin_code', $serviceRequest->pin_code),
      ]))"
      x-init="init()">
    @csrf
    @if($serviceRequest->exists) @method('PUT') @endif

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><h2 class="text-2xl font-bold text-slate-900">{{ $serviceRequest->exists ? 'Edit' : 'Create' }} Service Request</h2><p class="mt-1 text-sm text-slate-500">Select the customer and machine, then confirm coverage and visit details.</p></div>
        <button class="btn-primary">Save Service Request</button>
    </div>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">1. Request Type</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border p-5" :class="requestType === 'new_installation' ? 'border-blue-500 bg-blue-50' : 'border-slate-200'"><input type="radio" name="request_type" value="new_installation" x-model="requestType" @change="requestTypeChanged" class="mr-2"><span class="font-semibold">New Installation</span><p class="ml-6 mt-1 text-sm text-slate-500">Install a selected machine for a customer.</p></label>
            <label class="cursor-pointer rounded-2xl border p-5" :class="requestType === 'existing_service' ? 'border-blue-500 bg-blue-50' : 'border-slate-200'"><input type="radio" name="request_type" value="existing_service" x-model="requestType" @change="requestTypeChanged" class="mr-2"><span class="font-semibold">Existing Machine Service</span><p class="ml-6 mt-1 text-sm text-slate-500">AMC, free service, or paid service for a customer machine.</p></label>
        </div>
    </section>

    <section class="card mb-5 p-6">
        <h3 class="mb-4 font-bold text-slate-900">2. Customer & Machine</h3>
        <div class="grid gap-5 md:grid-cols-2">
            <div class="relative" @click.outside="customerOpen = false">
                <label class="form-label">Customer *</label><input type="hidden" name="customer_id" x-model="customerId">
                <div class="relative"><input type="search" x-model="customerSearch" @focus="customerOpen = true" @input="customerId = ''; customerOpen = true; machineId = ''; machineSearch = ''" class="form-input pr-10" placeholder="Search customer by name, code or phone" autocomplete="off"><button type="button" @click="customerOpen = !customerOpen" class="absolute inset-y-0 right-0 px-3 text-slate-400">⌄</button></div>
                <div x-cloak x-show="customerOpen" class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"><template x-for="customer in filteredCustomers" :key="customer.id"><button type="button" @click="selectCustomer(customer)" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-blue-50"><span class="block font-medium" x-text="customer.customer_name"></span><span class="text-xs text-slate-400" x-text="`${customer.customer_code} · ${customer.mobile}`"></span></button></template><p x-show="!filteredCustomers.length" class="p-4 text-center text-sm text-slate-400">No matching customer.</p></div>
            </div>

            <div><label class="form-label">Customer Phone *</label><input name="contact_phone" x-model="phone" class="form-input" maxlength="20" required><p class="mt-1 text-xs text-slate-400">Prefilled from customer; editable for this request.</p></div>

            <div class="relative md:col-span-2" @click.outside="machineOpen = false">
                <label class="form-label">Machine *</label><input type="hidden" name="machine_id" x-model="machineId">
                <div class="relative"><input type="search" x-model="machineSearch" @focus="machineOpen = true" @input="machineId = ''; machineOpen = true" class="form-input pr-10" :placeholder="requestType === 'existing_service' ? 'Search this customer’s machine' : 'Search machine by code, name or model'" autocomplete="off"><button type="button" @click="machineOpen = !machineOpen" class="absolute inset-y-0 right-0 px-3 text-slate-400">⌄</button></div>
                <div x-cloak x-show="machineOpen" class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"><template x-for="machine in filteredMachines" :key="machine.id"><button type="button" @click="selectMachine(machine)" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-blue-50"><span class="block font-medium" x-text="`${machine.machine_code} — ${machine.machine_name}`"></span><span class="text-xs text-slate-400" x-text="machine.model || ''"></span></button></template><p x-show="!filteredMachines.length" class="p-4 text-center text-sm text-slate-400" x-text="requestType === 'existing_service' && !customerId ? 'Select a customer first.' : 'No matching machine.'"></p></div>
            </div>

            <div><label class="form-label">Product Category</label><input :value="selectedMachine?.machine_category?.category_name || '—'" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Product Type / Brand</label><input :value="selectedMachine?.brand_master?.brand_name || '—'" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Product Name</label><input :value="selectedMachine?.machine_name || '—'" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Model</label><input :value="selectedMachine?.model || '—'" class="form-input bg-slate-50" readonly></div>
            <div><label class="form-label">Serial Number</label><input name="serial_number" x-model="serialNumber" class="form-input" placeholder="Serial number of this unit" maxlength="100"></div>
            <div><label class="form-label">Asset Number</label><input name="asset_number" x-model="assetNumber" class="form-input" placeholder="Asset number of this unit" maxlength="100"></div>

            <div x-show="requestType === 'existing_service'" x-cloak><label class="form-label">Service Coverage *</label><select name="service_type" class="form-input" :required="requestType === 'existing_service'" :disabled="requestType !== 'existing_service'"><option value="">Select coverage</option>@foreach(['amc' => 'AMC Service', 'free_service' => 'Free Service', 'paid_service' => 'Paid Service'] as $value => $label)<option value="{{ $value }}" @selected(old('service_type', $serviceRequest->service_type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <input type="hidden" name="service_type" value="installation" :disabled="requestType !== 'new_installation'">

            <div class="md:col-span-2">
                <label class="form-label">AMC Plans <span class="font-normal text-slate-400">(select multiple if required)</span></label>
                <input type="search" x-model="amcSearch" class="form-input mb-2" placeholder="Search AMC plans">
                <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3"><template x-for="plan in filteredAmcPlans" :key="plan.id"><label class="flex cursor-pointer items-start gap-3 rounded-lg p-2 hover:bg-slate-50"><input type="checkbox" name="amc_plan_ids[]" :value="plan.id" x-model="selectedAmcPlanIds" class="mt-1"><span><span class="block text-sm font-medium" x-text="`${plan.plan_code} — ${plan.plan_name}`"></span><span class="text-xs text-slate-400" x-text="`${plan.machine_category?.category_name || ''}${plan.brand_master?.brand_name ? ' · '+plan.brand_master.brand_name : ''} · ${plan.duration.replaceAll('_', ' ')}`"></span></span></label></template><p x-show="!filteredAmcPlans.length" class="p-3 text-center text-sm text-slate-400">No matching AMC plan.</p></div>
            </div>
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
            <div class="md:col-span-2"><label class="form-label">Service Address *</label><textarea name="service_address" x-model="address" rows="2" class="form-input" required></textarea><p class="mt-1 text-xs text-slate-400">Prefilled from customer; editable for this request.</p></div>
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
function serviceRequestForm(customers, machines, amcPlans, initial) {
    return {
        customers, machines, amcPlans,
        requestType: initial.requestType,
        customerId: initial.customerId ? String(initial.customerId) : '', customerSearch: '', customerOpen: false,
        machineId: initial.machineId ? String(initial.machineId) : '', machineSearch: '', machineOpen: false,
        selectedAmcPlanIds: Array.from(initial.amcPlanIds || [], String), amcSearch: '',
        phone: initial.phone || '', serialNumber: initial.serialNumber || '', assetNumber: initial.assetNumber || '',
        address: initial.address || '', city: initial.city || '', state: initial.state || '', pinCode: initial.pinCode || '',
        init() {
            const customer = this.customers.find(item => String(item.id) === this.customerId);
            const machine = this.machines.find(item => String(item.id) === this.machineId);
            if (customer) this.customerSearch = `${customer.customer_name} · ${customer.customer_code} · ${customer.mobile}`;
            if (machine) this.machineSearch = `${machine.machine_code} — ${machine.machine_name}${machine.model ? ' · '+machine.model : ''}`;
        },
        get filteredCustomers() {
            const term = this.customerSearch.toLowerCase().trim();
            return this.customers.filter(item => !term || `${item.customer_name} ${item.customer_code} ${item.mobile}`.toLowerCase().includes(term));
        },
        get availableMachines() {
            if (this.requestType === 'new_installation') return this.machines;
            if (!this.customerId) return [];
            return this.machines.filter(item => String(item.customer_id) === this.customerId);
        },
        get filteredMachines() {
            const term = this.machineSearch.toLowerCase().trim();
            return this.availableMachines.filter(item => !term || `${item.machine_code} ${item.machine_name} ${item.model || ''}`.toLowerCase().includes(term));
        },
        get selectedMachine() { return this.machines.find(item => String(item.id) === this.machineId); },
        get filteredAmcPlans() {
            const term = this.amcSearch.toLowerCase().trim();
            return this.amcPlans.filter(item => !term || `${item.plan_code} ${item.plan_name} ${item.machine_category?.category_name || ''} ${item.brand_master?.brand_name || ''}`.toLowerCase().includes(term));
        },
        selectCustomer(customer) {
            this.customerId = String(customer.id); this.customerSearch = `${customer.customer_name} · ${customer.customer_code} · ${customer.mobile}`; this.customerOpen = false;
            this.phone = customer.mobile || ''; this.address = customer.address || ''; this.city = customer.city || ''; this.state = customer.state || ''; this.pinCode = customer.pin_code || '';
            if (this.requestType === 'existing_service') { this.machineId = ''; this.machineSearch = ''; }
        },
        selectMachine(machine) {
            this.machineId = String(machine.id); this.machineSearch = `${machine.machine_code} — ${machine.machine_name}${machine.model ? ' · '+machine.model : ''}`; this.machineOpen = false;
        },
        requestTypeChanged() { this.machineId = ''; this.machineSearch = ''; }
    }
}
</script>
@endpush
