<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerAmcTaggingRequest;
use App\Models\AmcPlan;
use App\Models\Customer;
use App\Models\CustomerAmcTagging;
use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerAmcTaggingController extends Controller
{
    public function index(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        abort_unless($request->user()->hasRole('super-admin', 'admin') || $request->user()->hasPermission('customer-amc-taggings.view'), 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'customer_type' => ['nullable', 'in:in_warranty,amc,out_of_warranty'],
            'area' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', ...($request->filled('from_date') ? ['after_or_equal:from_date'] : [])],
            'export' => ['nullable', 'in:csv,pdf'],
        ]);
        $query = CustomerAmcTagging::with(['customer', 'machine', 'amcPlan'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('customer', fn ($q) => $q->where('customer_name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"))
                        ->orWhereHas('machine', fn ($q) => $q->where('machine_name', 'like', "%{$search}%")->orWhere('machine_code', 'like', "%{$search}%"))
                        ->orWhereHas('amcPlan', fn ($q) => $q->where('plan_name', 'like', "%{$search}%")->orWhere('plan_code', 'like', "%{$search}%"));
                });
            })
            ->when($filters['customer_type'] ?? null, fn ($q, $value) => $q->where('customer_type', $value))
            ->when($filters['area'] ?? null, fn ($q, $value) => $q->whereHas('customer', fn ($c) => $c->where('city', $value)))
            ->when($filters['model'] ?? null, fn ($q, $value) => $q->whereHas('machine', fn ($m) => $m->where('model', $value)))
            ->when($filters['machine_id'] ?? null, fn ($q, $value) => $q->where('machine_id', $value))
            ->when($filters['from_date'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to_date'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->orderByDesc('created_at')->orderByDesc('id');

        if (($filters['export'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['Customer', 'Customer Code', 'Mobile', 'Coverage Type', 'Area (City)', 'Machine', 'Model', 'AMC Plan', 'Plan Price', 'Services', 'Collection By', 'Paid Amount', 'Payment Method', 'Remarks', 'Start Date', 'End Date', 'Created Date']);
                foreach ($query->lazy(500) as $record) {
                    $row = [$record->customer->customer_name, $record->customer->customer_code, $record->customer->mobile,
                        $record->customer_type === 'amc' ? 'AMC' : ($record->customer_type === 'in_warranty' ? 'In Warranty' : 'Out Of Warranty'),
                        $record->customer->city, $record->machine->machine_name, $record->machine->model,
                        $record->amcPlan?->plan_name, $record->amcPlan?->price, $record->service_count,
                        $record->payment_collected_by, $record->paid_amount, $record->payment_method, $record->payment_remarks,
                        $record->start_date?->toDateString(), $record->end_date?->toDateString(), $record->created_at->toDateString()];
                    fputcsv($file, array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/', $value) ? "'".$value : $value, $row));
                }
                fclose($file);
            }, 'customer-taggings.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        if (($filters['export'] ?? null) === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('customer-amc-taggings.export', ['records' => $query->get(), 'filters' => $filters])
                ->setPaper('a4', 'landscape')->download('customer-taggings.pdf');
        }
        $records = $query->with(['serviceRequests.workAssignments.technician', 'serviceRequests.workAssignments.statusHistories', 'serviceRequests.workAssignments.jobParts'])->paginate(15)->withQueryString();
        $areas = Customer::whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city');
        $models = Machine::whereNotNull('model')->where('model', '!=', '')->distinct()->orderBy('model')->pluck('model');
        $machines = Machine::orderBy('machine_name')->get(['id', 'machine_name', 'machine_code']);
        return view('customer-amc-taggings.index', compact('records', 'areas', 'models', 'machines'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return $this->form(new CustomerAmcTagging);
    }

    public function store(CustomerAmcTaggingRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['created_by'] = $request->user()->id;
        CustomerAmcTagging::create($data);

        return to_route('customer-amc-taggings.index')->with('success', 'Customer AMC tagged successfully.');
    }

    public function edit(CustomerAmcTagging $customerAmcTagging): View
    {
        $this->authorizeAdmin();

        return $this->form($customerAmcTagging);
    }

    public function update(CustomerAmcTaggingRequest $request, CustomerAmcTagging $customerAmcTagging): RedirectResponse
    {
        $customerAmcTagging->update($this->payload($request));

        return to_route('customer-amc-taggings.index')->with('success', 'Customer AMC tagging updated.');
    }

    public function destroy(CustomerAmcTagging $customerAmcTagging): RedirectResponse
    {
        $this->authorizeAdmin();
        if ($customerAmcTagging->serviceRequests()->exists()) {
            return back()->withErrors(['tagging' => 'This AMC tagging cannot be deleted because it has service requests.']);
        }
        $customerAmcTagging->delete();

        return back()->with('success', 'Customer AMC tagging deleted.');
    }

    private function form(CustomerAmcTagging $customerAmcTagging): View
    {
        return view('customer-amc-taggings.form', [
            'tagging' => $customerAmcTagging,
            'customers' => Customer::orderBy('customer_name')->get(['id', 'customer_code', 'customer_name', 'mobile'])->map(fn ($customer) => ['id' => $customer->id, 'label' => "{$customer->customer_name} ({$customer->customer_code}) · {$customer->mobile}"]),
            'machines' => Machine::where('status', 'active')->orderBy('machine_name')->get(['id', 'machine_code', 'machine_name', 'model'])->map(fn ($machine) => ['id' => $machine->id, 'label' => "{$machine->machine_name} ({$machine->machine_code})".($machine->model ? " · {$machine->model}" : '')]),
            'plans' => AmcPlan::where('status', 'active')->orderBy('plan_name')->get(['id', 'plan_code', 'plan_name', 'duration', 'price'])->map(fn ($plan) => ['id' => $plan->id, 'label' => "{$plan->plan_name} ({$plan->plan_code}) · ".str($plan->duration)->replace('_', ' ')->title(), 'duration' => $plan->duration, 'price' => (float) $plan->price]),
        ]);
    }

    private function payload(CustomerAmcTaggingRequest $request): array
    {
        $data = $request->validated();
        if ($data['customer_type'] === 'amc') {
            $plan = AmcPlan::findOrFail($data['amc_plan_id']);
            $data['end_date'] = CustomerAmcTagging::calculateEndDate(Carbon::parse($data['start_date']), $plan->duration)->toDateString();
        } else {
            $data = array_merge($data, ['amc_plan_id' => null, 'service_count' => 0, 'start_date' => null, 'end_date' => null]);
        }

        return $data;
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('super-admin', 'admin'), 403);
    }
}
