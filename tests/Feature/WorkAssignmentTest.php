<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Machine;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkAssignment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        $this->actingAs($user);
    }

    public function test_work_assignment_crud_and_conflict_detection(): void
    {
        $customer = Customer::create(['customer_code' => 'AC123456', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9999999999', 'address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'active']);
        $machine = Machine::create(['customer_id' => $customer->id, 'machine_name' => 'Press', 'machine_code' => 'PR123456', 'status' => 'active']);
        $request = ServiceRequest::create(['request_type' => 'existing_service', 'service_type' => 'paid_service', 'customer_id' => $customer->id, 'contact_phone' => $customer->mobile, 'machine_id' => $machine->id, 'product_name' => 'Press', 'subject' => 'Repair press', 'priority' => 'high', 'preferred_date' => '2026-08-20', 'preferred_time' => '10:00', 'service_address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'open']);
        $technician = Technician::create(['name' => 'Field Tech', 'mobile' => '8888888888', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly']);
        $data = ['service_request_id' => $request->id, 'technician_id' => $technician->id, 'assignment_role' => 'primary', 'scheduled_date' => '2026-08-20', 'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'scheduled', 'service_address' => 'Road', 'work_instructions' => 'Inspect and repair', 'internal_notes' => 'Carry tools'];
        $this->post(route('assignments.store'), $data)->assertRedirect(route('assignments.index'));
        $assignment = WorkAssignment::firstOrFail();
        $this->assertMatchesRegularExpression('/^WA-\d{6}-\d{4}$/', $assignment->assignment_code);
        $this->assertSame('high', $assignment->priority);
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'scheduled']);
        $this->get(route('assignments.show', $assignment))->assertOk()->assertSee('Field Tech')->assertSee('Repair press');
        $this->post(route('assignments.store'), [...$data, 'start_time' => '11:00', 'end_time' => '13:00'])->assertSessionHasErrors('technician_id');
        $this->put(route('assignments.update', $assignment), [...$data, 'status' => 'in_progress'])->assertRedirect(route('assignments.index'));
        $this->assertDatabaseHas('work_assignments', ['id' => $assignment->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('work_status_histories', ['work_assignment_id' => $assignment->id, 'to_status' => 'in_progress']);
        $this->get(route('job-cards.index', ['month' => '2026-08']))->assertOk()->assertSee($assignment->assignment_code)->assertSee('1 work');
        $this->get(route('work-status.show', $assignment))->assertOk()->assertSee('Work Status Timeline')->assertSee('In Progress');
        $this->get(route('service-reports.show', $request))->assertOk()->assertSee('Lifecycle Timeline')->assertSee($assignment->assignment_code);
        $this->patch(route('work-status.update', $assignment), ['status' => 'completed', 'remarks' => 'Work finished'])->assertRedirect();
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'completed']);
        $this->delete(route('assignments.destroy', $assignment))->assertRedirect();
        $this->assertDatabaseMissing('work_assignments', ['id' => $assignment->id]);
    }

    public function test_already_assigned_request_is_excluded_from_create_but_kept_on_its_own_edit_page(): void
    {
        $customer = Customer::create(['customer_code' => 'AC654321', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9999999998', 'address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'active']);
        $machine = Machine::create(['machine_name' => 'Press', 'machine_code' => 'PR654321', 'status' => 'active']);
        $request = ServiceRequest::create(['request_type' => 'existing_service', 'service_type' => 'paid_service', 'customer_id' => $customer->id, 'contact_phone' => $customer->mobile, 'machine_id' => $machine->id, 'product_name' => 'Press', 'subject' => 'Repair press again', 'priority' => 'high', 'preferred_date' => '2026-08-20', 'preferred_time' => '10:00', 'service_address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'open']);
        $technician = Technician::create(['name' => 'Field Tech Two', 'mobile' => '8888888887', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly']);

        $this->get(route('assignments.create'))->assertOk()->assertSee($request->request_code);

        $data = ['service_request_id' => $request->id, 'technician_id' => $technician->id, 'assignment_role' => 'primary', 'scheduled_date' => '2026-08-20', 'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'scheduled', 'service_address' => 'Road'];
        $this->post(route('assignments.store'), $data)->assertRedirect(route('assignments.index'));
        $assignment = WorkAssignment::firstOrFail();

        $this->get(route('assignments.create'))->assertOk()->assertDontSee($request->request_code);
        $this->get(route('assignments.edit', $assignment))->assertOk()->assertSee($request->request_code);
    }

    public function test_work_assignment_work_status_and_service_report_pages_survive_a_deleted_customer(): void
    {
        $customer = Customer::create(['customer_code' => 'AC111222', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9999999996', 'address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'active']);
        $machine = Machine::create(['machine_name' => 'Press', 'machine_code' => 'PR111222', 'status' => 'active']);
        $request = ServiceRequest::create(['request_type' => 'existing_service', 'service_type' => 'paid_service', 'customer_id' => $customer->id, 'contact_phone' => $customer->mobile, 'machine_id' => $machine->id, 'product_name' => 'Press', 'subject' => 'Repair press', 'priority' => 'high', 'preferred_date' => '2026-08-20', 'preferred_time' => '10:00', 'service_address' => 'Road', 'city' => 'Mumbai', 'state' => 'MH', 'pin_code' => '400001', 'status' => 'open']);
        $technician = Technician::create(['name' => 'Field Tech Three', 'mobile' => '8888888886', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly']);
        $data = ['service_request_id' => $request->id, 'technician_id' => $technician->id, 'assignment_role' => 'primary', 'scheduled_date' => '2026-08-20', 'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'scheduled', 'service_address' => 'Road'];
        $this->post(route('assignments.store'), $data)->assertRedirect(route('assignments.index'));
        $assignment = WorkAssignment::firstOrFail();

        $customer->delete();

        $this->get(route('assignments.index'))->assertOk()->assertSee('Acme');
        $this->get(route('assignments.show', $assignment))->assertOk()->assertSee('Acme');
        $this->get(route('work-status.index'))->assertOk()->assertSee('Acme');
        $this->get(route('work-status.show', $assignment))->assertOk()->assertSee('Acme');
        $this->get(route('service-reports.index'))->assertOk()->assertSee('Acme');
        $this->get(route('service-reports.show', $request))->assertOk()->assertSee('Acme');
    }
}
