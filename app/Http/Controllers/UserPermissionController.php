<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    public function edit(User $user): View
    {
        abort_if($user->hasRole('super-admin'), 404);
        $this->authorize('update', $user);

        $menuGroups = config('crm.navigation') + ['Administration' => [['users', 'Users', 'CRM accounts, roles and account status']]];
        $granted = $user->permissions->pluck('slug')->all();

        return view('users.permissions', ['user' => $user, 'menuGroups' => $menuGroups, 'granted' => $granted, 'actions' => self::ACTIONS]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('super-admin'), 404);
        $this->authorize('update', $user);

        $slugs = [];
        foreach ((array) $request->input('permissions', []) as $slug => $actions) {
            foreach (array_keys((array) $actions) as $action) {
                if (in_array($action, self::ACTIONS, true)) {
                    $slugs[] = "$slug.$action";
                }
            }
        }

        $user->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));

        return to_route('users.index')->with('success', 'Permissions updated.');
    }
}
