<?php

namespace Tests\Feature;

use App\Models\AmcPlan;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
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

    public function test_new_installation_request_crud(): void
    {
        $customer = $this->customer('Acme');
        $category = MachineCategory::create(['category_name' => 'Chimney']);
        $brand = Brand::create(['brand_name' => 'Kutchina']);
        $machine = Machine::create(['machine_name' => 'Kutchina Chimney', 'machine_code' => 'KC123456', 'machine_category_id' => $category->id, 'brand_id' => $brand->id, 'model' => 'KC-100', 'total_stock' => 5, 'status' => 'active']);
        $plan = AmcPlan::create(['plan_name' => 'Gold Plan', 'machine_category_id' => $category->id, 'brand_id' => $brand->id, 'plan_type' => 'comprehensive', 'duration' => '1_year', 'parts_included' => true, 'price' => 5000, 'tax_percent' => 18, 'status' => 'active']);
        $data = $this->baseData($customer) + [
            'request_type' => 'new_installation',
            'service_type' => 'installation',
            'machine_id' => $machine->id,
            'serial_number' => 'SER-100',
            'asset_number' => 'AST-100',
            'amc_plan_ids' => [$plan->id],
        ];

        $this->post(route('service-requests.store'), $data)->assertRedirect(route('service-requests.index'));
        $request = ServiceRequest::firstOrFail();
        $this->assertMatchesRegularExpression('/^SR-\d{6}-\d{4}$/', $request->request_code);
        $this->assertSame('Kutchina Chimney', $request->product_name);
        $this->assertSame('SER-100', $request->serial_number);
        $this->assertSame('AST-100', $request->asset_number);
        $this->assertTrue($request->amcPlans->contains($plan));
        $this->assertSame(4, $machine->fresh()->total_stock);
        $this->assertDatabaseHas('machine_stock_transactions', ['machine_id' => $machine->id, 'service_request_id' => $request->id, 'quantity' => -1, 'balance_after' => 4]);
        $this->get(route('service-requests.show', $request))->assertOk()->assertSee('Kutchina Chimney')->assertSee('Acme');

        $this->put(route('service-requests.update', $request), [...$data, 'subject' => 'Install updated'])
            ->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'subject' => 'Install updated']);
        $this->delete(route('service-requests.destroy', $request))->assertRedirect();
        $this->assertDatabaseMissing('service_requests', ['id' => $request->id]);
    }

    public function test_new_installation_rejected_when_machine_out_of_stock(): void
    {
        $customer = $this->customer('Acme');
        $category = MachineCategory::create(['category_name' => 'Chimney']);
        $brand = Brand::create(['brand_name' => 'Kutchina']);
        $machine = Machine::create(['machine_name' => 'Kutchina Chimney', 'machine_code' => 'KC999999', 'machine_category_id' => $category->id, 'brand_id' => $brand->id, 'model' => 'KC-100', 'total_stock' => 0, 'status' => 'active']);
        $data = $this->baseData($customer) + [
            'request_type' => 'new_installation',
            'service_type' => 'installation',
            'machine_id' => $machine->id,
        ];

        $this->post(route('service-requests.store'), $data)->assertSessionHasErrors('machine_id');
        $this->assertSame(0, ServiceRequest::count());
        $this->assertSame(0, $machine->fresh()->total_stock);
    }

    public function test_existing_service_allows_any_active_catalog_machine(): void
    {
        // Machine Master no longer tracks which customer a unit belongs to (it's catalog
        // stock, not a per-installation record), so Existing Machine Service must be able
        // to pick any active machine — it does not require machine.customer_id to match.
        $customer = $this->customer('Acme');
        $otherCustomer = $this->customer('Other');
        $machine = Machine::create(['machine_name' => 'Press', 'machine_code' => 'PR123456', 'status' => 'active']);
        $data = $this->baseData($customer) + ['request_type' => 'existing_service', 'service_type' => 'amc', 'machine_id' => $machine->id];

        $this->post(route('service-requests.store'), $data)->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['customer_id' => $customer->id, 'machine_id' => $machine->id, 'service_type' => 'amc']);

        $this->post(route('service-requests.store'), [...$data, 'customer_id' => $otherCustomer->id])
            ->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['customer_id' => $otherCustomer->id, 'machine_id' => $machine->id, 'service_type' => 'amc']);
    }

    public function test_service_request_form_has_searchable_customer_machine_and_read_only_product_fields(): void
    {
        $this->get(route('service-requests.create'))->assertOk()
            ->assertSee('Search customer by name, code or phone')
            ->assertSee('Search machine by code, name or model')
            ->assertSee('Product Category')
            ->assertSee('select multiple if required');
    }

    public function test_service_request_form_includes_location_map(): void
    {
        $this->get(route('service-requests.create'))->assertOk()
            ->assertSee('Service Location')
            ->assertSee('id="sr-map"', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false);
    }

    private function customer(string $name): Customer
    {
        return Customer::create(['customer_code' => strtoupper(substr($name, 0, 2)).random_int(100000, 999999), 'customer_type' => 'company', 'customer_name' => $name, 'mobile' => (string) random_int(7000000000, 9999999999), 'address' => 'Main Road', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pin_code' => '400001', 'status' => 'active']);
    }

    private function baseData(Customer $customer): array
    {
        return ['customer_id' => $customer->id, 'contact_phone' => $customer->mobile, 'subject' => 'Service needed', 'complaint' => 'Please visit', 'priority' => 'normal', 'preferred_date' => '2026-08-15', 'preferred_time' => '10:30', 'service_address' => $customer->address, 'city' => $customer->city, 'state' => $customer->state, 'pin_code' => $customer->pin_code, 'status' => 'open'];
    }
}
