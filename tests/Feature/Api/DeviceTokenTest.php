<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    private function technician(array $overrides = []): Technician
    {
        return Technician::create(array_merge([
            'name' => 'Field Tech',
            'mobile' => '9111100040',
            'joining_date' => '2026-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary_type' => 'monthly',
        ], $overrides));
    }

    public function test_technician_can_register_and_remove_a_device_token(): void
    {
        $technician = $this->technician();
        Sanctum::actingAs($technician);

        $this->postJson('/api/device-tokens', ['token' => 'fcm-token-123', 'platform' => 'android'])->assertOk();
        $this->assertDatabaseHas('device_tokens', ['technician_id' => $technician->id, 'token' => 'fcm-token-123', 'platform' => 'android']);

        $this->deleteJson('/api/device-tokens', ['token' => 'fcm-token-123'])->assertOk();
        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-token-123']);
    }

    public function test_re_registering_a_token_moves_it_to_the_new_owner(): void
    {
        $first = $this->technician(['mobile' => '9111100041']);
        $second = $this->technician(['mobile' => '9111100042']);
        DeviceToken::create(['technician_id' => $first->id, 'token' => 'shared-device', 'platform' => 'android']);

        Sanctum::actingAs($second);
        $this->postJson('/api/device-tokens', ['token' => 'shared-device', 'platform' => 'android'])->assertOk();

        $this->assertSame(1, DeviceToken::where('token', 'shared-device')->count());
        $this->assertDatabaseHas('device_tokens', ['token' => 'shared-device', 'technician_id' => $second->id]);
    }
}
