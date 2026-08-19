<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkMissedCheckInsCommandTest extends TestCase
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

    public function test_it_does_not_mark_within_the_grace_window(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        Carbon::setTestNow('2026-08-19 11:15:00');

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_it_marks_leave_when_paid_leave_quota_remains(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        Carbon::setTestNow('2026-08-19 11:31:00');

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'attendance_status' => 'leave']);

        // Running again (next minute) must not duplicate or change the record.
        Carbon::setTestNow('2026-08-19 11:32:00');
        $this->artisan('attendance:mark-missed-checkins');
        $this->assertSame(1, Attendance::where('user_id', $user->id)->count());
    }

    public function test_it_marks_absent_once_paid_leave_quota_is_exhausted(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        Attendance::create(['user_id' => $user->id, 'attendance_date' => '2026-08-01', 'attendance_status' => 'leave']);
        Carbon::setTestNow('2026-08-19 11:31:00');

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'attendance_status' => 'absent']);
    }

    public function test_it_skips_sundays(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        Carbon::setTestNow('2026-08-16 11:31:00'); // Sunday

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_it_skips_holidays(): void
    {
        Holiday::create(['holiday_date' => '2026-08-19', 'name' => 'Test Holiday']);
        $user = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        Carbon::setTestNow('2026-08-19 11:31:00');

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_it_skips_staff_who_already_have_a_record_today(): void
    {
        $user = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        Attendance::create(['user_id' => $user->id, 'attendance_date' => '2026-08-19', 'attendance_status' => 'present', 'check_in' => '10:55']);
        Carbon::setTestNow('2026-08-19 11:31:00');

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'attendance_status' => 'present']);
        $this->assertSame(1, Attendance::where('user_id', $user->id)->count());
    }

    public function test_it_covers_technicians_too(): void
    {
        $technician = Technician::create(['name' => 'Grace Tech', 'mobile' => '9222222222', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly', 'expected_check_in_time' => '09:00', 'monthly_paid_leave_days' => 0]);
        Carbon::setTestNow('2026-08-19 09:31:00');

        $this->artisan('attendance:mark-missed-checkins');

        $this->assertDatabaseHas('attendances', ['technician_id' => $technician->id, 'attendance_status' => 'absent']);
    }
}
