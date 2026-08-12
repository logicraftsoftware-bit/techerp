<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestRequest;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
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
        ServiceRequest::create($this->requestData($request) + ['created_by' => $request->user()->id]);

        return to_route('service-requests.index')->with('success', 'Service request created.');
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        return view('service-requests.show', ['serviceRequest' => $serviceRequest->load(['customer', 'machine', 'machineCategory', 'brand', 'creator'])]);
    }

    public function edit(ServiceRequest $serviceRequest): View
    {
        return $this->form($serviceRequest);
    }

    public function update(ServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $serviceRequest->update($this->requestData($request));

        return to_route('service-requests.index')->with('success', 'Service request updated.');
    }

    public function destroy(ServiceRequest $serviceRequest): RedirectResponse
    {
        $serviceRequest->delete();

        return back()->with('success', 'Service request deleted.');
    }

    private function form(ServiceRequest $serviceRequest): View
    {
        return view('service-requests.form', [
            'serviceRequest' => $serviceRequest,
            'customers' => Customer::where('status', 'active')->orderBy('customer_name')->get(),
            'machines' => Machine::with(['brandMaster', 'machineCategory'])->where('status', 'active')->orderBy('machine_name')->get(),
            'categories' => MachineCategory::orderBy('category_name')->get(),
            'brands' => Brand::orderBy('brand_name')->get(),
        ]);
    }

    private function requestData(ServiceRequestRequest $request): array
    {
        $data = $request->validated();

        if ($data['request_type'] === 'existing_service') {
            $machine = Machine::findOrFail($data['machine_id']);
            if ((int) $machine->customer_id !== (int) $data['customer_id']) {
                throw ValidationException::withMessages(['machine_id' => 'The selected machine does not belong to this customer.']);
            }
            $data['machine_category_id'] = null;
            $data['brand_id'] = null;
            $data['product_name'] = null;
            $data['model'] = null;
            $data['serial_number'] = null;
        } else {
            $data['service_type'] = 'installation';
            $data['machine_id'] = null;
        }

        return $data;
    }
}
