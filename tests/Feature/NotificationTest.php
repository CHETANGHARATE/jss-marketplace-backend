<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_fetch_and_read_notifications(): void
    {
        $user = User::factory()->create();

        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => 'order_status',
            'title' => 'Order Confirmed',
            'message' => 'Your order #ORD-1001 has been confirmed.',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonFragment(['unread_count' => 1])
            ->assertJsonFragment(['title' => 'Order Confirmed']);

        // Mark as read
        $readRes = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$notification->id}/read");
        $readRes->assertStatus(200);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
