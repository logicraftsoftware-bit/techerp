<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $records = Role::query()
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.roles.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.roles.form', ['role' => new Role]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        Role::create($request->validated());

        return to_route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        abort_if($role->is_system, 404);

        return view('master.roles.form', compact('role'));
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 422, 'System roles cannot be modified.');
        $role->update($request->validated());

        return to_route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 422, 'System roles cannot be modified.');
        $role->delete();

        return back()->with('success', 'Role deleted.');
    }
}
