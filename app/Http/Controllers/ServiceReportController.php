<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:service-reports,view')->only(['index', 'show']);
    }

    public function index(Request $request): View
    {
        $records = ServiceRequest::with(['customer', 'machine'])->withCount('workAssignments')->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('request_code', 'like', "%{$s}%")->orWhereHas('customer', fn ($c) => $c->where('customer_name', 'like', "%{$s}%"))))->latest()->paginate(15)->withQueryString();

        return view('service-reports.index', compact('records'));
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        return view('service-reports.show', ['serviceRequest' => $serviceRequest->load(['customer', 'machine', 'creator', 'amcPlans', 'workAssignments.technician', 'workAssignments.statusHistories.changedBy'])]);
    }
}
