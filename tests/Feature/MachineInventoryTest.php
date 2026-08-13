<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineInventoryTest extends TestCase
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

    public function test_machine_inventory_page_opens(): void
    {
        $this->get(route('machine-inventory.index'))->assertOk();
    }

    public function test_stock_in_and_stock_out_update_machine_total_stock(): void
    {
        $machine = Machine::create(['machine_name' => 'Press', 'machine_code' => 'PR123456', 'model' => 'P1', 'service_period' => '4_months', 'buying_price' => 1, 'selling_price' => 2, 'total_stock' => 5, 'location_name' => 'Store', 'status' => 'active']);

        $this->post(route('machine-inventory.store'), ['machine_id' => $machine->id, 'transaction_type' => 'stock_in', 'quantity' => 10])->assertRedirect();
        $this->assertSame(15, $machine->fresh()->total_stock);

        $this->post(route('machine-inventory.store'), ['machine_id' => $machine->id, 'transaction_type' => 'stock_out', 'quantity' => 4])->assertRedirect();
        $this->assertSame(11, $machine->fresh()->total_stock);
        $this->assertDatabaseHas('machine_stock_transactions', ['machine_id' => $machine->id, 'transaction_type' => 'stock_out', 'quantity' => -4, 'balance_after' => 11]);

        $this->post(route('machine-inventory.store'), ['machine_id' => $machine->id, 'transaction_type' => 'stock_out', 'quantity' => 999])
            ->assertStatus(422);
        $this->assertSame(11, $machine->fresh()->total_stock);
    }
}
