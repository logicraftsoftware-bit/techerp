<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TechnicianLeave;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_apply_edit_and_withdraw_a_pending_leave_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('my-leave.index'))->assertOk();

        $this->post(route('my-leave.store'), ['leave_type' => 'casual', 'from_date' => '2026-08-10', 'to_date' => '2026-08-11', 'reason' => 'Personal work'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();
        $this->assertSame($user->id, $leave->user_id);
        $this->assertNull($leave->technician_id);
        $this->assertSame('pending', $leave->status);

        $this->get(route('my-leave.edit', $leave))->assertOk();
        $this->put(route('my-leave.update', $leave), ['leave_type' => 'sick', 'from_date' => '2026-08-10', 'to_date' => '2026-08-12', 'reason' => 'Feeling unwell'])->assertRedirect(route('my-leave.index'));
        $this->assertSame('sick', $leave->fresh()->leave_type);
        $this->assertSame(3, $leave->fresh()->total_days);

        $this->delete(route('my-leave.destroy', $leave))->assertRedirect(route('my-leave.index'));
        $this->assertDatabaseMissing('technician_leaves', ['id' => $leave->id]);
    }

    public function test_user_cannot_edit_or_delete_a_leave_request_once_actioned(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->actingAs($user)->post(route('my-leave.store'), ['leave_type' => 'casual', 'from_date' => '2026-08-10', 'to_date' => '2026-08-10', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();

        $this->actingAs($admin)->patch(route('leave.update', $leave), ['status' => 'approved'])->assertRedirect();

        $this->actingAs($user);
        $this->get(route('my-leave.edit', $leave))->assertStatus(422);
        $this->put(route('my-leave.update', $leave), ['leave_type' => 'sick', 'from_date' => '2026-08-10', 'to_date' => '2026-08-10', 'reason' => 'Changed'])->assertStatus(422);
        $this->delete(route('my-leave.destroy', $leave))->assertStatus(422);
        $this->assertDatabaseHas('technician_leaves', ['id' => $leave->id, 'leave_type' => 'casual']);
    }

    public function test_user_cannot_touch_another_users_leave_request(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $this->actingAs($owner)->post(route('my-leave.store'), ['leave_type' => 'casual', 'from_date' => '2026-08-10', 'to_date' => '2026-08-10', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();

        $this->actingAs($intruder);
        $this->get(route('my-leave.edit', $leave))->assertNotFound();
        $this->delete(route('my-leave.destroy', $leave))->assertNotFound();
    }

    public function test_super_admin_cannot_access_self_leave(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin);

        $this->get(route('my-leave.index'))->assertNotFound();
        $this->post(route('my-leave.store'))->assertNotFound();
    }

    public function test_my_leave_link_hidden_for_super_admin_but_shown_for_regular_users(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee('My Leave');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('My Leave');
    }
}
