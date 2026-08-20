<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Phase 4 B2B / Wholesale / Business Marketplace Engine.
     */
    public function up(): void
    {
        // 1. Business Accounts (Features 92 & 93)
        if (!Schema::hasTable('business_accounts')) {
            Schema::create('business_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('legal_business_name');
                $table->string('trade_name')->nullable();
                $table->string('business_type')->default('Private Limited'); // Sole Proprietorship, Partnership, LLP, Private Limited, MSME, etc.
                $table->string('gstin')->nullable()->index();
                $table->string('pan')->nullable()->index();
                $table->text('registered_address');
                $table->text('billing_address')->nullable();
                $table->text('shipping_address')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('pincode', 10)->nullable();
                $table->string('contact_person');
                $table->string('business_email');
                $table->string('business_phone', 20);
                $table->string('website')->nullable();
                $table->string('annual_turnover')->nullable();
                $table->json('documents')->nullable(); // GST Certificate, PAN, MSME, Business Proof URLs
                $table->enum('status', ['draft', 'submitted', 'under_review', 'verified', 'rejected', 'changes_required'])->default('submitted')->index();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add Wholesale columns to products table (Features 50 & 52)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_wholesale_enabled')) {
                $table->boolean('is_wholesale_enabled')->default(false)->after('offer_price')->index();
            }
            if (!Schema::hasColumn('products', 'wholesale_moq')) {
                $table->integer('wholesale_moq')->default(1)->after('is_wholesale_enabled');
            }
        });

        // 3. Product Price Tiers (Feature 50)
        if (!Schema::hasTable('product_price_tiers')) {
            Schema::create('product_price_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('min_quantity');
                $table->integer('max_quantity')->nullable(); // null means infinity / unbounded upper tier
                $table->decimal('unit_price', 12, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['product_id', 'is_active']);
            });
        }

        // 4. RFQs & RFQ Items (Features 51 & 82)
        if (!Schema::hasTable('rfqs')) {
            Schema::create('rfqs', function (Blueprint $table) {
                $table->id();
                $table->string('rfq_number')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('title');
                $table->text('description');
                $table->integer('quantity');
                $table->decimal('target_unit_price', 12, 2)->nullable();
                $table->string('delivery_location')->nullable();
                $table->string('delivery_pincode', 10)->nullable();
                $table->date('required_delivery_date')->nullable();
                $table->json('attachments')->nullable();
                $table->enum('status', ['submitted', 'quotation_received', 'negotiation', 'accepted', 'rejected', 'expired', 'cancelled', 'converted_to_po'])->default('submitted')->index();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rfq_items')) {
            Schema::create('rfq_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('product_name');
                $table->text('specifications')->nullable();
                $table->integer('quantity');
                $table->decimal('target_price', 12, 2)->nullable();
                $table->timestamps();
            });
        }

        // 5. Quotations (Feature 83)
        if (!Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table) {
                $table->id();
                $table->string('quotation_number')->unique();
                $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('unit_price', 12, 2);
                $table->integer('quantity');
                $table->integer('moq')->default(1);
                $table->integer('lead_time_days')->default(7);
                $table->decimal('shipping_cost', 12, 2)->default(0.00);
                $table->decimal('tax_amount', 12, 2)->default(0.00);
                $table->decimal('total_amount', 12, 2);
                $table->date('valid_until')->nullable();
                $table->text('seller_notes')->nullable();
                $table->json('attachments')->nullable();
                $table->enum('status', ['submitted', 'accepted', 'rejected', 'countered', 'expired', 'withdrawn'])->default('submitted')->index();
                $table->timestamps();

                $table->index(['rfq_id', 'seller_id']);
            });
        }

        // 6. Quotation Negotiations / Counter Offers (Phase 4F)
        if (!Schema::hasTable('quotation_negotiations')) {
            Schema::create('quotation_negotiations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('actor_type', ['buyer', 'seller'])->default('buyer');
                $table->decimal('offer_price', 12, 2);
                $table->integer('quantity')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->default('pending'); // pending, accepted, rejected, countered
                $table->timestamps();
            });
        }

        // 7. Purchase Orders & Items (Feature 88)
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('po_number')->unique();
                $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
                $table->foreignId('rfq_id')->nullable()->constrained('rfqs')->nullOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('subtotal', 12, 2);
                $table->decimal('tax_amount', 12, 2)->default(0.00);
                $table->decimal('shipping_amount', 12, 2)->default(0.00);
                $table->decimal('total_amount', 12, 2);
                $table->string('payment_terms')->default('Net 30');
                $table->string('delivery_terms')->default('Door Delivery');
                $table->json('billing_address')->nullable();
                $table->json('shipping_address')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'issued', 'accepted', 'rejected', 'partially_fulfilled', 'fulfilled', 'cancelled'])->default('issued')->index();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('product_name');
                $table->string('sku')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('tax_percent', 5, 2)->default(18.00);
                $table->decimal('total_price', 12, 2);
                $table->timestamps();
            });
        }

        // 8. Proforma Invoices (Feature 95)
        if (!Schema::hasTable('proforma_invoices')) {
            Schema::create('proforma_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('proforma_number')->unique();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('subtotal', 12, 2);
                $table->decimal('tax_amount', 12, 2)->default(0.00);
                $table->decimal('shipping_amount', 12, 2)->default(0.00);
                $table->decimal('total_amount', 12, 2);
                $table->json('buyer_details')->nullable();
                $table->json('seller_details')->nullable();
                $table->json('items_snapshot')->nullable();
                $table->text('payment_instructions')->nullable();
                $table->date('valid_until')->nullable();
                $table->enum('status', ['generated', 'sent', 'paid', 'expired', 'converted_to_tax_invoice'])->default('generated')->index();
                $table->timestamps();
            });
        }

        // 9. Business Credit Accounts & Transactions (Feature 94)
        if (!Schema::hasTable('business_credit_accounts')) {
            Schema::create('business_credit_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->decimal('credit_limit', 14, 2)->default(0.00);
                $table->decimal('available_credit', 14, 2)->default(0.00);
                $table->decimal('used_credit', 14, 2)->default(0.00);
                $table->integer('repayment_due_days')->default(30);
                $table->enum('status', ['inactive', 'pending', 'active', 'suspended'])->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('business_credit_transactions')) {
            Schema::create('business_credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_credit_account_id')->constrained('business_credit_accounts')->cascadeOnDelete();
                $table->enum('type', ['credit_assigned', 'order_deduction', 'repayment', 'credit_refund', 'adjustment'])->index();
                $table->decimal('amount', 14, 2);
                $table->decimal('balance_after', 14, 2);
                $table->string('reference_type')->nullable(); // order, repayment_receipt, admin_adjustment
                $table->string('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 10. Buyer Requirements & Seller Bids (Features 86 & 87)
        if (!Schema::hasTable('buyer_requirements')) {
            Schema::create('buyer_requirements', function (Blueprint $table) {
                $table->id();
                $table->string('requirement_number')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->string('title');
                $table->text('description');
                $table->integer('quantity');
                $table->decimal('target_price', 12, 2)->nullable();
                $table->string('delivery_pincode', 10)->nullable();
                $table->date('required_date')->nullable();
                $table->json('attachments')->nullable();
                $table->enum('status', ['published', 'closed', 'expired'])->default('published')->index();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('seller_bids')) {
            Schema::create('seller_bids', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_requirement_id')->constrained('buyer_requirements')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('bid_unit_price', 12, 2);
                $table->integer('moq')->default(1);
                $table->integer('lead_time_days')->default(7);
                $table->decimal('shipping_cost', 12, 2)->default(0.00);
                $table->text('message')->nullable();
                $table->enum('status', ['submitted', 'accepted', 'rejected'])->default('submitted')->index();
                $table->timestamps();

                $table->index(['buyer_requirement_id', 'seller_id']);
            });
        }

        // 11. Sample Requests (Features 84 & 85)
        if (!Schema::hasTable('sample_requests')) {
            Schema::create('sample_requests', function (Blueprint $table) {
                $table->id();
                $table->string('sample_request_number')->unique();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->integer('quantity')->default(1);
                $table->decimal('sample_price', 12, 2)->default(0.00);
                $table->json('shipping_address')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['requested', 'approved', 'shipped', 'delivered', 'rejected'])->default('requested')->index();
                $table->string('courier_name')->nullable();
                $table->string('tracking_number')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_requests');
        Schema::dropIfExists('seller_bids');
        Schema::dropIfExists('buyer_requirements');
        Schema::dropIfExists('business_credit_transactions');
        Schema::dropIfExists('business_credit_accounts');
        Schema::dropIfExists('proforma_invoices');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('quotation_negotiations');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('product_price_tiers');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'wholesale_moq')) {
                $table->dropColumn('wholesale_moq');
            }
            if (Schema::hasColumn('products', 'is_wholesale_enabled')) {
                $table->dropColumn('is_wholesale_enabled');
            }
        });

        Schema::dropIfExists('business_accounts');
    }
};
