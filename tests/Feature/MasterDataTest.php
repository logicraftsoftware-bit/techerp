<?php

namespace Tests\Feature;

use App\Models\Customer;
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

    public function test_machine_technician_and_skill_crud(): void
    {
        $c = Customer::create(['customer_code' => 'C1', 'customer_type' => 'company', 'customer_name' => 'Acme', 'mobile' => '9', 'address' => 'A', 'city' => 'C', 'state' => 'S', 'pin_code' => '1', 'status' => 'active']);
        $this->post(route('machines.store'), ['machine_code' => 'M1', 'machine_name' => 'Press', 'machine_type' => 'Hydraulic', 'customer_id' => $c->id, 'status' => 'active'])->assertRedirect(route('machines.index'));
        $this->assertDatabaseHas('machines', ['machine_code' => 'M1']);
        $s = Skill::create(['name' => 'Electrical', 'category' => 'electrical', 'is_active' => true]);
        $this->post(route('technicians.store'), ['employee_code' => 'T1', 'name' => 'Tech', 'mobile' => '8', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly', 'skills' => [$s->id]])->assertRedirect(route('technicians.index'));
        $this->assertTrue(Technician::first()->skills->contains($s));
    }
}
