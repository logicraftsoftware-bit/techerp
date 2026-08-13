<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_check_in_and_check_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('my-attendance.show'))->assertOk()->assertSee('Not checked in');

        $this->post(route('my-attendance.check-in'))->assertRedirect();
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'technician_id' => null, 'attendance_status' => 'present']);

        $this->post(route('my-attendance.check-in'))->assertStatus(422);

        $this->post(route('my-attendance.check-out'))->assertRedirect();
        $this->assertNotNull(Attendance::where('user_id', $user->id)->first()->check_out);

        $this->post(route('my-attendance.check-out'))->assertStatus(422);
    }

    public function test_check_out_before_check_in_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('my-attendance.check-out'))->assertStatus(422);
    }

    public function test_super_admin_cannot_access_self_attendance(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin);

        $this->get(route('my-attendance.show'))->assertNotFound();
        $this->post(route('my-attendance.check-in'))->assertNotFound();
    }

    public function test_self_check_in_shows_up_on_the_admin_attendance_page(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $admin->permissions()->attach(Permission::where('slug', 'attendance.view')->firstOrFail());

        $staffUser = User::factory()->create(['name' => 'Self Service Staff']);
        $this->actingAs($staffUser)->post(route('my-attendance.check-in'))->assertRedirect();

        $this->actingAs($admin)->get(route('attendance.index'))->assertOk()->assertSee('Self Service Staff');
    }

    public function test_my_attendance_link_hidden_for_super_admin_but_shown_for_regular_users(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee('My Attendance');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('My Attendance');
    }
}
