<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_only_verified_purchaser_can_submit_review(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Smartphone', 'slug' => 'smartphone', 'original_price' => 500, 'stock_quantity' => 10]);

        // Attempt submission without purchase
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Great phone, really love it!',
        ]);

        $response->assertStatus(400)->assertJsonFragment(['success' => false]);

        // Create delivered order for user
        $address = Address::create(['user_id' => $user->id, 'full_name' => 'John', 'phone' => '1', 'address_line_1' => 'A', 'city' => 'C', 'state' => 'S', 'pincode' => '1']);
        $order = Order::create([
            'order_number' => 'ORD-REV-01',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 500,
            'total_amount' => 500,
            'status' => 'delivered',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Smartphone', 'unit_price' => 500, 'quantity' => 1, 'subtotal' => 500]);

        // Submit review as verified purchaser
        $validRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Awesome smartphone after purchase!',
        ]);

        $validRes->assertStatus(201)->assertJsonFragment(['status' => 'pending']);
    }

    public function test_admin_moderation_approves_review_and_updates_product_rating(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => ['en' => 'Books'], 'slug' => 'books']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Laravel Guide', 'slug' => 'laravel-guide', 'original_price' => 100, 'stock_quantity' => 20]);

        $address = Address::create(['user_id' => $user->id, 'full_name' => 'Jane', 'phone' => '1', 'address_line_1' => 'A', 'city' => 'C', 'state' => 'S', 'pincode' => '1']);
        $order = Order::create([
            'order_number' => 'ORD-REV-02',
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshotArray(),
            'billing_address_snapshot' => $address->toSnapshotArray(),
            'subtotal' => 100,
            'total_amount' => 100,
            'status' => 'delivered',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Laravel Guide', 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100]);

        $revRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reviews', [
            'product_id' => $product->id,
            'rating' => 4,
            'comment' => 'Very informative book!',
        ]);
        $reviewId = $revRes->json('data.id');

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        // Admin approves review
        $modRes = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/reviews/{$reviewId}/moderate", [
            'status' => 'approved',
        ]);

        $modRes->assertStatus(200)->assertJsonFragment(['status' => 'approved']);

        // Check product average rating updated
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'rating' => 4.00,
            'reviews_count' => 1,
        ]);
    }
}
