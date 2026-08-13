<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_before_later_phase_tables_exist(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Work overview');
    }

    public function test_authenticated_user_can_open_every_crm_module_menu(): void
    {
        $user = User::factory()->create();

        foreach (collect(config('crm.navigation'))->flatten(1) as $module) {
            $this->actingAs($user)->get(route('modules.show', $module[0]))
                ->assertOk()
                ->assertSee($module[1]);
        }
    }

    public function test_active_resource_keeps_parent_sidebar_group_open(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->actingAs($user)
            ->get(route('machines.index'))
            ->assertOk()
            ->assertSee('x-data="{ open: true }"', false);
    }

    public function test_sidebar_only_shows_menus_the_user_has_view_permission_for(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('slug', 'customers.view')->firstOrFail());

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('customers.index').'"', false)
            ->assertDontSee('href="'.route('machines.index').'"', false)
            ->assertDontSee('Parts & Inventory')
            ->assertDontSee('href="'.route('users.index').'"', false);
    }

    public function test_route_access_is_blocked_without_the_matching_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('customers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('customers.create'))->assertForbidden();

        $user->permissions()->attach(Permission::where('slug', 'customers.view')->firstOrFail());
        $user->load('permissions');
        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $this->actingAs($user)->get(route('customers.create'))->assertForbidden();
    }

    public function test_sidebar_groups_are_in_operational_order(): void
    {
        $this->assertSame([
            'Master Data',
            'Parts & Inventory',
            'Service Operations',
            'Maintenance',
            'Workforce',
            'Insights & Control',
        ], array_keys(config('crm.navigation')));
    }

    public function test_service_operations_menu_is_in_workflow_order(): void
    {
        $this->assertSame([
            'service-requests',
            'assignments',
            'job-cards',
            'work-status',
            'service-reports',
        ], array_column(config('crm.navigation.Service Operations'), 0));
    }
}
