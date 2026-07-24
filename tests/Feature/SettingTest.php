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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_anyone_can_fetch_public_settings(): void
    {
        Setting::set('site_name', 'JSS Solutions', 'general', true);
        Setting::set('razorpay_secret', 'secret_val_123', 'payment', false);

        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonFragment(['key' => 'site_name'])
            ->assertJsonMissing(['key' => 'razorpay_secret']);
    }

    public function test_admin_can_fetch_all_settings_including_private(): void
    {
        Setting::set('site_name', 'JSS Solutions', 'general', true);
        Setting::set('razorpay_secret', 'secret_val_123', 'payment', false);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/settings?all=true');

        $response->assertStatus(200)
            ->assertJsonFragment(['key' => 'site_name'])
            ->assertJsonFragment(['key' => 'razorpay_secret']);
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
