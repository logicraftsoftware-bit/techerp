@extends('layouts.app', ['title' => 'User Profile']) @section('content')
<div class="mx-auto max-w-4xl">
    <div class="card overflow-hidden">
        <div class="h-28 bg-gradient-to-r from-blue-700 to-cyan-500"></div>
        <div class="px-6 pb-8">
            <div class="-mt-10 flex flex-col gap-4 sm:flex-row sm:items-end">
                <span class="grid size-20 place-items-center rounded-2xl border-4 border-white bg-slate-900 text-2xl font-bold text-white">{{ str($user->name)->substr(0,1) }}</span>
                <div class="pb-1"><h2 class="text-2xl font-bold text-slate-900">{{ $user->name }}</h2><p class="text-sm text-slate-500">{{ $user->email }} @if($user->employee_code)· {{ $user->employee_code }}@endif</p></div>
                <div class="sm:ml-auto"><span class="badge {{ $user->is_active ? 'badge-success':'badge-danger' }}">{{ $user->is_active?'Active':'Inactive' }}</span></div>
            </div>
            <div class="mt-8 grid gap-6 border-t border-slate-100 pt-7 sm:grid-cols-3">
                <div><p class="detail-label">Phone</p><p class="detail-value">{{ $user->phone ?: 'Not provided' }}</p></div>
                <div><p class="detail-label">Last login</p><p class="detail-value">{{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}</p></div>
                <div><p class="detail-label">Member since</p><p class="detail-value">{{ $user->created_at->format('d M Y') }}</p></div>
                <div><p class="detail-label">Department</p><p class="detail-value">{{ $user->departmentMaster?->department_name ?: 'Not assigned' }}</p></div>
                <div><p class="detail-label">Designation</p><p class="detail-value">{{ $user->designation ?: 'Not assigned' }}</p></div>
                <div><p class="detail-label">Reporting Manager</p><p class="detail-value">{{ $user->manager?->name ?: 'None' }}</p></div>
                <div><p class="detail-label">Joining Date</p><p class="detail-value">{{ $user->joining_date?->format('d M Y') ?: 'Not set' }}</p></div>
                <div><p class="detail-label">Employment Type</p><p class="detail-value">{{ $user->employment_type ? str($user->employment_type)->replace('_',' ')->title() : 'Not set' }}</p></div>
                <div><p class="detail-label">Employment Status</p><p class="detail-value">{{ $user->employment_status ? ucfirst($user->employment_status) : 'Not set' }}</p></div>
            </div>
            <div class="mt-8"><p class="detail-label">Roles</p><div class="mt-3 flex flex-wrap gap-2">@forelse($user->roles as $role)<span class="badge badge-info">{{ $role->name }}</span>@empty<span class="text-sm text-slate-400">No roles assigned.</span>@endforelse</div></div>
            <div class="mt-6">
                <div class="flex items-center justify-between"><p class="detail-label">Permissions</p>@can('update',$user)<a href="{{ route('users.permissions.edit',$user) }}" class="text-sm font-semibold text-blue-600">Manage permissions</a>@endcan</div>
                <div class="mt-3 flex flex-wrap gap-1">@forelse($user->permissions as $permission)<span class="badge bg-white text-slate-500 ring-1 ring-slate-200">{{ $permission->name }}</span>@empty<span class="text-sm text-slate-400">No permissions granted yet.</span>@endforelse</div>
            </div>
        </div>
    </div>
    <div class="mt-5 flex justify-end gap-3"><a href="{{ route('users.index') }}" class="btn-secondary">Back</a>@can('update',$user)<a href="{{ route('users.edit',$user) }}" class="btn-primary">Edit user</a>@endcan</div>
</div>
@endsection
