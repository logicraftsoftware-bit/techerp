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
          'latitude' => old('latitude', $serviceRequest->latitude),
          'longitude' => old('longitude', $serviceRequest->longitude),
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
                <div class="relative"><input type="search" x-model="machineSearch" @focus="machineOpen = true" @input="machineId = ''; machineOpen = true" class="form-input pr-10" placeholder="Search machine by code, name or model" autocomplete="off"><button type="button" @click="machineOpen = !machineOpen" class="absolute inset-y-0 right-0 px-3 text-slate-400">⌄</button></div>
                <div x-cloak x-show="machineOpen" class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"><template x-for="machine in filteredMachines" :key="machine.id"><button type="button" @click="selectMachine(machine)" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-blue-50"><span class="block font-medium" x-text="`${machine.machine_code} — ${machine.machine_name}`"></span><span class="text-xs text-slate-400" x-text="machine.model || ''"></span></button></template><p x-show="!filteredMachines.length" class="p-4 text-center text-sm text-slate-400">No matching machine.</p></div>
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
            <div class="md:col-span-2"><div class="mb-2 flex flex-col justify-between gap-2 sm:flex-row sm:items-end"><div><label class="form-label">Service Location</label><p class="text-xs text-slate-400">Defaults to the customer's saved location; search the address or click the map to move the pin.</p></div><div class="flex gap-2"><button type="button" id="sr-find-address" class="btn-secondary">Find Address</button><button type="button" id="sr-current-location" class="btn-secondary">Use Current Location</button></div></div><input type="hidden" id="sr-latitude" name="latitude" x-model="latitude"><input type="hidden" id="sr-longitude" name="longitude" x-model="longitude"><div id="sr-map" class="h-96 w-full rounded-2xl border border-slate-200 bg-slate-100"><div class="grid h-full place-items-center p-6 text-center text-sm text-slate-500">Loading Google Map…</div></div><p id="sr-map-coordinates" class="mt-2 text-xs text-slate-400"></p></div>
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
        address: initial.address || '', latitude: initial.latitude || '', longitude: initial.longitude || '',
        city: initial.city || '', state: initial.state || '', pinCode: initial.pinCode || '',
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
        get filteredMachines() {
            const term = this.machineSearch.toLowerCase().trim();
            return this.machines.filter(item => !term || `${item.machine_code} ${item.machine_name} ${item.model || ''}`.toLowerCase().includes(term));
        },
        get selectedMachine() { return this.machines.find(item => String(item.id) === this.machineId); },
        get filteredAmcPlans() {
            const term = this.amcSearch.toLowerCase().trim();
            return this.amcPlans.filter(item => !term || `${item.plan_code} ${item.plan_name} ${item.machine_category?.category_name || ''} ${item.brand_master?.brand_name || ''}`.toLowerCase().includes(term));
        },
        selectCustomer(customer) {
            this.customerId = String(customer.id); this.customerSearch = `${customer.customer_name} · ${customer.customer_code} · ${customer.mobile}`; this.customerOpen = false;
            this.phone = customer.mobile || ''; this.address = customer.address || ''; this.city = customer.city || ''; this.state = customer.state || ''; this.pinCode = customer.pin_code || '';
            this.latitude = customer.latitude || ''; this.longitude = customer.longitude || '';
            if (window.recenterServiceRequestMap) window.recenterServiceRequestMap(this.latitude, this.longitude);
        },
        selectMachine(machine) {
            this.machineId = String(machine.id); this.machineSearch = `${machine.machine_code} — ${machine.machine_name}${machine.model ? ' · '+machine.model : ''}`; this.machineOpen = false;
        },
        requestTypeChanged() { this.machineId = ''; this.machineSearch = ''; }
    }
}

window.initServiceRequestMap = function () {
    const latInput = document.getElementById('sr-latitude');
    const lngInput = document.getElementById('sr-longitude');
    const coordinates = document.getElementById('sr-map-coordinates');
    const existing = latInput.value && lngInput.value;
    const initial = existing ? {lat: Number(latInput.value), lng: Number(lngInput.value)} : {lat: 20.5937, lng: 78.9629};
    const map = new google.maps.Map(document.getElementById('sr-map'), {center: initial, zoom: existing ? 16 : 5, streetViewControl: false, mapTypeControl: true});
    const marker = new google.maps.Marker({position: initial, map, draggable: true, visible: Boolean(existing)});
    const geocoder = new google.maps.Geocoder();
    const setLocation = (lat, lng) => { lat = Number(Number(lat).toFixed(7)); lng = Number(Number(lng).toFixed(7)); latInput.value = lat; lngInput.value = lng; marker.setPosition({lat, lng}); marker.setVisible(true); map.panTo({lat, lng}); coordinates.textContent = `Selected location: ${lat}, ${lng}`; };
    map.addListener('click', event => setLocation(event.latLng.lat(), event.latLng.lng()));
    marker.addListener('dragend', event => setLocation(event.latLng.lat(), event.latLng.lng()));
    document.getElementById('sr-find-address').addEventListener('click', () => { const parts = ['service_address', 'city', 'state', 'pin_code'].map(name => document.querySelector(`[name="${name}"]`)?.value).filter(Boolean); if (!parts.length) return alert('Enter the address first.'); geocoder.geocode({address: parts.join(', ')}, (results, status) => { if (status === 'OK' && results[0]) { setLocation(results[0].geometry.location.lat(), results[0].geometry.location.lng()); map.setZoom(17); } else alert('Google Maps could not find this address. Place the pin manually.'); }); });
    document.getElementById('sr-current-location').addEventListener('click', () => { if (!navigator.geolocation) return alert('Location is not supported by this browser.'); navigator.geolocation.getCurrentPosition(position => { setLocation(position.coords.latitude, position.coords.longitude); map.setZoom(17); }, () => alert('Location permission was denied.')); });
    if (existing) coordinates.textContent = `Selected location: ${latInput.value}, ${lngInput.value}`;
    window.recenterServiceRequestMap = (lat, lng) => { if (!lat || !lng) return; setLocation(lat, lng); map.setZoom(16); };
};
</script>
@if(config('services.google_maps.key'))
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google_maps.key')) }}&callback=initServiceRequestMap"></script>
@else
<script>document.getElementById('sr-map').innerHTML='<div class="grid h-full place-items-center p-6 text-center text-sm text-amber-700">Google Maps API key is not configured. Add GOOGLE_MAPS_API_KEY to the server .env.</div>';</script>
@endif
@endpush
