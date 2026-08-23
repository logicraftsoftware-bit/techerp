<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Technician;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100002',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    public function test_technician_can_check_in_and_check_out(): void
    {
        $technician = $this->technician();
        Sanctum::actingAs($technician);

        $this->getJson('/api/attendance/today')->assertOk()->assertJsonPath('data', null);

        $this->postJson('/api/attendance/check-in')->assertOk()->assertJsonPath('data.status', 'present');
        $this->assertDatabaseHas('attendances', ['technician_id' => $technician->id, 'attendance_status' => 'present']);

        $this->postJson('/api/attendance/check-in')->assertStatus(422);

        $this->postJson('/api/attendance/check-out')->assertOk();
        $this->assertNotNull(Attendance::where('technician_id', $technician->id)->first()->check_out);
    }

    public function test_check_out_before_check_in_is_rejected(): void
    {
        Sanctum::actingAs($this->technician());

        $this->postJson('/api/attendance/check-out')->assertStatus(422);
    }

    public function test_check_in_is_blocked_after_the_grace_period(): void
    {
        $technician = $this->technician(['mobile' => '9111100003', 'expected_check_in_time' => '11:00']);
        Sanctum::actingAs($technician);
        Carbon::setTestNow('2026-08-19 11:31:00');

        $this->postJson('/api/attendance/check-in')->assertStatus(422);
        $this->assertDatabaseMissing('attendances', ['technician_id' => $technician->id, 'attendance_status' => 'present']);
    }

    public function test_attendance_history_scoped_to_the_month(): void
    {
        $technician = $this->technician(['mobile' => '9111100004']);
        Attendance::create(['technician_id' => $technician->id, 'attendance_date' => '2026-08-05', 'attendance_status' => 'present']);
        Attendance::create(['technician_id' => $technician->id, 'attendance_date' => '2026-07-05', 'attendance_status' => 'present']);
        Sanctum::actingAs($technician);

        $response = $this->getJson('/api/attendance/history?month=2026-08')->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
