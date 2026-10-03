<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VendorStore;
use App\Services\VendorStoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_seller_applicant_stays_in_pending_approval_and_cannot_access_operational_vendor_endpoints(): void
    {
        $applicant = User::factory()->create([
            'role' => UserRole::CUSTOMER,
        ]);

        // 1. Submit store registration
        $response = $this->actingAs($applicant, 'sanctum')->postJson('/api/v1/vendor/store', [
            'store_name' => 'Pending Tech Store',
            'store_email' => 'tech@pending.com',
            'store_phone' => '+919996669884',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('pending', $response->json('data.store.status'));
        $this->assertEquals('pending', $response->json('data.store.kyc_status'));

        // Applicant must NOT be given seller role immediately
        $applicant->refresh();
        $this->assertNotEquals(UserRole::SELLER, $applicant->role);

        // 2. Public store info / application status MUST be accessible to pending applicant
        $storeStatusRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/store');
        $storeStatusRes->assertStatus(200)
            ->assertJsonFragment(['status' => 'pending'])
            ->assertJsonFragment(['kyc_status' => 'pending']);

        $appStatusRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/application-status');
        $appStatusRes->assertStatus(200)
            ->assertJsonFragment(['status' => 'pending']);

        // 3. Operational vendor endpoints MUST be blocked with HTTP 403
        $dashboardRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/dashboard');
        $dashboardRes->assertStatus(403)
            ->assertJsonFragment(['vendor_status' => 'pending']);

        $productsRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/products');
        $productsRes->assertStatus(403);

        $ordersRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/orders');
        $ordersRes->assertStatus(403);

        $walletRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/wallet');
        $walletRes->assertStatus(403);
    }

    public function test_admin_approving_store_activates_vendor_and_grants_access(): void
    {
        $applicant = User::factory()->create([
            'role' => UserRole::CUSTOMER,
        ]);
        $storeService = app(VendorStoreService::class);
        $store = $storeService->registerStore($applicant, ['store_name' => 'Growth Store']);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRoleSafely(UserRole::ADMIN->value);

        // Admin approves store
        $approveRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/vendor/stores/{$store->id}/approve");
        $approveRes->assertStatus(200)
            ->assertJsonFragment(['status' => 'active'])
            ->assertJsonFragment(['kyc_status' => 'verified']);

        // Applicant now has seller role
        $applicant->refresh();
        $this->assertEquals(UserRole::SELLER, $applicant->role);

        // Operational endpoints are now accessible
        $dashboardRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/dashboard');
        $dashboardRes->assertStatus(200);

        // Notification was dispatched
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $applicant->id,
            'type' => 'seller_approved',
        ]);
    }

    public function test_admin_rejecting_store_blocks_access(): void
    {
        $applicant = User::factory()->create();
        $storeService = app(VendorStoreService::class);
        $store = $storeService->registerStore($applicant, ['store_name' => 'Declined Store']);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRoleSafely(UserRole::ADMIN->value);

        // Reject store
        $rejectRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/vendor/stores/{$store->id}/reject", [
            'reason' => 'Invalid GSTIN document.',
        ]);
        $rejectRes->assertStatus(200)
            ->assertJsonFragment(['kyc_status' => 'rejected']);

        // Operational endpoints are blocked with 403
        $dashboardRes = $this->actingAs($applicant, 'sanctum')->getJson('/api/v1/vendor/dashboard');
        $dashboardRes->assertStatus(403)
            ->assertJsonFragment(['vendor_status' => 'rejected']);

        // Notification was dispatched
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $applicant->id,
            'type' => 'seller_rejected',
        ]);
    }
}
