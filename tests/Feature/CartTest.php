<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_guest_can_add_product_to_cart(): void
    {
        $category = Category::create(['name' => ['en' => 'Gadgets'], 'slug' => 'gadgets']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'CART-PROD-01',
            'name' => ['en' => 'Wireless Mouse'],
            'slug' => 'wireless-mouse',
            'original_price' => 1000.00,
            'offer_price' => 800.00,
            'stock_quantity' => 10,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $sessionId = 'GUEST-SESSION-12345';

        $response = $this->withHeader('X-Session-ID', $sessionId)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['items_count' => 2])
            ->assertJsonFragment(['subtotal' => 1600]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 800.00,
            'total_price' => 1600.00,
        ]);
    }

    public function test_adding_quantity_exceeding_stock_fails(): void
    {
        $category = Category::create(['name' => ['en' => 'Gadgets'], 'slug' => 'gadgets']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'LOW-STOCK-01',
            'name' => ['en' => 'Limited Watch'],
            'slug' => 'limited-watch',
            'original_price' => 5000.00,
            'offer_price' => 4500.00,
            'stock_quantity' => 2,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Session-ID', 'GUEST-999')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);
    }

    public function test_user_can_merge_guest_cart_on_login(): void
    {
        $category = Category::create(['name' => ['en' => 'Books'], 'slug' => 'books']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'MERGE-PROD-01',
            'name' => ['en' => 'Novel'],
            'slug' => 'novel',
            'original_price' => 300.00,
            'offer_price' => 250.00,
            'stock_quantity' => 20,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $sessionId = 'GUEST-MERGE-SESSION';

        // Add to guest cart
        $this->withHeader('X-Session-ID', $sessionId)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 3,
            ]);

        $user = User::factory()->create();

        // Merge guest cart into user cart
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/merge', [
                'session_id' => $sessionId,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['user_id' => $user->id])
            ->assertJsonFragment(['items_count' => 3]);
    }
}
