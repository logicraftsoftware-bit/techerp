<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Machine;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $u = User::factory()->create();
        $u->roles()->attach(Role::where('slug', 'super-admin')->first());
        $this->actingAs($u);
    }

    public function test_customer_contact_crud(): void
    {
        $data = ['entry_type' => 'crm', 'refer_type' => 'self', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9999999999', 'address' => 'Street', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pin_code' => '400001', 'status' => 'active', 'contacts' => [['name' => 'Jane', 'mobile' => '8888888888']]];
        $this->post(route('customers.store'), $data)->assertRedirect(route('customers.index'));
        $c = Customer::first();
        $this->assertMatchesRegularExpression('/^AC\d{6}$/', $c->customer_code);
        $this->assertCount(1, $c->contacts);
        $this->delete(route('customers.destroy', $c))->assertRedirect();
        $this->assertSoftDeleted($c);
    }

    public function test_customer_form_uses_map_picker_with_hidden_coordinates(): void
    {
        $this->get(route('customers.create'))->assertOk()
            ->assertSee('Customer Location')
            ->assertSee('customer-map')
            ->assertDontSee('label>Latitude', false)
            ->assertDontSee('label>Longitude', false);
    }

    public function test_machine_technician_and_skill_crud(): void
    {
        $c = Customer::create(['customer_code' => 'C1', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9', 'address' => 'A', 'city' => 'C', 'state' => 'S', 'pin_code' => '1', 'status' => 'active']);
        $brand = Brand::create(['brand_name' => 'HydroTech']);
        $this->post(route('machines.store'), ['machine_name' => 'Press', 'brand_id' => $brand->id, 'model' => 'P100', 'service_period' => '6_months', 'buying_price' => 10000, 'selling_price' => 12000, 'total_stock' => 5, 'location_name' => 'Main Warehouse', 'status' => 'active'])->assertRedirect(route('machines.index'));
        $this->assertMatchesRegularExpression('/^PR\d{6}$/', Machine::firstOrFail()->machine_code);
        $s = Skill::create(['name' => 'Electrical', 'category' => 'electrical', 'is_active' => true]);
        $this->post(route('technicians.store'), ['employee_code' => 'T1', 'name' => 'Tech', 'mobile' => '8', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly', 'skills' => [$s->id]])->assertRedirect(route('technicians.index'));
        $this->assertTrue(Technician::first()->skills->contains($s));
    }

    public function test_brand_crud_and_machine_mapping(): void
    {
        $this->post(route('brands.store'), ['brand_name' => 'Daikin'])->assertRedirect(route('brands.index'));
        $brand = Brand::firstOrFail();
        $this->put(route('brands.update', $brand), ['brand_name' => 'Daikin India'])->assertRedirect(route('brands.index'));
        $this->assertDatabaseHas('brands', ['brand_name' => 'Daikin India']);
        $this->delete(route('brands.destroy', $brand))->assertRedirect();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_machine_index_renders_with_records(): void
    {
        $this->get(route('machines.index'))
            ->assertOk()
            ->assertSee('Free Service');
    }
}
