<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\PartIssue;
use App\Models\PartRequest;
use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartsInventoryTest extends TestCase
{
    use RefreshDatabase;

    private Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        $this->actingAs($user);
        $this->technician = Technician::create(['name' => 'Parts Tech', 'mobile' => '8888888888', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'active', 'salary_type' => 'monthly']);
    }

    public function test_all_parts_inventory_pages_open(): void
    {
        foreach (['parts.index', 'suppliers.index', 'inventory.index', 'parts-issues.index', 'job-parts.index', 'parts-requests.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_stock_in_issue_return_and_request_issue_update_stock(): void
    {
        $this->post(route('parts.store'), ['part_name' => 'Control Board', 'category' => 'Electrical', 'unit' => 'piece', 'purchase_price' => 100, 'selling_price' => 150, 'tax_percent' => 18, 'minimum_stock' => 2, 'warranty_months' => 12, 'status' => 'active'])->assertRedirect();
        $part = Part::firstOrFail();

        $this->post(route('inventory.store'), ['part_id' => $part->id, 'transaction_type' => 'stock_in', 'quantity' => 10, 'unit_cost' => 100])->assertRedirect();
        $this->assertSame(10, $part->fresh()->current_stock);

        $this->post(route('parts-issues.store'), ['part_id' => $part->id, 'technician_id' => $this->technician->id, 'issued_quantity' => 4])->assertRedirect();
        $issue = PartIssue::firstOrFail();
        $this->assertSame(6, $part->fresh()->current_stock);

        $this->patch(route('parts-issues.update', $issue), ['used_quantity' => 2, 'returned_quantity' => 2, 'damaged_quantity' => 0])->assertRedirect();
        $this->assertSame(8, $part->fresh()->current_stock);
        $this->assertSame('closed', $issue->fresh()->status);

        $this->post(route('parts-requests.store'), ['technician_id' => $this->technician->id, 'part_id' => $part->id, 'quantity' => 3, 'urgency' => 'high', 'reason' => 'Required for service'])->assertRedirect();
        $request = PartRequest::firstOrFail();
        $this->patch(route('parts-requests.update', $request), ['status' => 'issued'])->assertRedirect();
        $this->assertSame(5, $part->fresh()->current_stock);

        $this->patch(route('parts-requests.update', $request), ['status' => 'issued'])->assertRedirect();
        $this->assertSame(5, $part->fresh()->current_stock);
    }
}
