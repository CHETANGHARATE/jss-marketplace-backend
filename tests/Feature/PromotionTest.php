<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_apply_valid_percentage_coupon(): void
    {
        Coupon::create([
            'code' => 'WELCOME10',
            'name' => 'Welcome 10% Discount',
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
            'min_order_amount' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/promotions/coupons/apply', [
            'code' => 'WELCOME10',
            'cart_total' => 500.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['discount_amount' => 50.00])
            ->assertJsonFragment(['final_total' => 450.00]);
    }

    public function test_admin_can_create_flash_sale_campaign(): void
    {
        $category = Category::create(['name' => ['en' => 'Gadgets'], 'slug' => 'gadgets']);
        $product = Product::create([
            'name' => 'Smart Watch Pro',
            'slug' => 'smart-watch-pro',
            'category_id' => $category->id,
            'original_price' => 200.00,
            'stock_quantity' => 100,
            'status' => 'published',
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/flash-sales', [
            'title' => 'Monsoon Super Sale',
            'discount_percentage' => 25.00,
            'starts_at' => now()->toIso8601String(),
            'ends_at' => now()->addDays(2)->toIso8601String(),
            'products' => [
                ['product_id' => $product->id, 'quantity_limit' => 30]
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Monsoon Super Sale'])
            ->assertJsonFragment(['flash_price' => 150.00]);
    }
}
