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
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }
}
