<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_toggle_product_in_wishlist(): void
    {
        $category = Category::create(['name' => ['en' => 'Fashion'], 'slug' => 'fashion']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'WISH-PROD-01',
            'name' => ['en' => 'Denim Jacket'],
            'slug' => 'denim-jacket',
            'original_price' => 3000.00,
            'offer_price' => 2500.00,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        // 1. Add to wishlist
        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wishlist/toggle', ['product_id' => $product->id]);

        $response1->assertStatus(201)
            ->assertJsonFragment(['in_wishlist' => true]);

        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

        // 2. Toggle again (Remove from wishlist)
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wishlist/toggle', ['product_id' => $product->id]);

        $response2->assertStatus(200)
            ->assertJsonFragment(['in_wishlist' => false]);

        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }
}
