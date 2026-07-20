<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_initiate_payment_and_receive_gateway_payload(): void
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'phone' => '+919876543210',
            'address_line_1' => 'Street 1',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-PAY-TEST-01',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments/initiate', [
            'order_id' => $order->id,
            'gateway' => 'razorpay',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['gateway' => 'razorpay'])
            ->assertJsonFragment(['currency' => 'INR']);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_verify_payment_signature_and_update_order(): void
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => 'Jane Doe',
            'phone' => '+919876543210',
            'address_line_1' => 'Street 2',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-VERIFY-01',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $payload = [
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'payload' => [
                'razorpay_order_id' => 'order_mock12345',
                'razorpay_payment_id' => 'pay_mock98765',
                'razorpay_signature' => 'valid_mock_signature',
            ]
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments/verify', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'captured']);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'captured',
            'transaction_id' => 'pay_mock98765',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
    }
}
