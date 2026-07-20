<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\VendorCommissionService;
use App\Services\VendorStoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_register_vendor_store_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/vendor/store', [
            'store_name' => 'Apex Electronics Store',
            'store_email' => 'contact@apexelectronics.com',
            'store_phone' => '+919876543210',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['store_name' => 'Apex Electronics Store'])
            ->assertJsonFragment(['kyc_status' => 'pending']);

        $this->assertDatabaseHas('vendor_stores', ['user_id' => $user->id, 'store_name' => 'Apex Electronics Store']);
        $this->assertDatabaseHas('vendor_wallets', ['balance' => 0.00]);
    }

    public function test_admin_can_verify_vendor_kyc_and_activate_store(): void
    {
        $user = User::factory()->create();
        $storeService = app(VendorStoreService::class);
        $store = $storeService->registerStore($user, ['store_name' => 'Test Vendor Store']);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/vendor/stores/{$store->id}/kyc", [
            'kyc_status' => 'verified',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['kyc_status' => 'verified'])
            ->assertJsonFragment(['status' => 'active']);
    }

    public function test_vendor_can_request_settlement_and_admin_process(): void
    {
        $user = User::factory()->create();
        $storeService = app(VendorStoreService::class);
        $store = $storeService->registerStore($user, ['store_name' => 'Payout Store']);
        $storeService->verifyKYC($store, 'verified');

        // Manually top up wallet balance for test
        $store->wallet->increment('balance', 500.00);

        // Vendor requests settlement
        $settleRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/vendor/settlements/request', [
            'amount' => 300.00,
            'bank_details' => [
                'account_number' => '9876543210',
                'ifsc_code' => 'SBIN0001234',
                'bank_name' => 'State Bank of India',
                'account_holder' => 'Payout Store Proprietor',
            ]
        ]);

        $settleRes->assertStatus(201)->assertJsonFragment(['status' => 'requested']);
        $settlementId = $settleRes->json('data.id');

        // Verify balance deducted
        $this->assertEquals(200.00, $store->wallet->fresh()->balance);

        // Admin approves settlement
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $adminRes = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/vendor/settlements/{$settlementId}/process", [
            'status' => 'paid',
            'reference_number' => 'UTR98127398127',
        ]);

        $adminRes->assertStatus(200)->assertJsonFragment(['status' => 'paid']);
        $this->assertEquals(300.00, $store->wallet->fresh()->total_withdrawn);
    }
}
