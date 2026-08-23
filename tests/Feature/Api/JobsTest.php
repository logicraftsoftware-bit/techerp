<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\Part;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\Unit;
use App\Models\WorkAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100030',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    private function assignmentFor(Technician $technician): WorkAssignment
    {
        $customer = Customer::create(['customer_code' => 'AC-API-1', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9999900001', 'address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'active']);
        $machine = Machine::create(['customer_id' => $customer->id, 'machine_name' => 'Press', 'machine_code' => 'PR-API-01', 'status' => 'active']);
        $request = ServiceRequest::create(['request_type' => 'existing_service', 'service_type' => 'paid_service', 'customer_id' => $customer->id, 'contact_phone' => $customer->mobile, 'machine_id' => $machine->id, 'product_name' => 'Press', 'subject' => 'Repair press', 'priority' => 'high', 'preferred_date' => '2026-08-20', 'preferred_time' => '10:00', 'service_address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'open']);

        return WorkAssignment::create(['service_request_id' => $request->id, 'technician_id' => $technician->id, 'assignment_role' => 'primary', 'scheduled_date' => '2026-08-20', 'start_time' => '10:00', 'end_time' => '12:00', 'priority' => 'high', 'status' => 'scheduled', 'service_address' => 'Road']);
    }

    public function test_technician_can_list_and_view_own_jobs(): void
    {
        $technician = $this->technician();
        $assignment = $this->assignmentFor($technician);
        Sanctum::actingAs($technician);

        $this->getJson('/api/jobs?date=2026-08-20')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/jobs/{$assignment->id}")->assertOk()->assertJsonPath('data.assignment_code', $assignment->assignment_code);
    }

    public function test_technician_cannot_view_another_technicians_job(): void
    {
        $owner = $this->technician(['mobile' => '9111100031']);
        $intruder = $this->technician(['mobile' => '9111100032']);
        $assignment = $this->assignmentFor($owner);

        Sanctum::actingAs($intruder);
        $this->getJson("/api/jobs/{$assignment->id}")->assertStatus(404);
    }

    public function test_technician_can_update_job_status_and_it_is_recorded_with_technician_attribution(): void
    {
        $technician = $this->technician();
        $assignment = $this->assignmentFor($technician);
        Sanctum::actingAs($technician);

        $this->patchJson("/api/jobs/{$assignment->id}/status", ['status' => 'in_progress', 'remarks' => 'On my way'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('work_status_histories', ['work_assignment_id' => $assignment->id, 'to_status' => 'in_progress', 'changed_by_technician_id' => $technician->id]);
        $this->assertDatabaseHas('service_requests', ['id' => $assignment->service_request_id, 'status' => 'in_progress']);
    }

    public function test_technician_can_log_parts_used_and_stock_decrements(): void
    {
        $technician = $this->technician();
        $assignment = $this->assignmentFor($technician);
        $category = MachineCategory::create(['category_name' => 'Press Parts']);
        $unit = Unit::create(['unit_name' => 'Piece']);
        $part = Part::create(['part_name' => 'Valve', 'part_code' => 'PT-API-01', 'machine_category_id' => $category->id, 'unit_id' => $unit->id, 'purchase_price' => 100, 'selling_price' => 150, 'current_stock' => 5, 'status' => 'active']);
        Sanctum::actingAs($technician);

        $this->postJson("/api/jobs/{$assignment->id}/parts", ['part_id' => $part->id, 'quantity' => 2, 'rate' => 150, 'tax_percent' => 0])
            ->assertCreated();

        $this->assertSame(3, $part->fresh()->current_stock);
        $this->assertDatabaseHas('job_parts', ['work_assignment_id' => $assignment->id, 'part_id' => $part->id, 'quantity' => 2]);
    }

    public function test_technician_can_download_own_job_card(): void
    {
        $technician = $this->technician();
        $assignment = $this->assignmentFor($technician);
        Sanctum::actingAs($technician);

        $this->getJson("/api/jobs/{$assignment->id}/job-card")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
