<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_process_checkout_and_deduct_inventory(): void
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

        $category = Category::create(['name' => ['en' => 'Gadgets'], 'slug' => 'gadgets']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ORDER-TEST-01',
            'name' => ['en' => 'Headphones'],
            'slug' => 'headphones',
            'original_price' => 2000.00,
            'offer_price' => 1500.00,
            'stock_quantity' => 10,
            'status' => 'approved',
            'is_active' => true,
        ]);

        // 1. Add item to user cart
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // 2. Process Checkout
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/checkout/process', [
            'shipping_address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'pending'])
            ->assertJsonFragment(['total' => 3000]);

        // Verify Order Record created
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'subtotal' => 3000.00]);

        // Verify product inventory stock deducted (10 - 2 = 8)
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 8]);
    }

    public function test_user_can_cancel_pending_order_and_restore_stock(): void
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

        $category = Category::create(['name' => ['en' => 'Books'], 'slug' => 'books']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'CANCEL-TEST-01',
            'name' => ['en' => 'Story Book'],
            'slug' => 'story-book',
            'original_price' => 500.00,
            'offer_price' => 400.00,
            'stock_quantity' => 5,
            'status' => 'approved',
            'is_active' => true,
        ]);

        // Add to cart & checkout
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);
        $checkoutRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/checkout/process', ['shipping_address_id' => $address->id]);
        $orderNumber = $checkoutRes->json('data.order_number');

        // Cancel order
        $cancelRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$orderNumber}/cancel", [
            'reason' => 'Ordered by mistake',
        ]);

        $cancelRes->assertStatus(200)->assertJsonFragment(['status' => 'cancelled']);

        // Verify stock restored (back to 5)
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5]);
    }
}
