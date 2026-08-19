<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
use App\Services\MissedCheckInMarker;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissedCheckInAutoMarkingTest extends TestCase
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

    public function test_visiting_any_authenticated_page_triggers_missed_checkin_marking_without_a_cron_job(): void
    {
        $late = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        Carbon::setTestNow('2026-08-19 11:31:00');

        // No scheduler/cron ever ran -- an ordinary page visit by any user is what triggers marking.
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $this->assertDatabaseHas('attendances', ['user_id' => $late->id, 'attendance_status' => 'leave']);
    }

    public function test_marking_is_throttled_to_once_per_minute(): void
    {
        $late = User::factory()->create(['expected_check_in_time' => '11:00', 'monthly_paid_leave_days' => 1]);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        Carbon::setTestNow('2026-08-19 11:31:00');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->assertSame(1, Attendance::where('user_id', $late->id)->count());

        // Delete the auto-marked row and hit another page in the same minute bucket -- the
        // throttle should prevent a second sweep from re-creating it.
        Attendance::where('user_id', $late->id)->delete();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->assertSame(0, Attendance::where('user_id', $late->id)->count());
    }

    public function test_a_marker_failure_never_takes_down_the_page(): void
    {
        // e.g. a pending migration leaves a column the marker queries missing -- the page
        // itself (dashboard, service requests, anything in the authenticated group) must
        // still render rather than surfacing as a site-wide 500.
        $this->mock(MissedCheckInMarker::class, function ($mock) {
            $mock->shouldReceive('run')->once()->andThrow(new \RuntimeException('simulated schema drift'));
        });

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }
}
