<?php

namespace Tests\Feature;

use App\Models\User;
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
        $this->actingAs(User::factory()->create())
            ->get(route('machines.index'))
            ->assertOk()
            ->assertSee('x-data="{ open: true }"', false);
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
            'service-history',
        ], array_column(config('crm.navigation.Service Operations'), 0));
    }
}
