<?php

namespace Tests\Feature;

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

    public function test_super_admin_can_create_user_with_role(): void
    {
        $role = Role::where('slug', 'manager')->firstOrFail();
        $this->actingAs($this->admin())->post('/users', [
            'name' => 'Service Manager', 'email' => 'manager@example.com', 'phone' => '9999999998',
            'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => [$role->id], 'is_active' => '1',
        ])->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'manager@example.com', 'is_active' => true]);
        $this->assertTrue(User::whereEmail('manager@example.com')->first()->hasRole('manager'));
    }

    public function test_unauthorized_user_cannot_open_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get('/users')->assertForbidden();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        // Uses a plain "admin" role account (not super-admin) since super-admin is now
        // fully locked out of the CRM's user-management screens (see tests below) and
        // would 404 before this self-delete guard is even reached.
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());
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
        $this->put("/users/{$admin->id}", ['name' => 'Renamed', 'email' => $admin->email, 'roles' => [Role::where('slug', 'manager')->value('id')], 'is_active' => '1'])->assertNotFound();
        $this->delete("/users/{$admin->id}")->assertNotFound();

        // Switch to a plain "admin" role actor for the checkbox-list check, since the
        // acting super-admin's own role badge ("Super Admin") legitimately shows in the
        // page header and would otherwise make a page-wide text assertion a false positive.
        $plainAdmin = User::factory()->create();
        $plainAdmin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());
        $this->actingAs($plainAdmin)->get('/users/create')->assertOk()->assertDontSee('Super Admin');

        $role = Role::where('slug', 'manager')->firstOrFail();
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->actingAs($plainAdmin)->post('/users', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'phone' => '9999999997',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'roles' => [$role->id, $superAdminRole->id], 'is_active' => '1',
        ])->assertSessionHasErrors('roles');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }
}
