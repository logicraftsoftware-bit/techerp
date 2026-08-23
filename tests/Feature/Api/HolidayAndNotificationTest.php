<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Holiday;
use App\Models\Machine;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\WorkAssignment;
use App\Notifications\JobAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HolidayAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100050',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    public function test_holidays_are_scoped_to_the_requested_month(): void
    {
        Holiday::create(['holiday_date' => '2026-08-15', 'name' => 'Independence Day']);
        Holiday::create(['holiday_date' => '2026-10-02', 'name' => 'Gandhi Jayanti']);
        Sanctum::actingAs($this->technician());

        $response = $this->getJson('/api/holidays?month=2026-08')->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Independence Day', $response->json('data.0.name'));
    }

    public function test_technician_can_list_and_mark_notifications_read(): void
    {
        $technician = $this->technician();
        $customer = Customer::create(['customer_code' => 'AC-API-2', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9999900002', 'address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'active']);
        $machine = Machine::create(['customer_id' => $customer->id, 'machine_name' => 'Press', 'machine_code' => 'PR-API-02', 'status' => 'active']);
        $request = ServiceRequest::create(['request_type' => 'existing_service', 'service_type' => 'paid_service', 'customer_id' => $customer->id, 'contact_phone' => $customer->mobile, 'machine_id' => $machine->id, 'product_name' => 'Press', 'subject' => 'Repair press', 'priority' => 'high', 'preferred_date' => '2026-08-20', 'preferred_time' => '10:00', 'service_address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'open']);
        $assignment = WorkAssignment::create(['service_request_id' => $request->id, 'technician_id' => $technician->id, 'assignment_role' => 'primary', 'scheduled_date' => '2026-08-20', 'start_time' => '10:00', 'end_time' => '12:00', 'priority' => 'high', 'status' => 'scheduled', 'service_address' => 'Road']);

        // Only the "database" channel is exercised here (no Firebase credentials in tests);
        // the FCM channel is a no-op when the technician has no registered device tokens.
        $technician->notify(new JobAssigned($assignment));

        Sanctum::actingAs($technician);
        $response = $this->getJson('/api/notifications')->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('job_assigned', $response->json('data.0.data.type'));
        $id = $response->json('data.0.id');

        $this->postJson("/api/notifications/{$id}/read")->assertOk();
        $this->assertNotNull($technician->notifications()->first()->read_at);
    }
}
