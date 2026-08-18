<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfHolidayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_regular_user_can_view_holidays_read_only(): void
    {
        Holiday::create(['holiday_date' => '2026-08-15', 'name' => 'Independence Day']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('my-holidays.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Independence Day')
            ->assertDontSee('Add Holiday');

        $this->post(route('holidays.store'), ['holiday_date' => '2026-08-20', 'name' => 'Blocked'])->assertForbidden();
        $this->assertDatabaseMissing('holidays', ['name' => 'Blocked']);
    }

    public function test_super_admin_cannot_access_self_holidays(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->actingAs($admin)->get(route('my-holidays.index'))->assertNotFound();
    }

    public function test_holidays_link_hidden_for_super_admin_but_shown_for_regular_users(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee('Holidays');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Holidays');
    }
}
