<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\ShippingSeeder::class);
    }

    public function test_anyone_can_calculate_shipping_rate_by_pincode(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'pincode' => '400093',
            'cart_total' => 500.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['zone_name' => 'West India Zone'])
            ->assertJsonFragment(['shipping_cost' => 60]);
    }

    public function test_admin_can_create_shipment_and_generate_awb(): void
    {
        $user = User::factory()->create();
        $courier = Courier::where('code', 'DELHIVERY')->first();

        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => 'Test User',
            'phone' => '+919876543210',
            'address_line_1' => 'Street 1',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SHP-TEST-01',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'status' => 'confirmed',
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/shipments/create', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'label_created']);

        $this->assertDatabaseHas('shipments', ['order_id' => $order->id, 'courier_id' => $courier->id]);
    }

    public function test_updating_shipment_status_updates_order_status(): void
    {
        $user = User::factory()->create();
        $courier = Courier::where('code', 'DELHIVERY')->first();

        $address = Address::create(['user_id' => $user->id, 'full_name' => 'U', 'phone' => '1', 'address_line_1' => 'A', 'city' => 'C', 'state' => 'S', 'pincode' => '1']);
        $order = Order::create([
            'order_number' => 'ORD-DELIVERED-01',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 500,
            'total_amount' => 500,
            'status' => 'confirmed',
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $createRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/shipments/create', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
        ]);
        $shipmentId = $createRes->json('data.id');

        // Update status to delivered
        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/shipments/{$shipmentId}/status", [
            'status' => 'delivered',
            'location' => 'Customer Address',
            'description' => 'Delivered to recipient',
        ]);

        $response->assertStatus(200)->assertJsonFragment(['status' => 'delivered']);

        // Verify order status updated to delivered via listener
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
    }
}
