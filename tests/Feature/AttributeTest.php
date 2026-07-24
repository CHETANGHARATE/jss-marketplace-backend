<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_anyone_can_fetch_attributes(): void
    {
        $attribute = Attribute::create([
            'name' => ['en' => 'Size'],
            'code' => 'size',
            'type' => 'select',
            'is_filterable' => true,
        ]);

        $attribute->values()->create(['value' => 'XL']);

        $response = $this->getJson('/api/v1/attributes');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'size'])
            ->assertJsonFragment(['value' => 'XL']);
    }

    public function test_admin_can_create_attribute_with_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $payload = [
            'name' => ['en' => 'Color', 'hi' => 'रंग'],
            'code' => 'color',
            'type' => 'color_picker',
            'values' => [
                ['value' => 'Red', 'color_code' => '#FF0000'],
                ['value' => 'Blue', 'color_code' => '#0000FF'],
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/attributes', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'color']);

        $this->assertDatabaseHas('attributes', ['code' => 'color']);
        $this->assertDatabaseHas('attribute_values', ['value' => 'Red', 'color_code' => '#FF0000']);
    }
}
