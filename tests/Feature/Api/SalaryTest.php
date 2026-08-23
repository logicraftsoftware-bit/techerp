<?php

namespace Tests\Feature\Api;

use App\Models\SalaryRecord;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalaryTest extends TestCase
{
    use RefreshDatabase;

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100020',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    public function test_technician_can_list_and_view_own_salary_records(): void
    {
        $technician = $this->technician();
        $record = SalaryRecord::create(['salary_code' => 'SAL-1', 'technician_id' => $technician->id, 'salary_month' => '2026-08-01', 'working_days' => 26, 'basic_salary' => 20000, 'net_salary' => 20000]);
        Sanctum::actingAs($technician);

        $this->getJson('/api/salary?month=2026-08')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/salary/{$record->id}")->assertOk()->assertJsonPath('data.salary_code', 'SAL-1');
    }

    public function test_technician_cannot_view_another_technicians_salary_record(): void
    {
        $owner = $this->technician(['mobile' => '9111100021']);
        $intruder = $this->technician(['mobile' => '9111100022']);
        $record = SalaryRecord::create(['salary_code' => 'SAL-2', 'technician_id' => $owner->id, 'salary_month' => '2026-08-01', 'working_days' => 26, 'basic_salary' => 20000, 'net_salary' => 20000]);

        Sanctum::actingAs($intruder);
        $this->getJson("/api/salary/{$record->id}")->assertStatus(404);
    }
}
