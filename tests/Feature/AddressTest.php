<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_add_and_list_saved_addresses(): void
    {
        $user = User::factory()->create();

        $payload = [
            'type' => 'shipping',
            'full_name' => 'John Doe',
            'phone' => '+919876543210',
            'address_line_1' => 'Flat 101, Sunshine Heights',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'is_default' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/addresses', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['full_name' => 'John Doe']);

        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'pincode' => '400001']);

        // Fetch addresses list
        $listResponse = $this->actingAs($user, 'sanctum')->getJson('/api/v1/addresses');
        $listResponse->assertStatus(200)->assertJsonFragment(['city' => 'Mumbai']);
    }
}
