<?php

namespace Tests\Feature;

use App\Models\AmcPlan;
use App\Models\Brand;
use App\Models\CommissionType;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Technician;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $data = ['entry_type' => 'crm', 'refer_type' => 'self', 'customer_type' => 'company', 'customer_name' => 'Acme', 'date_of_birth' => '1990-05-20', 'mobile' => '9999999999', 'address' => 'Street', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pin_code' => '400001', 'status' => 'active', 'contacts' => [['name' => 'Jane', 'mobile' => '8888888888']]];
        $this->post(route('customers.store'), $data)->assertRedirect(route('customers.index'));
        $c = Customer::first();
        $this->assertMatchesRegularExpression('/^AC\d{6}$/', $c->customer_code);
        $this->assertCount(1, $c->contacts);
        $this->assertSame('1990-05-20', $c->date_of_birth->format('Y-m-d'));
        $this->delete(route('customers.destroy', $c))->assertRedirect();
        $this->assertSoftDeleted($c);
    }

    public function test_customer_bulk_csv_import(): void
    {
        ob_start();
        $this->get(route('customers.import.sample'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->sendContent();
        $this->assertStringContainsString('customer_name', ob_get_clean());

        $csv = "customer_type,customer_name,company_name,contact_person,date_of_birth,mobile,alternate_mobile,email,whatsapp,gst_number,pan_number,address,city,state,pin_code,status,notes\n"
            ."individual,Bulk Customer One,,,1985-01-15,9000000001,,,,,,Street 1,Kolkata,West Bengal,700001,active,\n"
            ."individual,,,,,9000000002,,,,,,Street 2,Kolkata,West Bengal,700002,active,\n";
        $file = UploadedFile::fake()->createWithContent('customers.csv', $csv);

        $this->post(route('customers.import.store'), ['file' => $file])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('import_errors');
        $this->assertDatabaseHas('customers', ['customer_name' => 'Bulk Customer One', 'mobile' => '9000000001']);
        $this->assertDatabaseMissing('customers', ['mobile' => '9000000002']);
        $this->assertSame(1, Customer::count());
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
        $category = MachineCategory::create(['category_name' => 'Press']);
        $this->post(route('machines.store'), ['machine_name' => 'Press', 'brand_id' => $brand->id, 'machine_category_id' => $category->id, 'model' => 'P100', 'service_period' => '6_months', 'buying_price' => 10000, 'selling_price' => 12000, 'total_stock' => 5, 'location_name' => 'Main Warehouse', 'status' => 'active'])->assertRedirect(route('machines.index'));
        $this->assertMatchesRegularExpression('/^PR\d{6}$/', Machine::firstOrFail()->machine_code);
        $s = Skill::create(['name' => 'Electrical', 'category' => 'electrical', 'is_active' => true]);
        $commissionType = CommissionType::create(['type_name' => 'Installation Bonus', 'calculation_type' => 'flat', 'value' => 500]);
        $this->post(route('technicians.store'), ['name' => 'Tech', 'gender' => 'male', 'mobile' => '8', 'password' => 'password123', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_structure_type' => 'fixed', 'salary_type' => 'monthly', 'monthly_salary' => 10000, 'daily_salary' => '', 'overtime_rate' => '', 'travel_allowance' => '', 'food_allowance' => '', 'other_allowance' => '', 'esi' => '', 'pf' => '', 'monthly_paid_leave_days' => 1, 'skills' => [$s->id], 'commission_type_ids' => [$commissionType->id]])->assertRedirect(route('technicians.index'));
        $this->assertMatchesRegularExpression('/^TE\d{6}$/', Technician::firstOrFail()->employee_code);
        $this->assertSame('0.00', Technician::firstOrFail()->daily_salary);
        $this->assertTrue(Technician::first()->skills->contains($s));
        $this->assertTrue(Technician::first()->commissionTypes->contains($commissionType));
        $this->assertSame(1, Technician::first()->monthly_paid_leave_days);
        Storage::fake('public');
        Storage::disk('public')->put('technicians/profile.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $technician = Technician::firstOrFail();
        $technician->update(['profile_photo' => 'technicians/profile.png']);
        $this->get(route('technicians.photo', $technician))->assertOk();
        $this->get(route('technicians.show', $technician))->assertOk()
            ->assertSee('Personal Information')
            ->assertSee('Salary Structure')
            ->assertSee(route('technicians.photo', $technician));
        $this->get(route('technicians.id-card', $technician))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload($technician->employee_code.'-id-card.pdf');
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

    public function test_unit_master_crud(): void
    {
        $this->post(route('units.store'), ['unit_name' => 'Box'])->assertRedirect(route('units.index'));
        $unit = Unit::firstOrFail();
        $this->get(route('units.index'))->assertOk()->assertSee('Box');
        $this->put(route('units.update', $unit), ['unit_name' => 'Carton'])->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'unit_name' => 'Carton']);
        $this->delete(route('units.destroy', $unit))->assertRedirect();
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_commission_type_master_crud(): void
    {
        $this->post(route('commission-types.store'), ['type_name' => 'Installation Bonus', 'calculation_type' => 'flat', 'value' => 500])->assertRedirect(route('commission-types.index'));
        $commissionType = CommissionType::firstOrFail();
        $this->get(route('commission-types.index'))->assertOk()->assertSee('Installation Bonus');
        $this->put(route('commission-types.update', $commissionType), ['type_name' => 'AMC Commission', 'calculation_type' => 'percentage', 'value' => 5])->assertRedirect(route('commission-types.index'));
        $this->assertDatabaseHas('commission_types', ['id' => $commissionType->id, 'type_name' => 'AMC Commission', 'calculation_type' => 'percentage']);
        $this->delete(route('commission-types.destroy', $commissionType))->assertRedirect();
        $this->assertDatabaseMissing('commission_types', ['id' => $commissionType->id]);
    }

    public function test_technician_commission_based_salary_structure(): void
    {
        $commissionType = CommissionType::create(['type_name' => 'AMC Commission', 'calculation_type' => 'percentage', 'value' => 5]);
        $base = ['name' => 'Commission Tech', 'gender' => 'male', 'mobile' => '8100000000', 'password' => 'password123', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active'];

        $this->post(route('technicians.store'), $base + ['salary_structure_type' => 'commission_based'])
            ->assertSessionHasErrors('commission_type_ids');
        $this->assertSame(0, Technician::count());

        $this->post(route('technicians.store'), $base + ['salary_structure_type' => 'commission_based', 'commission_type_ids' => [$commissionType->id]])
            ->assertRedirect(route('technicians.index'));
        $technician = Technician::firstOrFail();
        $this->assertSame('commission_based', $technician->salary_structure_type);
        $this->assertSame('0.00', $technician->monthly_salary);
        $this->assertTrue($technician->commissionTypes->contains($commissionType));
    }

    public function test_role_master_crud_and_system_role_guard(): void
    {
        $this->post(route('roles.store'), ['name' => 'Field Coordinator'])
            ->assertRedirect(route('roles.index'));
        $role = Role::where('name', 'Field Coordinator')->firstOrFail();
        $this->assertSame('field-coordinator', $role->slug);
        $this->assertFalse($role->is_system);

        $this->get(route('roles.index'))->assertOk()->assertSee('Field Coordinator');
        $this->put(route('roles.update', $role), ['name' => 'Regional Coordinator'])
            ->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Regional Coordinator']);

        $this->delete(route('roles.destroy', $role))->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        $systemRole = Role::where('slug', 'manager')->firstOrFail();
        $this->get(route('roles.edit', $systemRole))->assertNotFound();
        $this->put(route('roles.update', $systemRole), ['name' => 'Renamed Manager'])->assertStatus(422);
        $this->delete(route('roles.destroy', $systemRole))->assertStatus(422);
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id, 'name' => 'Manager']);
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

    public function test_amc_plan_crud(): void
    {
        $category = MachineCategory::create(['category_name' => 'Chimney']);
        $brand = Brand::create(['brand_name' => 'Kutchina Chimney']);
        $data = [
            'plan_name' => 'Gold Plan',
            'machine_category_id' => $category->id,
            'brand_id' => $brand->id,
            'plan_type' => 'comprehensive',
            'description' => 'Internal parts included',
            'duration' => '1_year',
            'parts_included' => '1',
            'price' => '4999.50',
            'tax_percent' => '18',
            'status' => 'active',
        ];

        $this->post(route('amc-plans.store'), $data)->assertRedirect(route('amc-plans.index'));
        $plan = AmcPlan::firstOrFail();
        $this->assertMatchesRegularExpression('/^AMC-\d{3}$/', $plan->plan_code);
        $this->get(route('amc-plans.index'))->assertOk()->assertSee('Gold Plan')->assertSee('Kutchina Chimney');

        $this->put(route('amc-plans.update', $plan), [...$data, 'plan_name' => 'Platinum Plan', 'parts_included' => '0'])
            ->assertRedirect(route('amc-plans.index'));
        $this->assertDatabaseHas('amc_plans', ['id' => $plan->id, 'plan_name' => 'Platinum Plan', 'parts_included' => false]);

        $this->delete(route('amc-plans.destroy', $plan))->assertRedirect();
        $this->assertDatabaseMissing('amc_plans', ['id' => $plan->id]);
    }

    public function test_machine_index_renders_with_records(): void
    {
        $this->get(route('machines.index'))
            ->assertOk()
            ->assertSee('Free Service');
    }

    public function test_machine_form_has_searchable_brand_and_category_fields(): void
    {
        Brand::create(['brand_name' => 'Kutchina']);
        MachineCategory::create(['category_name' => 'Chimney']);

        $this->get(route('machines.create'))
            ->assertOk()
            ->assertSee('Search or select brand')
            ->assertSee('Search or select category')
            ->assertSee('Kutchina')
            ->assertSee('Chimney');
    }

    public function test_machine_bulk_csv_import(): void
    {
        Brand::create(['brand_name' => 'Kutchina']);
        MachineCategory::create(['category_name' => 'Chimney']);

        ob_start();
        $this->get(route('machines.import.sample'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->sendContent();
        $this->assertStringContainsString('machine_name', ob_get_clean());

        $csv = "brand_name,machine_category_name,machine_name,model,manufacturing_date,service_period,buying_price,selling_price,total_stock,location_name,status\n"
            .'Kutchina,Chimney,Bulk Machine One,BM-100,2026-01-15,4_months,14500,21990,10,Main Warehouse,active'."\n"
            .'Unknown Brand,Chimney,Bulk Machine Two,BM-200,2026-01-15,4_months,14500,21990,10,Main Warehouse,active'."\n";
        $file = UploadedFile::fake()->createWithContent('machines.csv', $csv);

        $this->post(route('machines.import.store'), ['file' => $file])
            ->assertRedirect(route('machines.index'))
            ->assertSessionHas('import_errors');
        $this->assertDatabaseHas('machines', ['machine_name' => 'Bulk Machine One', 'total_stock' => 10]);
        $this->assertDatabaseMissing('machines', ['machine_name' => 'Bulk Machine Two']);
        $this->assertSame(1, Machine::count());
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
