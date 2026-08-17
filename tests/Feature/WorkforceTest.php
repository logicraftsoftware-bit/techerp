<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\SalaryRecord;
use App\Models\Technician;
use App\Models\TechnicianLeave;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkforceTest extends TestCase
{
    use RefreshDatabase;

    private Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        $this->actingAs($user);
        $this->technician = Technician::create(['name' => 'Payroll Tech', 'mobile' => '9000000000', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly', 'monthly_salary' => 26000, 'overtime_rate' => 100, 'travel_allowance' => 500, 'food_allowance' => 500, 'pf' => 200]);
    }

    public function test_workforce_pages_and_connected_workflows(): void
    {
        foreach (['attendance.index', 'leave.index', 'salary.index', 'expenses.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $key = "technician:{$this->technician->id}";
        $this->post(route('attendance.store'), ['attendance_date' => '2026-08-03', 'attendance' => [$key => ['status' => 'present', 'check_in' => '09:00', 'check_out' => '18:00', 'overtime_hours' => 2]]])->assertRedirect();
        $this->assertDatabaseHas('attendances', ['technician_id' => $this->technician->id, 'attendance_status' => 'present']);
        $this->post(route('leave.store'), ['staff' => $key, 'leave_type' => 'casual', 'from_date' => '2026-08-04', 'to_date' => '2026-08-05', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();
        $this->patch(route('leave.update', $leave), ['status' => 'approved'])->assertRedirect();
        $this->assertDatabaseCount('attendances', 3);
        $this->post(route('salary.generate'), ['month' => '2026-08'])->assertRedirect();
        $salary = SalaryRecord::whereNotNull('technician_id')->firstOrFail();
        $this->assertGreaterThan(0, $salary->net_salary);
        $this->patch(route('salary.pay', $salary), ['payment_reference' => 'TXN-1'])->assertRedirect();
        $this->assertSame('paid', $salary->fresh()->status);
        $this->post(route('expenses.store'), ['staff' => $key, 'expense_date' => '2026-08-03', 'expense_type' => 'travel', 'amount' => 250, 'description' => 'Customer visit'])->assertRedirect();
        $expense = Expense::firstOrFail();
        $this->patch(route('expenses.update', $expense), ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $expense->fresh()->status);
    }

    public function test_crm_users_are_tracked_alongside_technicians_in_workforce_screens(): void
    {
        $staffUser = User::factory()->create(['name' => 'Office Manager', 'is_active' => true, 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'employment_status' => 'active', 'salary_type' => 'monthly', 'monthly_salary' => 40000, 'overtime_rate' => 0, 'travel_allowance' => 0, 'food_allowance' => 0, 'pf' => 0, 'esi' => 0]);
        $key = "user:{$staffUser->id}";

        $this->get(route('attendance.index'))->assertOk()->assertSee($staffUser->name);

        $this->post(route('attendance.store'), ['attendance_date' => '2026-08-03', 'attendance' => [$key => ['status' => 'present']]])->assertRedirect();
        $this->assertDatabaseHas('attendances', ['user_id' => $staffUser->id, 'technician_id' => null, 'attendance_status' => 'present']);

        $this->post(route('leave.store'), ['staff' => $key, 'leave_type' => 'casual', 'from_date' => '2026-08-04', 'to_date' => '2026-08-04', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::where('user_id', $staffUser->id)->firstOrFail();
        $this->assertNull($leave->technician_id);

        $this->post(route('salary.generate'), ['month' => '2026-08'])->assertRedirect();
        $userSalary = SalaryRecord::where('user_id', $staffUser->id)->firstOrFail();
        $this->assertNull($userSalary->technician_id);
        $this->assertGreaterThan(0, $userSalary->net_salary);

        $this->post(route('expenses.store'), ['staff' => $key, 'expense_date' => '2026-08-03', 'expense_type' => 'travel', 'amount' => 100, 'description' => 'Client visit'])->assertRedirect();
        $this->assertDatabaseHas('expenses', ['user_id' => $staffUser->id, 'technician_id' => null]);

        // The technician-generated salary record from setUp() still coexists independently.
        $this->assertDatabaseHas('salary_records', ['technician_id' => $this->technician->id]);
    }

    public function test_super_admin_is_excluded_from_workforce_staff_lists(): void
    {
        $anotherSuperAdmin = User::factory()->create(['name' => 'Root Account']);
        $anotherSuperAdmin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->get(route('attendance.index'))->assertOk()->assertDontSee('Root Account');
    }

    public function test_admin_can_edit_and_delete_leave_requests(): void
    {
        $key = "technician:{$this->technician->id}";
        $this->post(route('leave.store'), ['staff' => $key, 'leave_type' => 'casual', 'from_date' => '2026-08-04', 'to_date' => '2026-08-05', 'reason' => 'Personal'])->assertRedirect();
        $leave = TechnicianLeave::firstOrFail();
        $this->patch(route('leave.update', $leave), ['status' => 'approved'])->assertRedirect();
        $this->assertDatabaseCount('attendances', 2);

        $this->get(route('leave.edit', $leave))->assertOk();
        $this->put(route('leave.update-details', $leave), ['staff' => $key, 'leave_type' => 'sick', 'from_date' => '2026-08-04', 'to_date' => '2026-08-06', 'reason' => 'Updated reason'])->assertRedirect(route('leave.index'));
        $leave->refresh();
        $this->assertSame('sick', $leave->leave_type);
        $this->assertSame(3, $leave->total_days);
        $this->assertDatabaseCount('attendances', 3);

        $this->delete(route('leave.destroy', $leave))->assertRedirect();
        $this->assertDatabaseMissing('technician_leaves', ['id' => $leave->id]);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_holiday_master_add_delete_and_calendar_rendering(): void
    {
        $this->post(route('holidays.store'), ['holiday_date' => '2026-08-15', 'name' => 'Independence Day'])->assertRedirect();
        $holiday = Holiday::firstOrFail();
        $this->get(route('holidays.index', ['month' => '2026-08']))->assertOk()->assertSee('Independence Day');

        $this->delete(route('holidays.destroy', $holiday))->assertRedirect();
        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_holidays_are_excluded_from_payroll_working_days(): void
    {
        $month = Carbon::parse('2026-08-01');
        $baselineWorkingDays = collect(range(1, $month->daysInMonth))->map(fn ($d) => $month->copy()->day($d))->filter(fn ($d) => ! $d->isSunday())->count();

        Holiday::create(['holiday_date' => '2026-08-15', 'name' => 'Independence Day']);

        $this->post(route('salary.generate'), ['month' => '2026-08'])->assertRedirect();
        $salary = SalaryRecord::whereNotNull('technician_id')->firstOrFail();
        $this->assertSame($baselineWorkingDays - 1, $salary->working_days);
    }
}
