<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestRequest;
use App\Models\AmcPlan;
use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachineStockTransaction;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:service-requests,view')->only(['index', 'show']);
        $this->middleware('permission:service-requests,create')->only(['create', 'store']);
        $this->middleware('permission:service-requests,update')->only(['edit', 'update']);
        $this->middleware('permission:service-requests,delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $records = ServiceRequest::with(['customer', 'machine'])
            ->when($request->search, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('request_code', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('customer_name', 'like', "%{$search}%"))))
            ->when($request->request_type, fn ($query, $type) => $query->where('request_type', $type))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('service-requests.index', compact('records'));
    }

    public function create(): View
    {
        return $this->form(new ServiceRequest);
    }

    public function store(ServiceRequestRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $this->requestData($request);
            $machine = null;

            if ($data['request_type'] === 'new_installation') {
                $machine = Machine::lockForUpdate()->findOrFail($data['machine_id']);
                if ($machine->total_stock < 1) {
                    throw ValidationException::withMessages(['machine_id' => 'Selected machine is out of stock.']);
                }
            }

            $serviceRequest = ServiceRequest::create($data + ['created_by' => $request->user()->id]);
            $serviceRequest->amcPlans()->sync($request->validated('amc_plan_ids', []));

            if ($machine) {
                $machine->decrement('total_stock');
                MachineStockTransaction::create([
                    'machine_id' => $machine->id,
                    'service_request_id' => $serviceRequest->id,
                    'transaction_type' => 'installation',
                    'quantity' => -1,
                    'balance_after' => $machine->total_stock,
                    'reference' => $serviceRequest->request_code,
                    'remarks' => 'Deducted for new installation service request.',
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return to_route('service-requests.index')->with('success', 'Service request created.');
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        return view('service-requests.show', ['serviceRequest' => $serviceRequest->load(['customer', 'machine', 'machineCategory', 'brand', 'creator', 'amcPlans'])]);
    }

    public function edit(ServiceRequest $serviceRequest): View
    {
        return $this->form($serviceRequest);
    }

    public function update(ServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceRequest): void {
            $serviceRequest->update($this->requestData($request));
            $serviceRequest->amcPlans()->sync($request->validated('amc_plan_ids', []));
        });

        return to_route('service-requests.index')->with('success', 'Service request updated.');
    }

    public function destroy(ServiceRequest $serviceRequest): RedirectResponse
    {
        $serviceRequest->delete();

        return back()->with('success', 'Service request deleted.');
    }

    private function form(ServiceRequest $serviceRequest): View
    {
        $serviceRequest->loadMissing('amcPlans');

        return view('service-requests.form', [
            'serviceRequest' => $serviceRequest,
            'customers' => Customer::where('status', 'active')->orderBy('customer_name')->get(),
            'machines' => Machine::with(['brandMaster', 'machineCategory'])->where('status', 'active')->orderBy('machine_name')->get(),
            'amcPlans' => AmcPlan::with(['machineCategory', 'brandMaster'])->where('status', 'active')->orderBy('plan_name')->get(),
        ]);
    }

    private function requestData(ServiceRequestRequest $request): array
    {
        $data = $request->safe()->except('amc_plan_ids');
        $machine = Machine::with(['machineCategory', 'brandMaster'])->findOrFail($data['machine_id']);

        if ($data['request_type'] !== 'existing_service') {
            $data['service_type'] = 'installation';
        }

        $data['machine_category_id'] = $machine->machine_category_id;
        $data['brand_id'] = $machine->brand_id;
        $data['product_name'] = $machine->machine_name;
        $data['model'] = $machine->model;

        return $data;
    }
}
