@extends('layouts.app', ['title' => $tagging->exists ? 'Edit Customer-AMC Tagging' : 'Add Customer-AMC Tagging'])

@section('content')
@php
    $collectorOptions = [['id' => 'staff', 'label' => 'Payment collected by Staff'], ['id' => 'technician', 'label' => 'Payment collected by Technician']];
    $paymentMethods = [['id' => 'cash', 'label' => 'Cash'], ['id' => 'upi', 'label' => 'UPI'], ['id' => 'card', 'label' => 'Card'], ['id' => 'bank_transfer', 'label' => 'Bank Transfer'], ['id' => 'cheque', 'label' => 'Cheque']];
@endphp
<form method="POST" action="{{ $tagging->exists ? route('customer-amc-taggings.update', $tagging) : route('customer-amc-taggings.store') }}" class="mx-auto max-w-4xl"
      x-data="amcTaggingForm(@js($plans), @js(['customerType' => old('customer_type', $tagging->customer_type ?? 'amc'), 'planId' => (string) old('amc_plan_id', $tagging->amc_plan_id), 'startDate' => old('start_date', $tagging->start_date?->format('Y-m-d')), 'paymentBy' => old('payment_collected_by', $tagging->payment_collected_by)]))"
      @searchable-select-changed.window="selectChanged($event.detail)">
    @csrf @if($tagging->exists) @method('PUT') @endif
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h2 class="text-2xl font-bold text-slate-900">{{ $tagging->exists ? 'Edit' : 'Add' }} Customer-AMC Tagging</h2><p class="text-sm text-slate-500">Select a customer, a machine and the customer coverage type.</p></div><button class="btn-primary">Save Tagging</button></div>
    <section class="card p-6"><div class="grid gap-5 md:grid-cols-2">
        <div><button type="button" class="btn-secondary float-right" @click="$dispatch('open-customer')">New +</button>
        @include('master._searchable_select', ['name' => 'customer_id', 'label' => 'Choose Customer', 'options' => $customers, 'labelKey' => 'label', 'selected' => $tagging->customer_id, 'placeholder' => 'Search or select customer', 'required' => true])</div>
        @include('master._searchable_select', ['name' => 'machine_id', 'label' => 'Choose Machine from Machine Master', 'options' => $machines, 'labelKey' => 'label', 'selected' => $tagging->machine_id, 'placeholder' => 'Search by machine name, code or model', 'required' => true])
        <div><label class="form-label">Customer Type *</label><select name="customer_type" x-model="customerType" class="form-input" required><option value="in_warranty">In Warranty</option><option value="amc">AMC</option><option value="out_of_warranty">Out Of Warranty</option></select></div>
        <div x-cloak x-show="customerType === 'amc'" class="contents"><fieldset :disabled="customerType !== 'amc'" class="contents">
        @include('master._searchable_select', ['name' => 'amc_plan_id', 'label' => 'Choose AMC Plan', 'options' => $plans, 'labelKey' => 'label', 'selected' => $tagging->amc_plan_id, 'placeholder' => 'Search or select AMC plan', 'required' => true])
        <div><label class="form-label">AMC Plan Price</label><input type="text" :value="formattedPlanPrice" class="form-input bg-slate-50 font-semibold" placeholder="Select an AMC plan" readonly></div>
        <div><label class="form-label">Number of Services *</label><input type="number" name="service_count" value="{{ old('service_count', $tagging->service_count) }}" min="0" max="999" step="1" class="form-input" placeholder="Enter number of services" required></div>
        <div><label class="form-label">AMC Start Date *</label><input type="date" name="start_date" x-model="startDate" class="form-input" required></div>
        <div><label class="form-label">AMC End Date *</label><input type="date" :value="endDate" class="form-input bg-slate-50" readonly><p class="mt-1 text-xs text-slate-400">Calculated automatically from the selected plan period.</p></div>
        </fieldset></div>
        @include('master._searchable_select', ['name' => 'payment_collected_by', 'label' => 'Payment Collection', 'options' => $collectorOptions, 'labelKey' => 'label', 'selected' => $tagging->payment_collected_by, 'placeholder' => 'Search or select collector', 'required' => true])

        <div x-cloak x-show="['staff', 'technician'].includes(paymentBy)" x-transition class="contents">
            <div><label class="form-label">Paid Amount</label><input type="number" name="paid_amount" value="{{ old('paid_amount', $tagging->paid_amount) }}" min="0" step="0.01" class="form-input" placeholder="Enter amount received"></div>
            @include('master._searchable_select', ['name' => 'payment_method', 'label' => 'Payment Method', 'options' => $paymentMethods, 'labelKey' => 'label', 'selected' => $tagging->payment_method, 'placeholder' => 'Search or select payment method', 'required' => false])
            <div class="md:col-span-2"><label class="form-label">Payment Remarks</label><textarea name="payment_remarks" rows="3" class="form-input" placeholder="Enter payment reference or remarks">{{ old('payment_remarks', $tagging->payment_remarks) }}</textarea></div>
        </div>
    </div></section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('customer-amc-taggings.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Tagging</button></div>
</form>
<dialog x-data @open-customer.window="$el.showModal()" @customer-created.window="$el.close()" @close-customer.window="$el.close()" class="w-full max-w-5xl rounded-2xl p-0 backdrop:bg-slate-950/50">
<div class="flex items-center justify-between border-b p-4"><h3 class="font-bold">New Customer</h3><button type="button" class="btn-secondary" @click="$el.closest('dialog').close()">Close</button></div>
<iframe src="{{ route('customers.create', ['embedded' => 1]) }}" title="Create customer" class="w-full border-0" style="height:75vh"></iframe>
</dialog>
@endsection

@push('scripts')
<script>
function amcTaggingForm(plans, initial) {
    return {
        customerType: initial.customerType || 'amc', plans, planId: initial.planId || '', startDate: initial.startDate || '', paymentBy: initial.paymentBy || '',
        selectChanged(detail) {
            if (detail.name === 'amc_plan_id') this.planId = detail.value;
            if (detail.name === 'payment_collected_by') this.paymentBy = detail.value;
        },
        get selectedPlan() { return this.plans.find(item => String(item.id) === String(this.planId)); },
        get formattedPlanPrice() { return this.selectedPlan ? `₹${Number(this.selectedPlan.price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : ''; },
        get endDate() {
            if (!this.startDate || !this.selectedPlan) return '';
            const years = { '1_year': 1, '2_years': 2, '3_years': 3 }[this.selectedPlan.duration];
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
