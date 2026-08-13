@csrf @if($user->exists) @method('PUT') @endif
<div class="mb-6 flex justify-between"><div></div><button class="btn-primary">{{ $user->exists ? 'Save changes' : 'Create user' }}</button></div>

<section class="card p-6">
    <h3 class="mb-5 font-bold">Account &amp; Access</h3>
    <div class="grid gap-5 md:grid-cols-2">
        <div><label class="form-label">Full name *</label><input name="name" class="form-input" value="{{ old('name',$user->name) }}" required></div>
        <div><label class="form-label">Email *</label><input name="email" type="email" class="form-input" value="{{ old('email',$user->email) }}" required></div>
        <div><label class="form-label">Phone</label><input name="phone" class="form-input" value="{{ old('phone',$user->phone) }}"></div>
        <div><label class="form-label">Account status</label><label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$user->exists ? $user->is_active : true)) class="rounded"> Active and able to sign in</label></div>
        <div><label class="form-label">Password {{ $user->exists ? '(leave blank to keep current)' : '*' }}</label><input name="password" type="password" class="form-input" {{ $user->exists ? '' : 'required' }}></div>
        <div><label class="form-label">Confirm password</label><input name="password_confirmation" type="password" class="form-input"></div>
    </div>
    <div class="mt-7"><label class="form-label">Assigned role *</label><select name="roles[]" class="form-input" required><option value="">Select role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected(in_array($role->id, old('roles',$user->roles->pluck('id')->all())))>{{ $role->name }}@if($role->description) — {{ $role->description }}@endif</option>@endforeach</select></div>
</section>

<section class="card mt-6 p-6">
    <h3 class="mb-5 font-bold">Personal Information</h3>
    <div class="grid gap-5 md:grid-cols-2">
        <div><label class="form-label">Employee ID</label><input class="form-input bg-slate-50" value="{{ $user->employee_code ?: 'Auto-generated after saving' }}" readonly><p class="mt-1 text-xs text-slate-400">First two letters of name + six random digits</p></div>
        <div><label class="form-label">Photo</label><input type="file" name="avatar" class="form-input" accept="image/*"><p class="mt-1 text-xs text-slate-400">Any image format, maximum 4 MB.</p></div>
        @include('master._field', ['model' => $user, 'name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => true, 'options' => ['' => 'Select', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other']])
        @include('master._field', ['model' => $user, 'name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'])
        @include('master._field', ['model' => $user, 'name' => 'emergency_contact', 'label' => 'Emergency Contact Number'])
        @include('master._field', ['model' => $user, 'name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'wide' => true])
        @include('master._field', ['model' => $user, 'name' => 'city', 'label' => 'City'])
        @include('master._field', ['model' => $user, 'name' => 'state', 'label' => 'State'])
        @include('master._field', ['model' => $user, 'name' => 'pin_code', 'label' => 'Pincode'])
    </div>
</section>

<section class="card mt-6 p-6">
    <h3 class="mb-5 font-bold">Employment</h3>
    <div class="grid gap-5 md:grid-cols-2">
        @include('master._field', ['model' => $user, 'name' => 'joining_date', 'label' => 'Joining Date', 'type' => 'date', 'required' => true])
        <div><label class="form-label">Department</label><select class="form-input" name="department_id"><option value="">Select department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>{{ $department->department_name }}</option>@endforeach</select></div>
        @include('master._field', ['model' => $user, 'name' => 'designation', 'label' => 'Designation'])
        <div><label class="form-label">Reporting Manager</label><select class="form-input" name="reporting_manager_id"><option value="">None</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected(old('reporting_manager_id', $user->reporting_manager_id) == $manager->id)>{{ $manager->name }} ({{ $manager->roles->first()?->name ?? 'User' }})</option>@endforeach</select></div>
        @include('master._field', ['model' => $user, 'name' => 'employment_type', 'label' => 'Employment Type', 'type' => 'select', 'required' => true, 'options' => ['' => 'Select', 'full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract']])
        @include('master._field', ['model' => $user, 'name' => 'employment_status', 'label' => 'Employment Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'terminated' => 'Terminated']])
    </div>
</section>

<section class="card mt-6 p-6" x-data="{ salaryType: @js(old('salary_type', $user->salary_type ?: 'monthly')) }">
    <h3 class="mb-5 font-bold">Salary Structure</h3>
    <div class="grid gap-5 md:grid-cols-3">
        <div><label class="form-label">Salary Type *</label><select class="form-input" name="salary_type" x-model="salaryType" required><option value="monthly">Monthly</option><option value="daily">Daily</option></select></div>
        <div x-show="salaryType === 'monthly'" x-cloak><label class="form-label">Monthly Salary *</label><input class="form-input" type="number" step="0.01" min="0" name="monthly_salary" value="{{ old('monthly_salary', $user->monthly_salary) }}" :required="salaryType === 'monthly'"></div>
        <div x-show="salaryType === 'daily'" x-cloak><label class="form-label">Daily Salary *</label><input class="form-input" type="number" step="0.01" min="0" name="daily_salary" value="{{ old('daily_salary', $user->daily_salary) }}" :required="salaryType === 'daily'"></div>
        @foreach(['overtime_rate' => 'Overtime Rate (Daily)', 'travel_allowance' => 'Travel Allowance (Daily)', 'food_allowance' => 'Food Allowance (Daily)', 'other_allowance' => 'Other Allowance (Daily)', 'esi' => 'ESI', 'pf' => 'PF'] as $name => $label)
            @include('master._field', ['model' => $user, 'name' => $name, 'label' => $label, 'type' => 'number'])
        @endforeach
    </div>
</section>

<div class="mt-6 flex justify-end gap-3"><a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">{{ $user->exists ? 'Save changes' : 'Create user' }}</button></div>
