<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\InventoryTransaction;
use App\Models\JobPart;
use App\Models\Part;
use App\Models\PartIssue;
use App\Models\PartRequest;
use App\Models\Supplier;
use App\Models\Technician;
use App\Models\WorkAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartsInventoryController extends Controller
{
    public function parts(): View
    {
        return view('parts-inventory.parts', ['records' => Part::latest()->paginate(15)]);
    }

    public function createPart(): View
    {
        return view('parts-inventory.parts-create', ['brands' => Brand::orderBy('brand_name')->get()]);
    }

    public function savePart(Request $r): RedirectResponse
    {
        $p = $r->validate(['id' => 'nullable|exists:parts,id', 'part_name' => 'required|max:150', 'category' => 'required|max:100', 'brand_id' => 'nullable|exists:brands,id', 'compatible_models' => 'nullable|max:255', 'unit' => 'required|max:30', 'purchase_price' => 'required|numeric|min:0', 'selling_price' => 'required|numeric|min:0', 'tax_percent' => 'required|numeric|min:0|max:100', 'minimum_stock' => 'required|integer|min:0', 'warranty_months' => 'required|integer|min:0', 'status' => ['required', Rule::in(['active', 'inactive'])]]);
        $id = $p['id'] ?? null;
        unset($p['id']);
        if ($id) {
            Part::findOrFail($id)->update($p);
        } else {
            $p['part_code'] = 'PT-'.str_pad((string) (Part::count() + 1), 5, '0', STR_PAD_LEFT);
            Part::create($p);
        }

        return to_route('parts.index')->with('success', 'Part saved.');
    }

    public function deletePart(Part $part): RedirectResponse
    {
        $part->delete();

        return back()->with('success', 'Part deleted.');
    }

    public function suppliers(): View
    {
        return view('parts-inventory.suppliers', ['records' => Supplier::latest()->paginate(15)]);
    }

    public function createSupplier(): View
    {
        return view('parts-inventory.suppliers-create');
    }

    public function saveSupplier(Request $r): RedirectResponse
    {
        $d = $r->validate(['id' => 'nullable|exists:suppliers,id', 'company_name' => 'required|max:150', 'contact_person' => 'nullable|max:100', 'mobile' => 'required|max:20', 'email' => 'nullable|email', 'gst_number' => 'nullable|max:30', 'pan_number' => 'nullable|max:20', 'address' => 'nullable', 'city' => 'nullable|max:100', 'state' => 'nullable|max:100', 'pin_code' => 'nullable|max:10', 'payment_terms_days' => 'required|integer|min:0', 'status' => ['required', Rule::in(['active', 'inactive'])]]);
        $id = $d['id'] ?? null;
        unset($d['id']);
        if ($id) {
            Supplier::findOrFail($id)->update($d);
        } else {
            $d['supplier_code'] = 'SUP-'.str_pad((string) (Supplier::count() + 1), 4, '0', STR_PAD_LEFT);
            Supplier::create($d);
        }

        return to_route('suppliers.index')->with('success', 'Supplier saved.');
    }

    public function deleteSupplier(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }

    public function inventory(): View
    {
        return view('parts-inventory.inventory', ['records' => InventoryTransaction::with(['part', 'supplier'])->latest()->paginate(20)]);
    }

    public function createTransaction(): View
    {
        return view('parts-inventory.inventory-create', ['parts' => Part::where('status', 'active')->get(), 'suppliers' => Supplier::where('status', 'active')->get()]);
    }

    public function transact(Request $r): RedirectResponse
    {
        $d = $r->validate(['part_id' => 'required|exists:parts,id', 'supplier_id' => 'nullable|exists:suppliers,id', 'transaction_type' => ['required', Rule::in(['stock_in', 'stock_out', 'adjustment_add', 'adjustment_remove'])], 'quantity' => 'required|integer|min:1', 'unit_cost' => 'nullable|numeric|min:0', 'warehouse' => 'nullable|max:100', 'reference' => 'nullable|max:100', 'remarks' => 'nullable']);
        DB::transaction(function () use ($d, $r) {
            $p = Part::lockForUpdate()->findOrFail($d['part_id']);
            $add = in_array($d['transaction_type'], ['stock_in', 'adjustment_add']);
            $balance = $add ? $p->current_stock + $d['quantity'] : $p->current_stock - $d['quantity'];
            abort_if($balance < 0, 422, 'Insufficient stock.');
            $p->update(['current_stock' => $balance]);
            InventoryTransaction::create($d + ['quantity' => $add ? $d['quantity'] : -$d['quantity'], 'balance_after' => $balance, 'created_by' => $r->user()->id]);
        });

        return to_route('inventory.index')->with('success', 'Inventory updated.');
    }

    public function issues(): View
    {
        return view('parts-inventory.issues', ['records' => PartIssue::with(['part', 'technician', 'assignment'])->latest()->paginate(15)]);
    }

    public function createIssue(): View
    {
        return view('parts-inventory.issues-create', ['parts' => Part::where('status', 'active')->get(), 'technicians' => Technician::where('status', 'active')->get(), 'assignments' => WorkAssignment::with('serviceRequest')->latest()->get()]);
    }

    public function issue(Request $r): RedirectResponse
    {
        $d = $r->validate(['part_id' => 'required|exists:parts,id', 'technician_id' => 'required|exists:technicians,id', 'work_assignment_id' => 'nullable|exists:work_assignments,id', 'issued_quantity' => 'required|integer|min:1', 'remarks' => 'nullable']);
        DB::transaction(function () use ($d) {
            $p = Part::lockForUpdate()->findOrFail($d['part_id']);
            abort_if($p->current_stock < $d['issued_quantity'], 422, 'Insufficient stock.');
            $p->decrement('current_stock', $d['issued_quantity']);
            PartIssue::create($d + ['issue_code' => 'PI-'.str_pad((string) (PartIssue::count() + 1), 5, '0', STR_PAD_LEFT)]);
        });

        return to_route('parts-issues.index')->with('success', 'Part issued.');
    }

    public function updateIssue(Request $r, PartIssue $partIssue): RedirectResponse
    {
        $d = $r->validate(['used_quantity' => 'required|integer|min:0', 'returned_quantity' => 'required|integer|min:0', 'damaged_quantity' => 'required|integer|min:0', 'remarks' => 'nullable']);
        abort_if(array_sum([$d['used_quantity'], $d['returned_quantity'], $d['damaged_quantity']]) > $partIssue->issued_quantity, 422, 'Used, returned and damaged quantities cannot exceed issued quantity.');
        DB::transaction(function () use ($d, $partIssue) {
            $partIssue->refresh();
            $returnDelta = $d['returned_quantity'] - $partIssue->returned_quantity;
            if ($returnDelta > 0) {
                Part::lockForUpdate()->findOrFail($partIssue->part_id)->increment('current_stock', $returnDelta);
            }$partIssue->update($d + ['status' => array_sum([$d['used_quantity'], $d['returned_quantity'], $d['damaged_quantity']]) === $partIssue->issued_quantity ? 'closed' : 'issued']);
        });

        return back()->with('success', 'Issue/return updated.');
    }

    public function jobParts(): View
    {
        return view('parts-inventory.job-parts', ['records' => JobPart::with(['part', 'assignment'])->latest()->paginate(15)]);
    }

    public function createJobPart(): View
    {
        return view('parts-inventory.job-parts-create', ['parts' => Part::where('status', 'active')->get(), 'assignments' => WorkAssignment::with('serviceRequest')->latest()->get()]);
    }

    public function usePart(Request $r): RedirectResponse
    {
        $d = $r->validate(['work_assignment_id' => 'required|exists:work_assignments,id', 'part_id' => 'required|exists:parts,id', 'quantity' => 'required|integer|min:1', 'rate' => 'required|numeric|min:0', 'tax_percent' => 'required|numeric|min:0|max:100', 'serial_number' => 'nullable|max:100', 'under_warranty' => 'nullable|boolean']);
        DB::transaction(function () use ($d) {
            $p = Part::lockForUpdate()->findOrFail($d['part_id']);
            abort_if($p->current_stock < $d['quantity'], 422, 'Insufficient stock.');
            $p->decrement('current_stock', $d['quantity']);
            JobPart::create($d);
        });

        return to_route('job-parts.index')->with('success', 'Job consumption saved.');
    }

    public function requests(): View
    {
        return view('parts-inventory.requests', ['records' => PartRequest::with(['part', 'technician', 'assignment'])->latest()->paginate(15)]);
    }

    public function createRequest(): View
    {
        return view('parts-inventory.requests-create', ['parts' => Part::where('status', 'active')->get(), 'technicians' => Technician::where('status', 'active')->get(), 'assignments' => WorkAssignment::latest()->get()]);
    }

    public function requestPart(Request $r): RedirectResponse
    {
        $d = $r->validate(['technician_id' => 'required|exists:technicians,id', 'work_assignment_id' => 'nullable|exists:work_assignments,id', 'part_id' => 'required|exists:parts,id', 'quantity' => 'required|integer|min:1', 'urgency' => ['required', Rule::in(['normal', 'high', 'urgent'])], 'reason' => 'nullable']);
        PartRequest::create($d + ['request_code' => 'PRQ-'.str_pad((string) (PartRequest::count() + 1), 5, '0', STR_PAD_LEFT)]);

        return to_route('parts-requests.index')->with('success', 'Part request created.');
    }

    public function actionRequest(Request $r, PartRequest $partRequest): RedirectResponse
    {
        $d = $r->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'issued'])], 'remarks' => 'nullable']);
        DB::transaction(function () use ($d, $partRequest, $r) {
            $partRequest->refresh();
            if ($d['status'] === 'issued' && $partRequest->status !== 'issued') {
                $part = Part::lockForUpdate()->findOrFail($partRequest->part_id);
                abort_if($part->current_stock < $partRequest->quantity, 422, 'Insufficient stock.');
                $part->decrement('current_stock', $partRequest->quantity);
                PartIssue::create(['issue_code' => 'PI-'.str_pad((string) (PartIssue::count() + 1), 5, '0', STR_PAD_LEFT), 'part_id' => $partRequest->part_id, 'technician_id' => $partRequest->technician_id, 'work_assignment_id' => $partRequest->work_assignment_id, 'issued_quantity' => $partRequest->quantity, 'remarks' => 'Issued from '.$partRequest->request_code]);
            }$partRequest->update($d + ['actioned_by' => $r->user()->id]);
        });

        return back()->with('success', 'Request updated.');
    }
}
