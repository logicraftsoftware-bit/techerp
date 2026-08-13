<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineStockTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MachineInventoryController extends Controller
{
    public function index(): View
    {
        return view('machine-inventory.index', [
            'records' => MachineStockTransaction::with('machine')->latest()->paginate(20),
            'machines' => Machine::where('status', 'active')->orderBy('machine_name')->get(),
        ]);
    }

    public function transact(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'machine_id' => 'required|exists:machines,id',
            'transaction_type' => ['required', Rule::in(['stock_in', 'stock_out', 'adjustment_add', 'adjustment_remove'])],
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|max:100',
            'remarks' => 'nullable',
        ]);

        DB::transaction(function () use ($d, $r) {
            $machine = Machine::lockForUpdate()->findOrFail($d['machine_id']);
            $add = in_array($d['transaction_type'], ['stock_in', 'adjustment_add']);
            $balance = $add ? $machine->total_stock + $d['quantity'] : $machine->total_stock - $d['quantity'];
            abort_if($balance < 0, 422, 'Insufficient stock.');
            $machine->update(['total_stock' => $balance]);
            MachineStockTransaction::create([
                'machine_id' => $machine->id,
                'transaction_type' => $d['transaction_type'],
                'quantity' => $add ? $d['quantity'] : -$d['quantity'],
                'balance_after' => $balance,
                'reference' => $d['reference'] ?? null,
                'remarks' => $d['remarks'] ?? null,
                'created_by' => $r->user()->id,
            ]);
        });

        return back()->with('success', 'Machine stock updated.');
    }
}
