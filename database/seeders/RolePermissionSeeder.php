<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['dashboard', 'users', 'customers', 'machines', 'technicians', 'attendance', 'leave', 'inventory', 'service_requests', 'jobs', 'salary', 'expenses', 'amc', 'reports', 'settings'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::updateOrCreate(['slug' => "$module.$action"], ['name' => ucfirst($action).' '.str($module)->replace('_', ' ')->title(), 'module' => $module]);
            }
        }

        // One permission per real sidebar menu item (the granularity the per-user
        // Permission screen actually operates on), plus "users" which lives outside
        // config('crm.navigation') under the hand-written Administration section.
        $menuSlugs = collect(config('crm.navigation'))->flatten(1)->mapWithKeys(fn ($item) => [$item[0] => $item[1]])->put('users', 'Users');
        foreach ($menuSlugs as $slug => $label) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::updateOrCreate(['slug' => "$slug.$action"], ['name' => ucfirst($action).' '.$label, 'module' => $slug]);
            }
        }

        $roles = [
            'super-admin' => ['name' => 'Super Admin', 'modules' => $modules],
            'admin' => ['name' => 'Admin', 'modules' => array_diff($modules, ['settings'])],
            'manager' => ['name' => 'Manager', 'modules' => ['dashboard', 'customers', 'machines', 'technicians', 'attendance', 'leave', 'service_requests', 'jobs', 'expenses', 'amc', 'reports']],
            'hr' => ['name' => 'HR', 'modules' => ['dashboard', 'technicians', 'attendance', 'leave', 'salary', 'expenses', 'reports']],
            'store-manager' => ['name' => 'Store Manager', 'modules' => ['dashboard', 'inventory', 'jobs', 'reports']],
        ];
        foreach ($roles as $slug => $data) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $data['name'], 'description' => $data['name'].' access', 'is_system' => true, 'is_active' => true]);
            $role->permissions()->sync(Permission::whereIn('module', $data['modules'])->pluck('id'));
        }
    }
}
