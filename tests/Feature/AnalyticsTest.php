<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_view_dashboard_overview_metrics(): void
    {
        $user = User::factory()->create();
        $address = Address::create(['user_id' => $user->id, 'full_name' => 'A', 'phone' => '1', 'address_line_1' => 'A', 'city' => 'C', 'state' => 'S', 'pincode' => '1']);
        Order::create([
            'order_number' => 'ORD-ANALYTICS-01',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 1500,
            'total_amount' => 1500,
            'status' => 'confirmed',
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/analytics/overview');

        $response->assertStatus(200)
            ->assertJsonFragment(['total_sales' => 1500])
            ->assertJsonFragment(['total_orders' => 1]);
    }

    public function test_admin_can_export_sales_report_as_csv(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')->get('/api/v1/admin/reports/sales/export?format=csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('Order Number,Customer Name', $response->getContent());
    }
}
