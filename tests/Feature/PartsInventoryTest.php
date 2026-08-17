<?php

namespace Tests\Feature;

use App\Models\MachineCategory;
use App\Models\Part;
use App\Models\PartIssue;
use App\Models\PartRequest;
use App\Models\Role;
use App\Models\Technician;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_all_parts_inventory_create_pages_open(): void
    {
        foreach (['parts.create', 'suppliers.create', 'inventory.create', 'parts-issues.create', 'job-parts.create', 'parts-requests.create'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_suppliers_are_removed_from_the_sidebar_but_still_reachable(): void
    {
        $this->get(route('parts.index'))->assertOk()->assertDontSee('Suppliers');
        // Data/routes are intentionally kept, just unlinked from navigation.
        $this->get(route('suppliers.index'))->assertOk();
    }

    private function part(): Part
    {
        $category = MachineCategory::create(['category_name' => 'Chimney']);
        $unit = Unit::create(['unit_name' => 'Piece']);
        $this->post(route('parts.store'), [
            'part_name' => 'Control Board', 'machine_category_id' => $category->id, 'unit_id' => $unit->id,
            'purchase_price' => 100, 'selling_price' => 150, 'current_stock' => 0,
            'has_amc' => '1', 'has_warranty' => '1', 'warranty_months' => 12, 'status' => 'active',
        ])->assertRedirect();

        return Part::firstOrFail();
    }

    public function test_part_stores_category_unit_amc_and_warranty(): void
    {
        $part = $this->part();
        $this->assertNotNull($part->machine_category_id);
        $this->assertNotNull($part->unit_id);
        $this->assertTrue($part->has_amc);
        $this->assertTrue($part->has_warranty);
        $this->assertSame(12, $part->warranty_months);
    }

    public function test_stock_in_issue_return_and_request_issue_update_stock(): void
    {
        $part = $this->part();

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

    public function test_part_can_be_edited_updated_and_deleted(): void
    {
        $part = $this->part();

        $this->get(route('parts.edit', $part))->assertOk()->assertSee('Control Board');

        $this->put(route('parts.update', $part), [
            'part_name' => 'Control Board V2', 'machine_category_id' => $part->machine_category_id, 'unit_id' => $part->unit_id,
            'purchase_price' => 120, 'selling_price' => 180, 'current_stock' => 5,
            'has_amc' => '0', 'has_warranty' => '0', 'status' => 'active',
        ])->assertRedirect(route('parts.index'));
        $this->assertSame('Control Board V2', $part->fresh()->part_name);
        $this->assertSame(5, $part->fresh()->current_stock);

        $this->delete(route('parts.destroy', $part))->assertRedirect();
        $this->assertDatabaseMissing('parts', ['id' => $part->id]);
    }

    public function test_parts_index_search_filters_by_name_or_code(): void
    {
        $category = MachineCategory::create(['category_name' => 'Chimney']);
        $unit = Unit::create(['unit_name' => 'Piece']);
        Part::create(['part_name' => 'Control Board', 'part_code' => 'PT-00001', 'machine_category_id' => $category->id, 'unit_id' => $unit->id, 'purchase_price' => 100, 'selling_price' => 150, 'current_stock' => 0, 'status' => 'active']);
        Part::create(['part_name' => 'Fan Motor', 'part_code' => 'PT-00002', 'machine_category_id' => $category->id, 'unit_id' => $unit->id, 'purchase_price' => 100, 'selling_price' => 150, 'current_stock' => 0, 'status' => 'active']);

        $this->get(route('parts.index', ['search' => 'Fan']))->assertOk()->assertSee('Fan Motor')->assertDontSee('Control Board');
        $this->get(route('parts.index', ['search' => 'PT-00001']))->assertOk()->assertSee('Control Board')->assertDontSee('Fan Motor');
    }

    public function test_part_bulk_csv_import(): void
    {
        MachineCategory::create(['category_name' => 'Chimney']);
        Unit::create(['unit_name' => 'Piece']);

        ob_start();
        $this->get(route('parts.import.sample'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->sendContent();
        $this->assertStringContainsString('part_name', ob_get_clean());

        $csv = "part_name,category,brand,start_date,unit,dealer_price,mrp,total_stock,has_amc,has_warranty,warranty_months,status\n"
            .'Bulk Part One,Chimney,,2026-01-01,Piece,100,150,20,Y,Y,12,active'."\n"
            .'Bulk Part Two,Unknown Category,,2026-01-01,Piece,100,150,20,N,N,,active'."\n";
        $file = UploadedFile::fake()->createWithContent('parts.csv', $csv);

        $this->post(route('parts.import.store'), ['file' => $file])
            ->assertRedirect(route('parts.index'))
            ->assertSessionHas('import_errors');
        $this->assertDatabaseHas('parts', ['part_name' => 'Bulk Part One', 'current_stock' => 20, 'has_amc' => true, 'has_warranty' => true, 'warranty_months' => 12]);
        $this->assertDatabaseMissing('parts', ['part_name' => 'Bulk Part Two']);
        $this->assertSame(1, Part::count());
    }
}
