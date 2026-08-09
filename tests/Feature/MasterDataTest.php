<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Machine;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $this->post(route('technicians.store'), ['name' => 'Tech', 'gender' => 'male', 'mobile' => '8', 'password' => 'password123', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly', 'monthly_salary' => 10000, 'skills' => [$s->id]])->assertRedirect(route('technicians.index'));
        $this->assertMatchesRegularExpression('/^TE\d{6}$/', Technician::firstOrFail()->employee_code);
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

    public function test_department_crud(): void
    {
        $this->post(route('departments.store'), ['department_name' => 'Service'])
            ->assertRedirect(route('departments.index'));
        $department = Department::firstOrFail();

        $this->get(route('departments.index'))->assertOk()->assertSee('Service');
        $this->put(route('departments.update', $department), ['department_name' => 'Field Service'])
            ->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['department_name' => 'Field Service']);

        $this->delete(route('departments.destroy', $department))->assertRedirect();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_machine_index_renders_with_records(): void
    {
        $this->get(route('machines.index'))
            ->assertOk()
            ->assertSee('Free Service');
    }

    public function test_machine_document_is_served_without_public_storage_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('machines/photo.webp', 'image-content');
        $machine = Machine::create(['machine_name' => 'Press', 'machine_code' => 'PR123456', 'model' => 'P1', 'service_period' => '4_months', 'buying_price' => 1, 'selling_price' => 2, 'total_stock' => 1, 'location_name' => 'Store', 'status' => 'active']);
        $document = $machine->documents()->create(['document_type' => 'photo', 'title' => 'Photo', 'file_path' => 'machines/photo.webp', 'original_name' => 'photo.webp', 'mime_type' => 'image/webp', 'file_size' => 13]);

        $this->get(route('machines.show', $machine))
            ->assertOk()
            ->assertSee('Machine Photo')
            ->assertSee(route('machine-documents.show', $document));

        $this->get(route('machine-documents.show', $document))
            ->assertOk()
            ->assertHeader('content-type', 'image/webp');

        $this->deleteJson(route('machine-documents.destroy', $document))
            ->assertOk()
            ->assertJson(['message' => 'Upload deleted.']);
        Storage::disk('public')->assertMissing('machines/photo.webp');
        $this->assertDatabaseMissing('machine_documents', ['id' => $document->id]);
    }
}
