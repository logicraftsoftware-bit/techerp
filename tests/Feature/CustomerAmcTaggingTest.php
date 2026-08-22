<?php

namespace Tests\Feature;

use App\Models\AmcPlan;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\CustomerAmcTagging;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAmcTaggingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Machine $machine;

    private AmcPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());
        $this->customer = Customer::create(['customer_name' => 'Acme Industries', 'mobile' => '9000000001', 'address' => 'Main Road', 'city' => 'Kolkata', 'state' => 'West Bengal', 'pin_code' => '700001']);
        $this->machine = Machine::create(['customer_id' => $this->customer->id, 'machine_name' => 'Industrial Press', 'model' => 'IP-10', 'service_period' => '1_year', 'buying_price' => 100, 'selling_price' => 200, 'total_stock' => 1, 'location_name' => 'Site', 'status' => 'active']);
        $category = MachineCategory::create(['category_name' => 'Press']);
        $brand = Brand::create(['brand_name' => 'Acme']);
        $this->plan = AmcPlan::create(['plan_name' => 'Gold Plan', 'machine_category_id' => $category->id, 'brand_id' => $brand->id, 'plan_type' => 'comprehensive', 'duration' => '2_years', 'parts_included' => true, 'price' => 5000, 'tax_percent' => 18, 'status' => 'active']);
    }

    public function test_admin_can_create_update_and_delete_customer_amc_tagging(): void
    {
        $this->actingAs($this->admin)->post(route('customer-amc-taggings.store'), [
            'customer_id' => $this->customer->id, 'machine_id' => $this->machine->id,
            'amc_plan_id' => $this->plan->id, 'start_date' => '2026-08-22',
        ])->assertRedirect(route('customer-amc-taggings.index'));

        $tagging = CustomerAmcTagging::firstOrFail();
        $this->assertSame('2028-08-21', $tagging->end_date->toDateString());
        $this->actingAs($this->admin)->get(route('customer-amc-taggings.index'))->assertOk()->assertSee('Acme Industries')->assertSee('Industrial Press')->assertSee('Gold Plan');

        $this->actingAs($this->admin)->put(route('customer-amc-taggings.update', $tagging), [
            'customer_id' => $this->customer->id, 'machine_id' => $this->machine->id,
            'amc_plan_id' => $this->plan->id, 'start_date' => '2026-09-01',
        ])->assertRedirect(route('customer-amc-taggings.index'));
        $this->assertDatabaseHas('customer_amc_taggings', ['id' => $tagging->id, 'end_date' => '2028-08-31 00:00:00']);

        $this->actingAs($this->admin)->delete(route('customer-amc-taggings.destroy', $tagging))->assertRedirect();
        $this->assertDatabaseMissing('customer_amc_taggings', ['id' => $tagging->id]);
    }

    public function test_machine_must_belong_to_selected_customer(): void
    {
        $other = Customer::create(['customer_name' => 'Other Customer', 'mobile' => '9000000002', 'address' => 'Other Road', 'city' => 'Kolkata', 'state' => 'West Bengal', 'pin_code' => '700002']);
        $this->actingAs($this->admin)->post(route('customer-amc-taggings.store'), [
            'customer_id' => $other->id, 'machine_id' => $this->machine->id,
            'amc_plan_id' => $this->plan->id, 'start_date' => '2026-08-22',
        ])->assertSessionHasErrors('machine_id');
    }

    public function test_non_admin_with_view_permission_cannot_modify_taggings(): void
    {
        $viewer = User::factory()->create();
        $viewer->permissions()->attach(Permission::where('slug', 'customer-amc-taggings.view')->firstOrFail());
        $this->actingAs($viewer)->get(route('customer-amc-taggings.index'))->assertOk();
        $this->actingAs($viewer)->get(route('customer-amc-taggings.create'))->assertForbidden();
    }

    public function test_admin_dashboard_shows_amcs_expiring_within_one_month(): void
    {
        CustomerAmcTagging::create(['customer_id' => $this->customer->id, 'machine_id' => $this->machine->id, 'amc_plan_id' => $this->plan->id, 'start_date' => today()->subYears(2)->addDays(10), 'end_date' => today()->addDays(9), 'created_by' => $this->admin->id]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('AMC Expiry Reminder')->assertSee('Acme Industries');
    }
}
