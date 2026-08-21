<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Central Notification Templates Table (Multi-Channel, Multi-Language, Dynamic Variables)
        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('template_key')->index(); // e.g. order_placed, order_shipped, price_drop, back_in_stock
                $table->string('event_name'); // Human-friendly event name
                $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp'])->default('in_app');
                $table->string('language', 10)->default('en'); // en, hi, mr
                $table->string('subject')->nullable(); // For email channel
                $table->text('body'); // Template body with {variable} placeholders
                $table->json('variables')->nullable(); // Available placeholders list
                $table->string('dlt_template_id')->nullable(); // For Indian DLT / MSG91 flow
                $table->string('whatsapp_template_name')->nullable(); // For WhatsApp Cloud API
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system_locked')->default(false); // Protect security-critical templates
                $table->timestamps();

                $table->unique(['template_key', 'channel', 'language'], 'notif_tpl_key_chan_lang_unique');
                $table->index(['channel', 'is_active']);
            });
        }

        // 2. User Notification Preferences Table
        if (!Schema::hasTable('user_notification_preferences')) {
            Schema::create('user_notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                
                // Channel level toggles
                $table->boolean('email_enabled')->default(true);
                $table->boolean('sms_enabled')->default(true);
                $table->boolean('whatsapp_enabled')->default(true);
                $table->boolean('in_app_enabled')->default(true);
                
                // Topic level toggles
                $table->boolean('order_updates')->default(true); // Always mandatory/exempt for security
                $table->boolean('price_alerts')->default(true);
                $table->boolean('stock_alerts')->default(true);
                $table->boolean('store_updates')->default(true);
                $table->boolean('promotions')->default(true);
                $table->boolean('abandoned_cart')->default(true);
                
                // Preferred Language
                $table->string('preferred_language', 10)->default('en');
                
                $table->timestamps();

                $table->unique('user_id');
            });
        }

        // 3. Central Notification Delivery Logs Table
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recipient_target'); // email, phone, or user_id
                $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp']);
                $table->string('event_key')->index();
                $table->string('template_key')->nullable();
                $table->string('idempotency_key')->unique()->nullable();
                $table->string('subject')->nullable();
                $table->text('message_content');
                $table->json('payload_data')->nullable();
                $table->string('provider')->default('system'); // msg91, whatsapp_cloud, smtp, in_app
                $table->string('provider_message_id')->nullable();
                $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
                $table->text('error_message')->nullable();
                $table->json('provider_response')->nullable();
                $table->unsignedSmallInteger('retry_count')->default(0);
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'channel']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // 4. Product Launch Subscriptions Table (Feature 123 - Coming Soon Products)
        if (!Schema::hasTable('product_launch_subscriptions')) {
            Schema::create('product_launch_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->enum('status', ['active', 'notified', 'cancelled'])->default('active');
                $table->timestamp('subscribed_at')->useCurrent();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
                $table->index(['product_id', 'status']);
            });
        }

        // 5. Abandoned Cart Reminders Tracking Table (Phase 5G)
        if (!Schema::hasTable('abandoned_cart_reminders')) {
            Schema::create('abandoned_cart_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('session_id')->nullable()->index();
                $table->decimal('cart_total', 12, 2)->default(0.00);
                $table->integer('item_count')->default(0);
                $table->json('cart_snapshot')->nullable();
                $table->unsignedTinyInteger('reminder_stage')->default(1); // Stage 1 (2h), Stage 2 (24h)
                $table->enum('status', ['pending', 'sent', 'converted', 'cancelled'])->default('pending');
                $table->timestamp('abandoned_at')->useCurrent();
                $table->timestamp('last_reminded_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        // 6. Scheduled & Recurring Notifications Table (Phase 5I)
        if (!Schema::hasTable('scheduled_notifications')) {
            Schema::create('scheduled_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('event_key');
                $table->enum('target_audience', ['all_users', 'verified_buyers', 'vendors', 'inactive_users', 'custom_list'])->default('all_users');
                $table->json('target_user_ids')->nullable();
                $table->json('channels'); // ["in_app", "email", "whatsapp", "sms"]
                $table->text('message');
                $table->string('action_url')->nullable();
                $table->timestamp('scheduled_for');
                $table->enum('recurrence', ['none', 'daily', 'weekly', 'monthly'])->default('none');
                $table->enum('status', ['scheduled', 'processing', 'completed', 'cancelled', 'failed'])->default('scheduled');
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('successful_deliveries')->default(0);
                $table->unsignedInteger('failed_deliveries')->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'scheduled_for']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
        Schema::dropIfExists('abandoned_cart_reminders');
        Schema::dropIfExists('product_launch_subscriptions');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('notification_templates');
    }
};
