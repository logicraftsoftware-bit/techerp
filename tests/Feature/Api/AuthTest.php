<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100001',
            'password' => Hash::make('password123'),
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    public function test_login_succeeds_and_logs_the_ip(): void
    {
        $technician = $this->technician();

        $response = $this->postJson('/api/login', ['mobile' => '9111100001', 'password' => 'password123']);

        $response->assertOk()->assertJsonStructure(['token', 'technician' => ['id', 'name', 'mobile']]);
        $this->assertDatabaseHas('technician_login_logs', ['technician_id' => $technician->id]);
        $this->assertNotNull($technician->fresh()->last_login_at);
        $this->assertNotNull($technician->fresh()->last_login_ip);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->technician();

        $this->postJson('/api/login', ['mobile' => '9111100001', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    public function test_login_fails_for_inactive_technician(): void
    {
        $this->technician(['status' => 'inactive']);

        $this->postJson('/api/login', ['mobile' => '9111100001', 'password' => 'password123'])
            ->assertStatus(403);
    }

    public function test_logout_revokes_the_token(): void
    {
        $technician = $this->technician();
        Sanctum::actingAs($technician);

        $this->getJson('/api/me')->assertOk();
        $this->postJson('/api/logout')->assertOk();
    }

    public function test_me_returns_technician_profile(): void
    {
        $technician = $this->technician();
        Sanctum::actingAs($technician);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.mobile', '9111100001');
    }

    public function test_change_password_requires_current_password(): void
    {
        $technician = $this->technician();
        Sanctum::actingAs($technician);

        $this->putJson('/api/profile/password', ['current_password' => 'wrong', 'new_password' => 'newpassword1', 'new_password_confirmation' => 'newpassword1'])
            ->assertStatus(422);

        $this->putJson('/api/profile/password', ['current_password' => 'password123', 'new_password' => 'newpassword1', 'new_password_confirmation' => 'newpassword1'])
            ->assertOk();
        $this->assertTrue(Hash::check('newpassword1', $technician->fresh()->password));
    }

    public function test_deactivating_a_technician_from_the_admin_panel_revokes_their_token(): void
    {
        $technician = $this->technician();
        $token = $technician->createToken('mobile-app');
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);

        $admin = User::factory()->create();
        $this->seed(RolePermissionSeeder::class);
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $payload = ['name' => $technician->name, 'gender' => 'male', 'mobile' => $technician->mobile, 'joining_date' => '2026-01-01', 'employment_type' => 'full_time', 'status' => 'inactive', 'salary_structure_type' => 'fixed', 'salary_type' => 'monthly', 'monthly_salary' => 10000];
        $this->actingAs($admin)->put(route('technicians.update', $technician), $payload)->assertRedirect(route('technicians.index'));

        // Token revocation is asserted directly against the database rather than by
        // replaying the dead bearer token through a real request: Sanctum's guard falls
        // back to the session left behind by actingAs($admin) above once the token is
        // gone, which would make this assertion pass for the wrong reason.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }
}
