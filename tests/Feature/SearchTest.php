<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_perform_advanced_search_with_facets(): void
    {
        $category = Category::create(['name' => ['en' => 'Laptops'], 'slug' => 'laptops']);
        Product::create([
            'name' => 'MacBook Pro M3',
            'slug' => 'macbook-pro-m3',
            'category_id' => $category->id,
            'original_price' => 1999.00,
            'stock_quantity' => 10,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/search?q=MacBook');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'MacBook Pro M3'])
            ->assertJsonStructure(['data' => ['products', 'facets' => ['price_range', 'categories', 'brands']]]);

        $this->assertDatabaseHas('search_logs', ['query' => 'MacBook', 'results_count' => 1]);
    }

    public function test_user_can_get_autocomplete_suggestions(): void
    {
        $category = Category::create(['name' => ['en' => 'Audio'], 'slug' => 'audio']);
        Product::create([
            'name' => 'Sony Wireless Headphones',
            'slug' => 'sony-headphones',
            'category_id' => $category->id,
            'original_price' => 299.00,
            'stock_quantity' => 20,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/search/autocomplete?q=Sony');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Sony Wireless Headphones']);
    }

    public function test_admin_can_view_search_analytics(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/search/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['popular_queries', 'zero_result_queries', 'recent_logs']]);
    }
}
