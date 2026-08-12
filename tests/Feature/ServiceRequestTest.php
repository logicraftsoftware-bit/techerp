<?php

namespace Tests\Feature;

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
        $data = $this->baseData($customer) + [
            'request_type' => 'new_installation',
            'service_type' => 'installation',
            'machine_category_id' => $category->id,
            'brand_id' => $brand->id,
            'product_name' => 'Kutchina Chimney',
            'model' => 'KC-100',
        ];

        $this->post(route('service-requests.store'), $data)->assertRedirect(route('service-requests.index'));
        $request = ServiceRequest::firstOrFail();
        $this->assertMatchesRegularExpression('/^SR-\d{6}-\d{4}$/', $request->request_code);
        $this->get(route('service-requests.show', $request))->assertOk()->assertSee('Kutchina Chimney')->assertSee('Acme');

        $this->put(route('service-requests.update', $request), [...$data, 'subject' => 'Install updated'])
            ->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'subject' => 'Install updated']);
        $this->delete(route('service-requests.destroy', $request))->assertRedirect();
        $this->assertDatabaseMissing('service_requests', ['id' => $request->id]);
    }

    public function test_existing_service_requires_machine_belonging_to_customer(): void
    {
        $customer = $this->customer('Acme');
        $otherCustomer = $this->customer('Other');
        $machine = Machine::create(['customer_id' => $customer->id, 'machine_name' => 'Press', 'machine_code' => 'PR123456', 'status' => 'active']);
        $data = $this->baseData($customer) + ['request_type' => 'existing_service', 'service_type' => 'amc', 'machine_id' => $machine->id];

        $this->post(route('service-requests.store'), $data)->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['customer_id' => $customer->id, 'machine_id' => $machine->id, 'service_type' => 'amc']);

        $this->post(route('service-requests.store'), [...$data, 'customer_id' => $otherCustomer->id])
            ->assertSessionHasErrors('machine_id');
    }

    private function customer(string $name): Customer
    {
        return Customer::create(['customer_code' => strtoupper(substr($name, 0, 2)).random_int(100000, 999999), 'customer_type' => 'company', 'customer_name' => $name, 'mobile' => (string) random_int(7000000000, 9999999999), 'address' => 'Main Road', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pin_code' => '400001', 'status' => 'active']);
    }

    private function baseData(Customer $customer): array
    {
        return ['customer_id' => $customer->id, 'subject' => 'Service needed', 'complaint' => 'Please visit', 'priority' => 'normal', 'preferred_date' => '2026-08-15', 'preferred_time' => '10:30', 'service_address' => $customer->address, 'city' => $customer->city, 'state' => $customer->state, 'pin_code' => $customer->pin_code, 'status' => 'open'];
    }
}
