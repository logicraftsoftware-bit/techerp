@extends('layouts.app', ['title' => $tagging->exists ? 'Edit Customer-AMC Tagging' : 'Add Customer-AMC Tagging'])

@section('content')
<form method="POST" action="{{ $tagging->exists ? route('customer-amc-taggings.update', $tagging) : route('customer-amc-taggings.store') }}" class="mx-auto max-w-4xl"
      x-data="amcTaggingForm(@js($machines), @js($plans), @js(['customerId' => (string) old('customer_id', $tagging->customer_id), 'machineId' => (string) old('machine_id', $tagging->machine_id), 'planId' => (string) old('amc_plan_id', $tagging->amc_plan_id), 'startDate' => old('start_date', $tagging->start_date?->format('Y-m-d'))]))">
    @csrf @if($tagging->exists) @method('PUT') @endif
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h2 class="text-2xl font-bold text-slate-900">{{ $tagging->exists ? 'Edit' : 'Add' }} Customer-AMC Tagging</h2><p class="text-sm text-slate-500">Select a customer, machine and plan. The end date follows the plan duration.</p></div><button class="btn-primary">Save Tagging</button></div>
    <section class="card p-6"><div class="grid gap-5 md:grid-cols-2">
        <div><label class="form-label">Choose Customer *</label><select name="customer_id" x-model="customerId" @change="machineId = ''" class="form-input" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->customer_name }} ({{ $customer->customer_code }})</option>@endforeach</select></div>
        <div><label class="form-label">Choose Machine *</label><select name="machine_id" x-model="machineId" class="form-input" required :disabled="!customerId"><option value="">Select machine</option><template x-for="machine in customerMachines" :key="machine.id"><option :value="String(machine.id)" x-text="`${machine.machine_name} (${machine.machine_code})${machine.model ? ' · '+machine.model : ''}`"></option></template></select><p x-show="customerId && !customerMachines.length" class="mt-1 text-xs text-amber-600">This customer has no machines.</p></div>
        <div><label class="form-label">Choose AMC Plan *</label><select name="amc_plan_id" x-model="planId" class="form-input" required><option value="">Select AMC plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->plan_name }} ({{ $plan->plan_code }}) — {{ str($plan->duration)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
        <div><label class="form-label">AMC Start Date *</label><input type="date" name="start_date" x-model="startDate" class="form-input" required></div>
        <div><label class="form-label">AMC End Date *</label><input type="date" :value="endDate" class="form-input bg-slate-50" readonly><p class="mt-1 text-xs text-slate-400">Calculated automatically from the selected plan period.</p></div>
    </div></section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('customer-amc-taggings.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Tagging</button></div>
</form>
@endsection

@push('scripts')
<script>
function amcTaggingForm(machines, plans, initial) {
    return {
        machines, plans,
        customerId: initial.customerId || '', machineId: initial.machineId || '', planId: initial.planId || '', startDate: initial.startDate || '',
        get customerMachines() { return this.machines.filter(machine => String(machine.customer_id) === String(this.customerId)); },
        get endDate() {
            if (!this.startDate || !this.planId) return '';
            const plan = this.plans.find(item => String(item.id) === String(this.planId));
            const years = { '1_year': 1, '2_years': 2, '3_years': 3 }[plan?.duration];
            if (!years) return '';
            const parts = this.startDate.split('-').map(Number);
            const date = new Date(Date.UTC(parts[0] + years, parts[1] - 1, parts[2]));
            if (date.getUTCMonth() !== parts[1] - 1) date.setUTCDate(0);
            date.setUTCDate(date.getUTCDate() - 1);
            return date.toISOString().slice(0, 10);
        }
    };
}
</script>
@endpush
