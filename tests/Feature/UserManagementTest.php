<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $user;
    }

    // Permissions are per-user only (see [[permission-model-per-user]]) — a role alone
    // grants nothing. Tests that exercise a specific ability grant it directly here.
    private function grant(User $user, string ...$slugs): User
    {
        $user->permissions()->attach(Permission::whereIn('slug', $slugs)->pluck('id'));

        return $user;
    }

    // Users now carry a Technician-like employee profile (see [[user-employee-profile]]),
    // so gender/joining_date/employment_type/employment_status/salary_type are required
    // on every create/update submission.
    private function baseUserData(): array
    {
        return ['gender' => 'male', 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'employment_status' => 'active', 'salary_type' => 'monthly', 'monthly_salary' => 30000];
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $role = Role::where('slug', 'manager')->firstOrFail();
        $this->actingAs($this->admin())->post('/users', $this->baseUserData() + [
            'name' => 'Service Manager', 'email' => 'manager@example.com', 'phone' => '9999999998',
            'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => [$role->id], 'is_active' => '1',
        ])->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'manager@example.com', 'is_active' => true]);
        $created = User::whereEmail('manager@example.com')->first();
        $this->assertTrue($created->hasRole('manager'));
        $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{6}$/', $created->employee_code);
        $this->assertSame('30000.00', $created->monthly_salary);
    }

    public function test_unauthorized_user_cannot_open_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get('/users')->assertForbidden();
    }

    public function test_user_without_permission_is_forbidden_even_with_a_role(): void
    {
        // Holding the "admin" role alone (with no direct permission grant) must not be
        // enough — access is per-user now, not inherited from the role.
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());
        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        // Uses a plain account granted the users.delete permission directly (not
        // super-admin) since super-admin is now fully locked out of the CRM's
        // user-management screens (see tests below) and would 404 before this
        // self-delete guard is even reached.
        $user = $this->grant(User::factory()->create(), 'users.delete');
        $this->actingAs($user)->delete("/users/{$user->id}")->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_super_admin_is_hidden_and_locked_from_user_management(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->get('/users')->assertOk()->assertDontSee($admin->email);
        $this->get("/users/{$admin->id}")->assertNotFound();
        $this->get("/users/{$admin->id}/edit")->assertNotFound();
        $this->put("/users/{$admin->id}", $this->baseUserData() + ['name' => 'Renamed', 'email' => $admin->email, 'roles' => [Role::where('slug', 'manager')->value('id')], 'is_active' => '1'])->assertNotFound();
        $this->delete("/users/{$admin->id}")->assertNotFound();

        // Switch to a plain, directly-permitted actor for the checkbox-list check, since
        // the acting super-admin's own role badge ("Super Admin") legitimately shows in
        // the page header and would otherwise make a page-wide text assertion a false positive.
        $plainAdmin = $this->grant(User::factory()->create(), 'users.create');
        $this->actingAs($plainAdmin)->get('/users/create')->assertOk()->assertDontSee('Super Admin');

        $role = Role::where('slug', 'manager')->firstOrFail();
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->actingAs($plainAdmin)->post('/users', $this->baseUserData() + [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'phone' => '9999999997',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'roles' => [$role->id, $superAdminRole->id], 'is_active' => '1',
        ])->assertSessionHasErrors('roles');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_super_admin_can_grant_and_revoke_permissions_for_a_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('users.permissions.edit', $target))
            ->assertOk()
            ->assertSee('Customers');

        $this->actingAs($this->admin())
            ->put(route('users.permissions.update', $target), ['permissions' => ['customers' => ['view' => '1', 'create' => '1']]])
            ->assertRedirect(route('users.index'));

        $this->assertTrue($target->fresh()->hasPermission('customers.view'));
        $this->assertTrue($target->fresh()->hasPermission('customers.create'));
        $this->assertFalse($target->fresh()->hasPermission('customers.delete'));

        $this->actingAs($this->admin())
            ->put(route('users.permissions.update', $target), ['permissions' => []])
            ->assertRedirect(route('users.index'));
        $this->assertFalse($target->fresh()->hasPermission('customers.view'));
    }

    public function test_super_admin_permission_page_is_inaccessible_for_a_super_admin_target(): void
    {
        $otherAdmin = $this->admin();
        $this->actingAs($this->admin())->get(route('users.permissions.edit', $otherAdmin))->assertNotFound();
    }
}
