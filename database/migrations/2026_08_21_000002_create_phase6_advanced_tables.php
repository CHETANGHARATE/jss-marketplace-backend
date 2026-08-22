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
        // 1. Add Phase 6 columns to products table (AR, 3D Models, Try-On, Visual Signatures)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_ar_enabled')) {
                $table->boolean('is_ar_enabled')->default(false)->after('is_trending');
            }
            if (!Schema::hasColumn('products', 'ar_model_glb')) {
                $table->string('ar_model_glb')->nullable()->after('is_ar_enabled');
            }
            if (!Schema::hasColumn('products', 'ar_model_usdz')) {
                $table->string('ar_model_usdz')->nullable()->after('ar_model_glb');
            }
            if (!Schema::hasColumn('products', 'is_try_on_enabled')) {
                $table->boolean('is_try_on_enabled')->default(false)->after('ar_model_usdz');
            }
            if (!Schema::hasColumn('products', 'try_on_category')) {
                $table->string('try_on_category', 50)->nullable()->after('is_try_on_enabled'); // apparel, eyewear, accessories
            }
            if (!Schema::hasColumn('products', 'image_signature')) {
                $table->json('image_signature')->nullable()->after('try_on_category');
            }
        });

        // 2. 360 Degree Product Image Sequence (Feature 159)
        if (!Schema::hasTable('product_360_media')) {
            Schema::create('product_360_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('frame_url');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['product_id', 'sort_order']);
            });
        }

        // 3. Live Shopping & Live Stream Sessions (Feature 161)
        if (!Schema::hasTable('live_sessions')) {
            Schema::create('live_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->string('stream_url')->nullable();
                $table->enum('status', ['scheduled', 'live', 'ended', 'cancelled'])->default('scheduled');
                $table->timestamp('scheduled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->unsignedInteger('viewers_count')->default(0);
                $table->unsignedInteger('likes_count')->default(0);
                $table->timestamps();

                $table->index(['status', 'scheduled_at']);
                $table->index('seller_id');
            });
        }

        // 4. Live Session Featured Products (Feature 161)
        if (!Schema::hasTable('live_session_products')) {
            Schema::create('live_session_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('live_session_id')->constrained('live_sessions')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->boolean('is_pinned')->default(false);
                $table->decimal('special_live_price', 12, 2)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['live_session_id', 'is_pinned']);
            });
        }

        // 5. Passkey Credentials for WebAuthn / FIDO2 (Feature 171)
        if (!Schema::hasTable('passkey_credentials')) {
            Schema::create('passkey_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('credential_id', 512)->unique();
                $table->text('public_key');
                $table->unsignedBigInteger('sign_count')->default(0);
                $table->json('transports')->nullable();
                $table->string('device_name')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        // 6. AI Shopping Assistant Conversations & History (Feature 57)
        if (!Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('session_id', 100)->index();
                $table->text('user_message');
                $table->text('assistant_message');
                $table->json('intent_data')->nullable();
                $table->json('recommended_product_ids')->nullable();
                $table->timestamps();

                $table->index(['session_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('passkey_credentials');
        Schema::dropIfExists('live_session_products');
        Schema::dropIfExists('live_sessions');
        Schema::dropIfExists('product_360_media');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_ar_enabled',
                'ar_model_glb',
                'ar_model_usdz',
                'is_try_on_enabled',
                'try_on_category',
                'image_signature',
            ]);
        });
    }
};
