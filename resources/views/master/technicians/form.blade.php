@extends('layouts.app', ['title' => $technician->exists ? 'Edit Technician' : 'Add Technician'])

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $technician->exists ? route('technicians.update', $technician) : route('technicians.store') }}" class="mx-auto max-w-6xl" x-data="{ salaryType: @js(old('salary_type', $technician->salary_type ?: 'monthly')) }">
    @csrf
    @if($technician->exists) @method('PUT') @endif
    <div class="mb-6 flex justify-between"><div><h2 class="text-2xl font-bold">{{ $technician->exists ? 'Edit' : 'Add' }} Technician</h2><p class="text-sm text-slate-500">Personal, employment, salary and skill details.</p></div><button class="btn-primary">Save Technician</button></div>

    <section class="card p-6">
        <h3 class="mb-5 font-bold">Personal Information</h3>
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label">Employee ID</label><input class="form-input bg-slate-50" value="{{ $technician->employee_code ?: 'Auto-generated after saving' }}" readonly><p class="mt-1 text-xs text-slate-400">First two letters of name + six random digits</p></div>
            @include('master._field', ['model' => $technician, 'name' => 'name', 'label' => 'Name', 'required' => true])
            <div><label class="form-label">Photo</label><input type="file" name="profile_photo" class="form-input" accept="image/*"><p class="mt-1 text-xs text-slate-400">Any image format, maximum 4 MB.</p></div>
            @include('master._field', ['model' => $technician, 'name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => true, 'options' => ['' => 'Select', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other']])
            @include('master._field', ['model' => $technician, 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'])
            @include('master._field', ['model' => $technician, 'name' => 'mobile', 'label' => 'Mobile Number', 'required' => true])
            @include('master._field', ['model' => $technician, 'name' => 'emergency_contact', 'label' => 'Emergency Contact Number'])
            @include('master._field', ['model' => $technician, 'name' => 'email', 'label' => 'Email', 'type' => 'email'])
            @include('master._field', ['model' => $technician, 'name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'wide' => true])
            @include('master._field', ['model' => $technician, 'name' => 'city', 'label' => 'City'])
            @include('master._field', ['model' => $technician, 'name' => 'state', 'label' => 'State'])
            @include('master._field', ['model' => $technician, 'name' => 'pin_code', 'label' => 'Pincode'])
            <div><label class="form-label">Password {{ $technician->exists ? '' : '*' }}</label><input type="password" name="password" class="form-input" minlength="8" {{ $technician->exists ? '' : 'required' }} autocomplete="new-password"><p class="mt-1 text-xs text-slate-400">{{ $technician->exists ? 'Leave blank to keep the current password.' : 'Minimum 8 characters.' }}</p></div>
        </div>
    </section>

    <section class="card mt-6 p-6">
        <h3 class="mb-5 font-bold">Employment</h3>
        <div class="grid gap-5 md:grid-cols-2">
            @include('master._field', ['model' => $technician, 'name' => 'joining_date', 'label' => 'Joining Date', 'type' => 'date', 'required' => true])
            <div><label class="form-label">Department</label><select class="form-input" name="department_id"><option value="">Select department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id', $technician->department_id) == $department->id)>{{ $department->department_name }}</option>@endforeach</select></div>
            @include('master._field', ['model' => $technician, 'name' => 'designation', 'label' => 'Designation'])
            @php $selectedManager = old('reporting_manager', $technician->reporting_manager_id ? 'technician:'.$technician->reporting_manager_id : ($technician->reporting_user_id ? 'user:'.$technician->reporting_user_id : '')); @endphp
            <div><label class="form-label">Reporting Manager</label><select class="form-input" name="reporting_manager"><option value="">None</option><optgroup label="Staff">@foreach($managers as $manager)<option value="technician:{{ $manager->id }}" @selected($selectedManager === 'technician:'.$manager->id)>{{ $manager->name }} ({{ $manager->employee_code }})</option>@endforeach</optgroup><optgroup label="Admin / Users">@foreach($adminUsers as $user)<option value="user:{{ $user->id }}" @selected($selectedManager === 'user:'.$user->id)>{{ $user->name }} ({{ $user->roles->first()?->name ?? 'User' }})</option>@endforeach</optgroup></select></div>
            @include('master._field', ['model' => $technician, 'name' => 'employment_type', 'label' => 'Employment Type', 'type' => 'select', 'required' => true, 'options' => ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract']])
            @include('master._field', ['model' => $technician, 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'terminated' => 'Terminated']])
        </div>
    </section>

    <section class="card mt-6 p-6">
        <h3 class="mb-5 font-bold">Salary Structure</h3>
        <div class="grid gap-5 md:grid-cols-3">
            <div><label class="form-label">Salary Type *</label><select class="form-input" name="salary_type" x-model="salaryType" required><option value="monthly">Monthly</option><option value="daily">Daily</option></select></div>
            <div x-show="salaryType === 'monthly'" x-cloak><label class="form-label">Monthly Salary *</label><input class="form-input" type="number" step="0.01" min="0" name="monthly_salary" value="{{ old('monthly_salary', $technician->monthly_salary) }}" :required="salaryType === 'monthly'"></div>
            <div x-show="salaryType === 'daily'" x-cloak><label class="form-label">Daily Salary *</label><input class="form-input" type="number" step="0.01" min="0" name="daily_salary" value="{{ old('daily_salary', $technician->daily_salary) }}" :required="salaryType === 'daily'"></div>
            @foreach(['overtime_rate' => 'Overtime Rate (Daily)', 'travel_allowance' => 'Travel Allowance (Daily)', 'food_allowance' => 'Food Allowance (Daily)', 'other_allowance' => 'Other Allowance (Daily)', 'esi' => 'ESI', 'pf' => 'PF'] as $name => $label)
                @include('master._field', ['model' => $technician, 'name' => $name, 'label' => $label, 'type' => 'number'])
            @endforeach
        </div>
    </section>

    <section class="card mt-6 p-6">
        <div class="mb-4 flex items-center justify-between"><h3 class="font-bold">Skills</h3><a href="{{ route('skills.create') }}" class="text-sm font-semibold text-blue-600">+ Add Skill</a></div>
        <div class="grid gap-3 sm:grid-cols-3">
            @forelse($skills as $skill)
                <label class="rounded-xl border p-3 text-sm"><input type="checkbox" name="skills[]" value="{{ $skill->id }}" @checked(in_array($skill->id, old('skills', $technician->skills->pluck('id')->all())))><span class="ml-2 font-medium">{{ $skill->name }}</span></label>
            @empty
                <p class="text-sm text-slate-400">No active skills available.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-6 flex justify-end gap-3"><a href="{{ route('technicians.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Technician</button></div>
</form>
@endsection
