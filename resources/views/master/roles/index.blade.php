@extends('layouts.app', ['title' => 'Role Master'])

@section('content')
@include('master._index_header', ['heading' => 'Role Master', 'description' => 'Manage role names used when creating users.', 'create' => route('roles.create'), 'singular' => 'Role'])

<div class="card overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Role Name</th><th class="p-4 text-right">Actions</th></tr></thead>
        <tbody class="divide-y">
            @forelse($records as $role)
                <tr>
                    <td class="p-4 font-semibold text-slate-800">{{ $role->name }}@if($role->is_system)<span class="badge badge-info ml-2">System</span>@endif</td>
                    <td class="p-4">
                        @if($role->is_system)
                            <span class="text-xs text-slate-400">Locked</span>
                        @else
                            <div class="flex justify-end gap-2"><a class="table-action" href="{{ route('roles.edit', $role) }}">Edit</a><form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">@csrf @method('DELETE')<button class="table-action text-rose-600">Delete</button></form></div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" class="p-12 text-center text-slate-400">No roles found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
