<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_fetch_public_settings(): void
    {
        Setting::set('site_name', 'JSS Solutions', 'general');

        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/admin/settings', [
                'key' => 'site_name',
                'value' => 'New Name',
            ]);

        $response->assertStatus(403);
    }
}
