<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_anyone_can_view_brands_list(): void
    {
        Brand::create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/brands');

        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'samsung']);
    }

    public function test_admin_can_create_brand(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/brands', [
                'name' => 'Nike',
                'slug' => 'nike',
                'is_featured' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['slug' => 'nike']);

        $this->assertDatabaseHas('brands', ['slug' => 'nike']);
    }
}
