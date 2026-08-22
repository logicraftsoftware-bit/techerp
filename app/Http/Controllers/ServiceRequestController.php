<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestRequest;
use App\Models\AmcPlan;
use App\Models\Customer;
use App\Models\CustomerAmcTagging;
use App\Models\Machine;
use App\Models\MachineStockTransaction;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;
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

    public function create(Request $request): View
    {
        $this->authorizeCreation($request);

        if ($request->filled('amc_tagging')) {
            $tagging = CustomerAmcTagging::with(['customer', 'machine.machineCategory', 'machine.brandMaster', 'amcPlan'])
                ->findOrFail($request->integer('amc_tagging'));
            $serviceNumber = $request->integer('service_number');
            abort_unless($serviceNumber >= 1 && $serviceNumber <= $tagging->service_count, 404);
            abort_if($tagging->serviceRequests()->where('amc_service_number', $serviceNumber)->exists(), 409, 'A service request already exists for this AMC service slot.');

            return view('service-requests.amc-form', compact('tagging', 'serviceNumber'));
        }

        return $this->form(new ServiceRequest);
    }

    public function store(ServiceRequestRequest $request): RedirectResponse
    {
        $this->authorizeCreation($request);

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

        return $request->filled('customer_amc_tagging_id')
            ? to_route('customer-amc-taggings.index')->with('success', 'AMC service request created.')
            : to_route('service-requests.index')->with('success', 'Service request created.');
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        return view('service-requests.show', ['serviceRequest' => $serviceRequest->load(['customer', 'machine', 'machineCategory', 'brand', 'creator', 'amcPlans', 'referredByTechnician', 'referredByUser'])]);
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
            'technicians' => Technician::where('status', 'active')->orderBy('name')->get(),
            'referrerUsers' => User::where('is_active', true)->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'super-admin'))->orderBy('name')->get(),
        ]);
    }

    private function requestData(ServiceRequestRequest $request): array
    {
        $data = $request->safe()->except('amc_plan_ids', 'referred_by');
        if (! empty($data['customer_amc_tagging_id'])) {
            $tagging = CustomerAmcTagging::with(['customer', 'machine'])->findOrFail($data['customer_amc_tagging_id']);
            $data['customer_id'] = $tagging->customer_id;
            $data['machine_id'] = $tagging->machine_id;
            $data['request_type'] = 'existing_service';
            $data['service_type'] = 'amc';
            $data['contact_phone'] = $tagging->customer->mobile;
            $data['serial_number'] = $tagging->machine->serial_number;
            $data['asset_number'] = $tagging->machine->asset_number;
            $data['city'] = $tagging->customer->city;
            $data['state'] = $tagging->customer->state;
            $data['pin_code'] = $tagging->customer->pin_code;
        }
        $machine = Machine::with(['machineCategory', 'brandMaster'])->findOrFail($data['machine_id']);

        if ($data['request_type'] !== 'existing_service') {
            $data['service_type'] = 'installation';
            if (! empty($data['purchase_date']) && $request->validated('amc_plan_ids')) {
                $plan = AmcPlan::findOrFail((int) $request->validated('amc_plan_ids')[0]);
                $data['amc_start_date'] = $data['purchase_date'];
                $data['amc_end_date'] = CustomerAmcTagging::calculateEndDate(Carbon::parse($data['purchase_date']), $plan->duration)->toDateString();
            }
            if (($data['payment_collected_by'] ?? null) === 'technician') {
                $data['paid_amount'] = null;
                $data['payment_method'] = null;
                $data['payment_remarks'] = null;
            }
        }

        $data['machine_category_id'] = $machine->machine_category_id;
        $data['brand_id'] = $machine->brand_id;
        $data['product_name'] = $machine->machine_name;
        $data['model'] = $machine->model;

        [$data['referred_by_technician_id'], $data['referred_by_user_id']] = $this->splitReferrer($request->validated('referred_by'));

        return $data;
    }

    private function splitReferrer(?string $referredBy): array
    {
        if (! $referredBy || ! preg_match('/^(technician|user):(\d+)$/', $referredBy, $matches)) {
            return [null, null];
        }

        return [$matches[1] === 'technician' ? (int) $matches[2] : null, $matches[1] === 'user' ? (int) $matches[2] : null];
    }

    private function authorizeCreation(Request $request): void
    {
        abort_unless($request->user()->hasRole('super-admin', 'admin') || $request->user()->hasPermission('service-requests.create'), 403);
    }
}
