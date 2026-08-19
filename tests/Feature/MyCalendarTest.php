<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\TechnicianLeave;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_check_in_and_check_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('my-calendar.index'))->assertOk()->assertSee('Not checked in');

        $this->post(route('my-calendar.check-in'))->assertRedirect();
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'technician_id' => null, 'attendance_status' => 'present']);

        $this->post(route('my-calendar.check-in'))->assertStatus(422);

        $this->post(route('my-calendar.check-out'))->assertRedirect();
        $this->assertNotNull(Attendance::where('user_id', $user->id)->first()->check_out);

        $this->post(route('my-calendar.check-out'))->assertStatus(422);
    }

    public function test_check_out_before_check_in_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('my-calendar.check-out'))->assertStatus(422);
    }

    public function test_check_in_is_blocked_after_the_grace_period(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00']);
        Carbon::setTestNow('2026-08-19 11:31:00');

        $this->actingAs($user)->post(route('my-calendar.check-in'))->assertStatus(422);
        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_check_in_is_allowed_within_the_grace_period(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00']);
        Carbon::setTestNow('2026-08-19 11:29:00');

        $this->actingAs($user)->post(route('my-calendar.check-in'))->assertRedirect();
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'attendance_status' => 'present']);
    }

    public function test_super_admin_cannot_access_my_calendar(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin);

        $this->get(route('my-calendar.index'))->assertNotFound();
        $this->post(route('my-calendar.check-in'))->assertNotFound();
    }

    public function test_my_calendar_link_hidden_for_super_admin_but_shown_for_regular_users(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee('My Calendar');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('My Calendar');
    }

    public function test_user_can_apply_edit_and_withdraw_a_pending_leave_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('my-calendar.leave.store'), ['leave_type' => 'casual', 'from_date' => '2026-08-25', 'to_date' => '2026-08-26', 'reason' => 'Personal work'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();
        $this->assertSame($user->id, $leave->user_id);
        $this->assertSame('pending', $leave->status);

        $this->get(route('my-calendar.leave.edit', $leave))->assertOk();
        $this->put(route('my-calendar.leave.update', $leave), ['leave_type' => 'sick', 'from_date' => '2026-08-25', 'to_date' => '2026-08-27', 'reason' => 'Feeling unwell'])->assertRedirect(route('my-calendar.index'));
        $this->assertSame('sick', $leave->fresh()->leave_type);
        $this->assertSame(3, $leave->fresh()->total_days);

        $this->delete(route('my-calendar.leave.destroy', $leave))->assertRedirect(route('my-calendar.index'));
        $this->assertDatabaseMissing('technician_leaves', ['id' => $leave->id]);
    }

    public function test_leave_apply_rejects_a_non_future_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('my-calendar.leave.store'), ['leave_type' => 'casual', 'from_date' => today()->format('Y-m-d'), 'to_date' => today()->format('Y-m-d'), 'reason' => 'Personal'])
            ->assertSessionHasErrors('from_date');
    }

    public function test_user_cannot_edit_or_delete_a_leave_request_once_actioned(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->actingAs($user)->post(route('my-calendar.leave.store'), ['leave_type' => 'casual', 'from_date' => '2026-08-25', 'to_date' => '2026-08-25', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();

        $this->actingAs($admin)->patch(route('leave.update', $leave), ['status' => 'approved'])->assertRedirect();

        $this->actingAs($user);
        $this->get(route('my-calendar.leave.edit', $leave))->assertStatus(422);
        $this->delete(route('my-calendar.leave.destroy', $leave))->assertStatus(422);
    }

    public function test_user_cannot_touch_another_users_leave_request(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $this->actingAs($owner)->post(route('my-calendar.leave.store'), ['leave_type' => 'casual', 'from_date' => '2026-08-25', 'to_date' => '2026-08-25', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();

        $this->actingAs($intruder);
        $this->get(route('my-calendar.leave.edit', $leave))->assertNotFound();
        $this->delete(route('my-calendar.leave.destroy', $leave))->assertNotFound();
    }

    public function test_calendar_shows_holiday_and_attendance_colors(): void
    {
        $user = User::factory()->create();
        Holiday::create(['holiday_date' => '2026-08-15', 'name' => 'Independence Day']);
        Attendance::create(['user_id' => $user->id, 'attendance_date' => '2026-08-10', 'attendance_status' => 'present', 'check_in' => '09:00']);
        Attendance::create(['user_id' => $user->id, 'attendance_date' => '2026-08-11', 'attendance_status' => 'absent']);
        Attendance::create(['user_id' => $user->id, 'attendance_date' => '2026-08-12', 'attendance_status' => 'leave']);

        $response = $this->actingAs($user)->get(route('my-calendar.index', ['month' => '2026-08']));

        $response->assertOk()
            ->assertSee('Independence Day')
            ->assertSee('bg-emerald-50', false)
            ->assertSee('bg-orange-50', false)
            ->assertSee('bg-amber-50', false)
            ->assertSee('bg-rose-50', false);
    }
}
