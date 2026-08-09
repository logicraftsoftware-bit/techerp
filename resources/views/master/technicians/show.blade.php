@extends('layouts.app', ['title' => $technician->name])

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        @if($technician->profile_photo)
            <img src="{{ route('technicians.photo', $technician) }}" alt="{{ $technician->name }}" class="rounded-2xl border border-slate-200 object-cover shadow-sm" style="width: 92px; height: 92px;">
        @else
            <div class="grid place-items-center rounded-2xl bg-blue-100 text-3xl font-bold text-blue-700" style="width: 92px; height: 92px;">{{ str($technician->name)->substr(0, 1)->upper() }}</div>
        @endif
        <div><p class="text-sm font-semibold text-blue-600">{{ $technician->employee_code }}</p><h2 class="text-3xl font-bold">{{ $technician->name }}</h2><p class="text-slate-500">{{ $technician->designation ?: 'Technician' }} · {{ str($technician->employment_type)->replace('_', ' ')->title() }}</p></div>
    </div>
    <a class="btn-primary" href="{{ route('technicians.edit', $technician) }}">Edit Technician</a>
</div>

<div class="grid gap-6 xl:grid-cols-2">
    <section class="card p-6">
        <h3 class="font-bold">Personal Information</h3>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            @foreach([
                'Employee ID' => $technician->employee_code,
                'Name' => $technician->name,
                'Gender' => str($technician->gender)->title(),
                'Date of Birth' => $technician->date_of_birth?->format('d M Y'),
                'Mobile Number' => $technician->mobile,
                'Emergency Contact Number' => $technician->emergency_contact,
                'Email' => $technician->email,
                'Address' => $technician->address,
                'City' => $technician->city,
                'State' => $technician->state,
                'Pincode' => $technician->pin_code,
            ] as $label => $value)
                <div class="{{ $label === 'Address' ? 'sm:col-span-2' : '' }}"><p class="detail-label">{{ $label }}</p><p class="detail-value">{{ $value ?: '—' }}</p></div>
            @endforeach
        </div>
    </section>

    <section class="card p-6">
        <h3 class="font-bold">Employment</h3>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            @foreach([
                'Joining Date' => $technician->joining_date?->format('d M Y'),
                'Department' => $technician->departmentMaster?->department_name ?? $technician->getAttribute('department'),
                'Designation' => $technician->designation,
                'Reporting Manager' => $technician->manager?->name ?? $technician->reportingUser?->name,
                'Manager Type' => $technician->manager ? 'Staff' : ($technician->reportingUser ? ($technician->reportingUser->roles->first()?->name ?? 'Admin / User') : null),
                'Employment Type' => str($technician->employment_type)->replace('_', ' ')->title(),
                'Status' => str($technician->status)->title(),
            ] as $label => $value)
                <div><p class="detail-label">{{ $label }}</p><p class="detail-value">{{ $value ?: '—' }}</p></div>
            @endforeach
        </div>
    </section>

    <section class="card p-6">
        <h3 class="font-bold">Salary Structure</h3>
        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            @foreach([
                'Salary Type' => str($technician->salary_type)->title(),
                'Monthly Salary' => '₹'.number_format((float) $technician->monthly_salary, 2),
                'Daily Salary' => '₹'.number_format((float) $technician->daily_salary, 2),
                'Overtime Rate (Daily)' => '₹'.number_format((float) $technician->overtime_rate, 2),
                'Travel Allowance (Daily)' => '₹'.number_format((float) $technician->travel_allowance, 2),
                'Food Allowance (Daily)' => '₹'.number_format((float) $technician->food_allowance, 2),
                'Other Allowance (Daily)' => '₹'.number_format((float) $technician->other_allowance, 2),
                'ESI' => '₹'.number_format((float) $technician->esi, 2),
                'PF' => '₹'.number_format((float) $technician->pf, 2),
            ] as $label => $value)
                <div><p class="detail-label">{{ $label }}</p><p class="detail-value">{{ $value }}</p></div>
            @endforeach
        </div>
    </section>

    <section class="card p-6">
        <h3 class="font-bold">Skills</h3>
        <div class="mt-4 flex flex-wrap gap-2">
            @forelse($technician->skills as $skill)
                <span class="badge badge-info">{{ $skill->name }}</span>
            @empty
                <p class="text-sm text-slate-400">No skills assigned.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
