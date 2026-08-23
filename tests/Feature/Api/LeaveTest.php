<?php

namespace Tests\Feature\Api;

use App\Models\Technician;
use App\Models\TechnicianLeave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100010',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    public function test_technician_can_apply_edit_and_withdraw_a_pending_leave(): void
    {
        $technician = $this->technician();
        Sanctum::actingAs($technician);

        $this->postJson('/api/leaves', ['leave_type' => 'casual', 'from_date' => '2026-08-25', 'to_date' => '2026-08-26', 'reason' => 'Personal'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
        $leave = TechnicianLeave::firstOrFail();
        $this->assertSame($technician->id, $leave->technician_id);

        $this->putJson("/api/leaves/{$leave->id}", ['leave_type' => 'sick', 'from_date' => '2026-08-25', 'to_date' => '2026-08-27', 'reason' => 'Unwell'])
            ->assertOk()->assertJsonPath('data.leave_type', 'sick');
        $this->assertSame(3, $leave->fresh()->total_days);

        $this->deleteJson("/api/leaves/{$leave->id}")->assertOk();
        $this->assertDatabaseMissing('technician_leaves', ['id' => $leave->id]);
    }

    public function test_leave_apply_rejects_a_non_future_date(): void
    {
        Sanctum::actingAs($this->technician());

        $this->postJson('/api/leaves', ['leave_type' => 'casual', 'from_date' => today()->format('Y-m-d'), 'to_date' => today()->format('Y-m-d'), 'reason' => 'Personal'])
            ->assertStatus(422);
    }

    public function test_technician_cannot_edit_a_leave_once_actioned(): void
    {
        $technician = $this->technician();
        $leave = TechnicianLeave::create(['technician_id' => $technician->id, 'leave_type' => 'casual', 'from_date' => '2026-08-25', 'to_date' => '2026-08-25', 'total_days' => 1, 'reason' => 'x', 'status' => 'approved', 'leave_code' => 'LV-TEST-0001']);
        Sanctum::actingAs($technician);

        $this->putJson("/api/leaves/{$leave->id}", ['leave_type' => 'sick', 'from_date' => '2026-08-25', 'to_date' => '2026-08-25', 'reason' => 'x'])->assertStatus(422);
        $this->deleteJson("/api/leaves/{$leave->id}")->assertStatus(422);
    }

    public function test_technician_cannot_touch_another_technicians_leave(): void
    {
        $owner = $this->technician(['mobile' => '9111100011']);
        $intruder = $this->technician(['mobile' => '9111100012']);
        $leave = TechnicianLeave::create(['technician_id' => $owner->id, 'leave_type' => 'casual', 'from_date' => '2026-08-25', 'to_date' => '2026-08-25', 'total_days' => 1, 'reason' => 'x', 'status' => 'pending', 'leave_code' => 'LV-TEST-0002']);

        Sanctum::actingAs($intruder);
        $this->putJson("/api/leaves/{$leave->id}", ['leave_type' => 'sick', 'from_date' => '2026-08-25', 'to_date' => '2026-08-25', 'reason' => 'x'])->assertStatus(404);
        $this->deleteJson("/api/leaves/{$leave->id}")->assertStatus(404);
    }
}
