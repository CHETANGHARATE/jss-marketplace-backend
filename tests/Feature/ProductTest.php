<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_anyone_can_browse_approved_products(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Electronics'],
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'TEST-PROD-01',
            'name' => ['en' => 'Test Smartphone'],
            'slug' => 'test-smartphone',
            'original_price' => 10000.00,
            'offer_price' => 8000.00,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'test-smartphone'])
            ->assertJsonFragment(['discountPercent' => 20]);
    }

    public function test_filter_products_by_category_and_price(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Fashion'],
            'slug' => 'fashion',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'TEST-CHEAP-01',
            'name' => ['en' => 'Cheap Shirt'],
            'slug' => 'cheap-shirt',
            'original_price' => 500.00,
            'offer_price' => 400.00,
            'status' => 'approved',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'TEST-EXP-01',
            'name' => ['en' => 'Luxury Jacket'],
            'slug' => 'luxury-jacket',
            'original_price' => 5000.00,
            'offer_price' => 4500.00,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/products?category=fashion&max_price=1000');

        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'cheap-shirt'])
            ->assertJsonMissing(['slug' => 'luxury-jacket']);
    }

    public function test_admin_can_create_product_atomically(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Electronics'],
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $payload = [
            'category_id' => $category->id,
            'sku' => 'ADMIN-PROD-99',
            'name' => ['en' => 'Admin Test Laptop'],
            'slug' => 'admin-test-laptop',
            'original_price' => 50000.00,
            'offer_price' => 45000.00,
            'status' => 'approved',
            'specifications' => [
                ['key' => 'RAM', 'value' => '16GB'],
                ['key' => 'Processor', 'value' => 'Intel i7'],
            ],
            'tags' => ['laptop', 'intel', 'admin'],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/products', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['slug' => 'admin-test-laptop']);

        $this->assertDatabaseHas('products', ['sku' => 'ADMIN-PROD-99']);
        $this->assertDatabaseHas('product_specifications', ['spec_key' => 'RAM', 'spec_value' => '16GB']);
    }

    public function test_admin_can_update_product_status(): void
    {
        $category = Category::create(['name' => ['en' => 'Books'], 'slug' => 'books']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'PEND-PROD-01',
            'name' => ['en' => 'Pending Book'],
            'slug' => 'pending-book',
            'original_price' => 200.00,
            'offer_price' => 200.00,
            'status' => 'pending_approval',
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/products/{$product->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'approved']);
    }
}
