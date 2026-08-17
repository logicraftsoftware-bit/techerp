@extends('layouts.app', ['title' => 'Add Inventory Transaction'])
@section('content')
<form method="POST" action="{{ route('inventory.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Add Inventory Transaction</h2><p class="mt-1 text-sm text-slate-500">Stock in, stock out or an adjustment with running balance.</p></div>
        <button class="btn-primary">Save Transaction</button>
    </div>
    <section class="card p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Part *</label><select name="part_id" class="form-input" required><option value="">Select part</option>@foreach($parts as $x)<option value="{{ $x->id }}" @selected(old('part_id') == $x->id)>{{ $x->part_code }} — {{ $x->part_name }} ({{ $x->current_stock }})</option>@endforeach</select></div>
            <div><label class="form-label">Transaction Type *</label><select name="transaction_type" class="form-input"><option value="stock_in" @selected(old('transaction_type', 'stock_in') === 'stock_in')>Stock In</option><option value="stock_out" @selected(old('transaction_type') === 'stock_out')>Stock Out</option><option value="adjustment_add" @selected(old('transaction_type') === 'adjustment_add')>Adjustment Add</option><option value="adjustment_remove" @selected(old('transaction_type') === 'adjustment_remove')>Adjustment Remove</option></select></div>
            <div><label class="form-label">Quantity *</label><input type="number" name="quantity" min="1" value="{{ old('quantity') }}" class="form-input" required></div>
            <div><label class="form-label">Unit Cost</label><input type="number" step=".01" name="unit_cost" value="{{ old('unit_cost', 0) }}" class="form-input"></div>
            <div><label class="form-label">Warehouse</label><input name="warehouse" value="{{ old('warehouse') }}" class="form-input"></div>
            <div><label class="form-label">Invoice / Reference</label><input name="reference" value="{{ old('reference') }}" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Remarks</label><textarea name="remarks" rows="3" class="form-input">{{ old('remarks') }}</textarea></div>
        </div>
    </section>
    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('inventory.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Transaction</button></div>
</form>
@endsection
