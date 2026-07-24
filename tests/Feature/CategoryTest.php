<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_anyone_can_browse_categories_tree(): void
    {
        $parent = Category::create([
            'name' => ['en' => 'Fashion', 'hi' => 'फैशन'],
            'slug' => 'fashion',
            'is_active' => true,
        ]);

        Category::create([
            'parent_id' => $parent->id,
            'name' => ['en' => "Men's Wear"],
            'slug' => 'mens-wear',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'fashion'])
            ->assertJsonFragment(['slug' => 'mens-wear']);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $payload = [
            'name' => ['en' => 'Electronics', 'hi' => 'इलेक्ट्रॉनिक्स'],
            'slug' => 'electronics',
            'icon' => 'Laptop',
            'is_featured' => true,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['slug' => 'electronics']);

        $this->assertDatabaseHas('categories', ['slug' => 'electronics']);
    }

    public function test_customer_cannot_create_category(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/admin/categories', [
                'name' => ['en' => 'Unauthorized Category'],
                'slug' => 'unauthorized-category',
            ]);

        $response->assertStatus(403);
    }
}
