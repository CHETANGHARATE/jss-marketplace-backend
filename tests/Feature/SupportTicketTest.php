<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_customer_can_create_ticket_and_converse_with_admin(): void
    {
        $user = User::factory()->create();

        // Customer creates ticket
        $ticketRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/support/tickets', [
            'subject' => 'Payment Issue with Order',
            'category' => 'payment',
            'priority' => 'high',
            'message' => 'My payment went through but order says pending.',
        ]);

        $ticketRes->assertStatus(201)->assertJsonFragment(['category' => 'payment']);
        $ticketNumber = $ticketRes->json('data.ticket_number');
        $ticketId = $ticketRes->json('data.id');

        // Admin replies
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $replyRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/support/tickets/{$ticketId}/reply", [
            'message' => 'We have checked your transaction and confirmed the payment.',
        ]);

        $replyRes->assertStatus(201)->assertJsonFragment(['is_admin_reply' => true]);

        // Customer checks conversation history
        $showRes = $this->actingAs($user, 'sanctum')->getJson("/api/v1/support/tickets/{$ticketNumber}");
        $showRes->assertStatus(200);
        $this->assertCount(2, $showRes->json('data.messages'));
    }
}
