@extends('layouts.app', ['title' => $assignment->exists ? 'Edit Work Assignment' : 'Assign Work'])
@section('content')
<form method="POST" action="{{ $assignment->exists ? route('assignments.update',$assignment) : route('assignments.store') }}" class="mx-auto max-w-6xl" x-data="assignmentForm(@js($requests),@js($technicians),@js(['requestId'=>old('service_request_id',$assignment->service_request_id),'technicianId'=>old('technician_id',$assignment->technician_id),'scheduledDate'=>old('scheduled_date',$assignment->scheduled_date?->format('Y-m-d')),'startTime'=>old('start_time',$assignment->start_time)]))" x-init="init()">@csrf @if($assignment->exists)@method('PUT')@endif
<div class="mb-6 flex justify-between"><div><h2 class="text-2xl font-bold">{{ $assignment->exists ? 'Edit' : 'Create' }} Work Assignment</h2><p class="text-sm text-slate-500">Choose the request, match a technician, and schedule the visit.</p></div><button class="btn-primary">Save Assignment</button></div>
<section class="card p-6"><h3 class="mb-5 font-bold">Service Request</h3><div class="grid gap-5 md:grid-cols-2">
<div class="relative md:col-span-2" @click.outside="requestOpen=false"><label class="form-label">Service Request *</label><input type="hidden" name="service_request_id" x-model="requestId"><input x-model="requestSearch" @focus="requestOpen=true" @input="requestId='';requestOpen=true" class="form-input" placeholder="Search request ID, customer, machine or subject" autocomplete="off"><div x-cloak x-show="requestOpen" class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border bg-white p-1 shadow-xl dark:border-slate-700 dark:bg-slate-800"><template x-for="request in filteredRequests" :key="request.id"><button type="button" @click="selectRequest(request)" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-blue-50 dark:hover:bg-slate-700"><span class="font-medium" x-text="`${request.request_code} — ${request.subject}`"></span><span class="block text-xs text-slate-400" x-text="`${request.customer.customer_name} · ${request.machine?.machine_name || request.product_name}`"></span></button></template></div></div>
<div><label class="form-label">Customer</label><input :value="selectedRequest?.customer?.customer_name || '—'" class="form-input bg-slate-50" readonly></div><div><label class="form-label">Machine</label><input :value="selectedRequest?.machine?.machine_name || selectedRequest?.product_name || '—'" class="form-input bg-slate-50" readonly></div><div><label class="form-label">Service Type</label><input :value="selectedRequest?.service_type?.replaceAll('_',' ') || '—'" class="form-input bg-slate-50 capitalize" readonly></div><div><label class="form-label">Customer Phone</label><input :value="selectedRequest?.contact_phone || '—'" class="form-input bg-slate-50" readonly></div></div></section>
<section class="card mt-6 p-6"><h3 class="mb-5 font-bold">Technician & Schedule</h3><div class="grid gap-5 md:grid-cols-2">
<div class="relative md:col-span-2" @click.outside="technicianOpen=false"><label class="form-label">Technician *</label><input type="hidden" name="technician_id" x-model="technicianId"><input x-model="technicianSearch" @focus="technicianOpen=true" @input="technicianId='';technicianOpen=true" class="form-input" placeholder="Search technician by name, employee ID, mobile or skill" autocomplete="off"><div x-cloak x-show="technicianOpen" class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border bg-white p-1 shadow-xl dark:border-slate-700 dark:bg-slate-800"><template x-for="technician in filteredTechnicians" :key="technician.id"><button type="button" @click="selectTechnician(technician)" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-blue-50 dark:hover:bg-slate-700"><span class="font-medium" x-text="`${technician.employee_code} — ${technician.name}`"></span><span class="block text-xs text-slate-400" x-text="technician.skills.map(skill=>skill.name).join(', ') || 'No skills mapped'"></span></button></template></div></div>
@include('master._field',['model'=>$assignment,'name'=>'assignment_role','label'=>'Assignment Role','type'=>'select','required'=>true,'options'=>['primary'=>'Primary Technician','support'=>'Support Technician','inspection'=>'Inspector']])
<div><label class="form-label">Preferred Date & Time</label><input :value="preferredVisit" class="form-input bg-slate-50" readonly></div>
<div><label class="form-label">Service Request Priority</label><input :value="selectedRequest?.priority || '—'" class="form-input bg-slate-50 capitalize" readonly></div>
<div><label class="form-label">Scheduled Date *</label><input type="date" name="scheduled_date" x-model="scheduledDate" class="form-input" required></div>
<div><label class="form-label">Start Time *</label><input type="time" name="start_time" x-model="startTime" class="form-input" required></div>
@include('master._field',['model'=>$assignment,'name'=>'end_time','label'=>'End Time','type'=>'time','required'=>true])
@include('master._field',['model'=>$assignment,'name'=>'status','label'=>'Status','type'=>'select','required'=>true,'options'=>['scheduled'=>'Scheduled','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled']])
</div></section>
<section class="card mt-6 p-6"><h3 class="mb-5 font-bold">Work Details</h3><div class="grid gap-5"><div><label class="form-label">Service Address *</label><textarea name="service_address" x-model="address" class="form-input" rows="2" required></textarea></div>@include('master._field',['model'=>$assignment,'name'=>'work_instructions','label'=>'Work Instructions','type'=>'textarea','wide'=>true])@include('master._field',['model'=>$assignment,'name'=>'internal_notes','label'=>'Internal Notes','type'=>'textarea','wide'=>true])</div></section>
<div class="mt-6 flex justify-end gap-3"><a href="{{ route('assignments.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Assignment</button></div></form>
@endsection
@push('scripts')
<script>
function assignmentForm(requests, technicians, initial) {
    return {
        requests, technicians,
        requestId: initial.requestId ? String(initial.requestId) : '', technicianId: initial.technicianId ? String(initial.technicianId) : '',
        scheduledDate: initial.scheduledDate || '', startTime: initial.startTime?.slice(0, 5) || '',
        requestSearch: '', technicianSearch: '', requestOpen: false, technicianOpen: false,
        address: @js(old('service_address', $assignment->service_address)),
        init() { const r=this.selectedRequest,t=this.selectedTechnician;if(r)this.requestSearch=`${r.request_code} — ${r.subject}`;if(t)this.technicianSearch=`${t.employee_code} — ${t.name}`; },
        get selectedRequest() { return this.requests.find(x => String(x.id) === this.requestId); },
        get selectedTechnician() { return this.technicians.find(x => String(x.id) === this.technicianId); },
        get preferredVisit() { const r=this.selectedRequest;if(!r)return '—';return `${r.preferred_date?.slice(0,10)||'No preferred date'}${r.preferred_time?' · '+r.preferred_time.slice(0,5):''}`; },
        get filteredRequests() { const q=this.requestSearch.toLowerCase();return this.requests.filter(x=>!q||`${x.request_code} ${x.subject} ${x.customer.customer_name} ${x.machine?.machine_name||x.product_name}`.toLowerCase().includes(q)); },
        get filteredTechnicians() { const q=this.technicianSearch.toLowerCase();return this.technicians.filter(x=>!q||`${x.employee_code} ${x.name} ${x.mobile} ${x.skills.map(s=>s.name).join(' ')}`.toLowerCase().includes(q)); },
        selectRequest(r) { this.requestId=String(r.id);this.requestSearch=`${r.request_code} — ${r.subject}`;this.address=`${r.service_address}, ${r.city}, ${r.state} ${r.pin_code}`;this.scheduledDate=r.preferred_date?.slice(0,10)||this.scheduledDate;this.startTime=r.preferred_time?.slice(0,5)||this.startTime;this.requestOpen=false; },
        selectTechnician(t) { this.technicianId=String(t.id);this.technicianSearch=`${t.employee_code} — ${t.name}`;this.technicianOpen=false; }
    };
}
</script>
@endpush
