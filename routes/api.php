<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminPromotionController;
use App\Http\Controllers\Api\V1\Admin\AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminSearchController;
use App\Http\Controllers\Api\V1\Admin\AdminShippingController;
use App\Http\Controllers\Api\V1\Admin\AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminBulkImportController;
use App\Http\Controllers\Api\V1\Admin\AttributeTemplateController;
use App\Http\Controllers\Api\V1\Admin\AdminVendorController;
use App\Http\Controllers\Api\V1\Admin\AdminCmsController;
use App\Http\Controllers\Api\V1\Admin\AdminStaffController;
use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\B2BMarketplaceController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\BusinessAccountController;
use App\Http\Controllers\Api\V1\BusinessCreditController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderReturnController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductTierController;
use App\Http\Controllers\Api\V1\ProformaInvoiceController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\RfqController;
use App\Http\Controllers\Api\V1\QuotationController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\SubcategoryController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\VendorStoreController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Versioned REST API endpoints for JSS Solutions Multi Vendor Marketplace.
|
*/

// Temporary SMTP Isolation Debug Endpoint (Top-level /api/debug/mail-test and /api/v1/debug/mail-test)
Route::get('/debug/mail-test', function (\Illuminate\Http\Request $request) {
    $to = $request->query('to') ?: ($request->query('email') ?: 'YOUR_GMAIL@gmail.com');

    \Illuminate\Support\Facades\Log::info("RAW_MAIL_TEST: Executing Mail::raw to [{$to}]");

    try {
        \Illuminate\Support\Facades\Mail::raw(
            "OTP TEST\n\nCode: 123456",
            function ($message) use ($to) {
                $message->to($to);
                $message->from("no-reply@jsssolutions.in", "JSS Marketplace");
                $message->subject("Laravel Raw Mail Test");
            }
        );

        \Illuminate\Support\Facades\Log::info("RAW_MAIL_TEST: Mail::raw completed successfully to [{$to}]");

        return response()->json([
            'success' => true,
            'message' => 'Raw mail test dispatched successfully',
            'recipient' => $to,
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("RAW_MAIL_TEST_FAILED to [{$to}]: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to deliver raw mail: ' . $e->getMessage(),
        ], 500);
    }
});

Route::get('/debug/otp-mail-test', function (\Illuminate\Http\Request $request) {
    $to = $request->query('to') ?: ($request->query('email') ?: 'YOUR_GMAIL@gmail.com');

    \Illuminate\Support\Facades\Log::info("OTP_MAIL_TEST: Executing Mail::to('{$to}')->send(new OtpMail(...))");

    try {
        $mailable = new \App\Mail\OtpMail('123456', 'email_verification');
        \Illuminate\Support\Facades\Mail::to($to)->send($mailable);

        \Illuminate\Support\Facades\Log::info("OTP_MAIL_TEST: Mail::to('{$to}')->send() completed successfully");

        return response()->json([
            'success' => true,
            'message' => 'OtpMail test dispatched successfully to ' . $to,
            'mailable' => get_class($mailable),
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("OTP_MAIL_TEST_FAILED to [{$to}]: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to deliver OtpMail: ' . $e->getMessage(),
        ], 500);
    }
});

Route::prefix('v1')->group(function () {
    
    // System Health Check Diagnostic (Module 14)
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/debug/mail-test', function (\Illuminate\Http\Request $request) {
        $to = $request->query('to') ?: ($request->query('email') ?: 'YOUR_GMAIL@gmail.com');

        \Illuminate\Support\Facades\Log::info("RAW_MAIL_TEST (v1): Executing Mail::raw to [{$to}]");

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "OTP TEST\n\nCode: 123456",
                function ($message) use ($to) {
                    $message->to($to);
                    $message->from("no-reply@jsssolutions.in", "JSS Marketplace");
                    $message->subject("Laravel Raw Mail Test");
                }
            );

            \Illuminate\Support\Facades\Log::info("RAW_MAIL_TEST (v1): Mail::raw completed successfully to [{$to}]");

            return response()->json([
                'success' => true,
                'message' => 'Raw mail test dispatched successfully',
                'recipient' => $to,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("RAW_MAIL_TEST_FAILED (v1) to [{$to}]: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver raw mail: ' . $e->getMessage(),
            ], 500);
        }
    });

    // Authentication Endpoints (Rate Limited to 6 attempts/minute)
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/send-otp', [AuthController::class, 'sendOtp']);
            Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp']);
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
            Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);
            Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
        });

        // Authenticated User Endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'profile']);
            Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationNotification'])
                ->middleware('throttle:6,1');
        });
    });

    // Alias for authenticated user profile (/api/v1/me)
    Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

    // Public System Settings
    Route::get('/settings', [SettingController::class, 'index']);

    // Public Catalog Foundation Endpoints (Module 2)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{id}/attributes', [CategoryController::class, 'getCategoryAttributes']);
    Route::get('/categories/{id}/subcategories', [SubcategoryController::class, 'index']);
    Route::get('/subcategories', [SubcategoryController::class, 'index']);
    Route::get('/subcategories/{slug}', [SubcategoryController::class, 'show']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/{slug}', [BrandController::class, 'show']);

    Route::get('/attributes', [AttributeController::class, 'index']);
    Route::get('/attributes/{id}', [AttributeController::class, 'show']);

    // Public Product Management Engine Endpoints (Module 3)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/trending', [ProductController::class, 'trending']);
    Route::get('/products/compare', [ProductController::class, 'compare']);
    Route::get('/products/{id}/frequently-bought-together', [ProductController::class, 'frequentlyBoughtTogether']);
    Route::get('/products/{id}/tiers', [ProductTierController::class, 'getTiers']);
    Route::get('/products/{id}/calculate-price', [ProductTierController::class, 'calculatePrice']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // Public Wholesale & B2B (Features 50 & 86)
    Route::get('/b2b/requirements', [B2BMarketplaceController::class, 'listRequirements']);

    // Advanced Product Search & Discovery (Module 13)
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete']);
    Route::get('/products/{id}/related', [SearchController::class, 'related']);
    Route::get('/recommendations/trending', [SearchController::class, 'trending']);

    // Public Vendor Storefront Endpoints (Module 11)
    Route::get('/stores', [VendorStoreController::class, 'index']);
    Route::get('/stores/{slug}', [VendorStoreController::class, 'show']);

    // Public Promotions & Coupons Endpoints (Module 12)
    Route::post('/promotions/coupons/apply', [PromotionController::class, 'applyCoupon']);
    Route::get('/promotions/flash-sales', [PromotionController::class, 'flashSales']);

    // Public Product Reviews & Questions (Module 9)
    Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
    Route::get('/products/{id}/questions', [QuestionController::class, 'index']);

    // Public Warehouse Endpoints (Module 4)
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);

    // Public / Guest Shopping Cart Endpoints (Module 5)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [CartController::class, 'removeItem']);
        Route::post('/clear', [CartController::class, 'clear']);
        
        // Feature 15: Save for Later endpoints
        Route::get('/saved-for-later', [CartController::class, 'getSavedForLater']);
        Route::post('/items/{id}/save-for-later', [CartController::class, 'saveForLater']);
        Route::post('/saved-for-later/{id}/move-to-cart', [CartController::class, 'moveToCart']);
        Route::delete('/saved-for-later/{id}', [CartController::class, 'removeSavedItem']);

        // Merge guest cart on login
        Route::middleware('auth:sanctum')->post('/merge', [CartController::class, 'merge']);
    });

    // Public Gateway Webhook Listener (Module 7)
    Route::post('/payments/webhook/{gateway}', [PaymentController::class, 'webhook']);

    // Public Shipping Rate Calculation & AWB Tracking (Module 8)
    Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);
    Route::get('/shipments/track/{trackingNumber}', [ShippingController::class, 'track']);

    // Protected Customer Operations (Modules 5-14)
    Route::middleware('auth:sanctum')->group(function () {
        // Customer Addresses
        Route::prefix('addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index']);
            Route::post('/', [AddressController::class, 'store']);
            Route::delete('/{id}', [AddressController::class, 'destroy']);
        });

        // Customer Checkout & Orders Engine
        Route::post('/checkout/process', [OrderController::class, 'checkout']);
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{orderNumber}', [OrderController::class, 'show']);
            Route::get('/{orderNumber}/invoice', [OrderController::class, 'downloadInvoice']);
            Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel']);
            Route::post('/{orderNumber}/items/{itemId}/cancel', [OrderController::class, 'cancelItem']);
            Route::get('/{orderNumber}/shipment', [ShippingController::class, 'orderShipment']);
        });

        // Customer Payments Engine (Module 7)
        Route::prefix('payments')->group(function () {
            Route::post('/initiate', [PaymentController::class, 'initiate']);
            Route::post('/verify', [PaymentController::class, 'verify']);
            Route::get('/{paymentNumber}', [PaymentController::class, 'show']);
        });

        // Customer Wishlist Endpoints
        Route::prefix('wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']);
            Route::post('/toggle', [WishlistController::class, 'toggle']);
            Route::delete('/{productId}', [WishlistController::class, 'destroy']);
        });

        // Customer Reviews & Q&A (Module 9)
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::post('/reviews/{id}/report', [ReviewController::class, 'report']);
        Route::post('/questions', [QuestionController::class, 'store']);

        // Customer Support Tickets (Module 9)
        Route::prefix('support/tickets')->group(function () {
            Route::get('/', [SupportTicketController::class, 'index']);
            Route::post('/', [SupportTicketController::class, 'store']);
            Route::get('/{ticketNumber}', [SupportTicketController::class, 'show']);
            Route::post('/{ticketNumber}/reply', [SupportTicketController::class, 'reply']);
        });

        // Customer Notifications Engine (Module 10)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        });

        // Customer Loyalty Points & Personalized Recommendations (Modules 12, 13)
        Route::get('/loyalty/points', [PromotionController::class, 'loyaltyPoints']);
        Route::get('/recommendations/personalized', [SearchController::class, 'personalized']);

        // Phase 3C: Social & Favorites (Features 65, 66, 67)
        Route::prefix('favorites')->group(function () {
            Route::get('/brands', [FavoriteController::class, 'getFavoriteBrands']);
            Route::post('/brands/{brandId}', [FavoriteController::class, 'addFavoriteBrand']);
            Route::delete('/brands/{brandId}', [FavoriteController::class, 'removeFavoriteBrand']);

            Route::get('/categories', [FavoriteController::class, 'getFavoriteCategories']);
            Route::post('/categories/{categoryId}', [FavoriteController::class, 'addFavoriteCategory']);
            Route::delete('/categories/{categoryId}', [FavoriteController::class, 'removeFavoriteCategory']);
        });

        // Feature 65: Follow Seller / Store
        Route::prefix('stores')->group(function () {
            Route::get('/following', [FavoriteController::class, 'getFollowedStores']);
            Route::post('/{id}/follow', [FavoriteController::class, 'followStore']);
            Route::delete('/{id}/unfollow', [FavoriteController::class, 'unfollowStore']);
        });

        // Phase 3D: Real Price Drop & Back-in-Stock Alerts (Features 40 & 41)
        Route::prefix('alerts')->group(function () {
            // Feature 40: Price Drop Alerts
            Route::get('/price-drop', [AlertController::class, 'getPriceDropAlerts']);
            Route::post('/price-drop', [AlertController::class, 'subscribePriceDrop']);
            Route::delete('/price-drop/{productId}', [AlertController::class, 'cancelPriceDrop']);

            // Feature 41: Back-in-Stock Alerts
            Route::get('/back-in-stock', [AlertController::class, 'getBackInStockSubscriptions']);
            Route::post('/back-in-stock', [AlertController::class, 'subscribeBackInStock']);
            Route::delete('/back-in-stock/{productId}', [AlertController::class, 'cancelBackInStock']);
        });

        // Phase 3E: Order Returns & Reverse Logistics (Features 36 & 37)
        Route::prefix('returns')->group(function () {
            Route::get('/', [OrderReturnController::class, 'index']);
            Route::post('/', [OrderReturnController::class, 'store']);
            Route::get('/{returnNumber}', [OrderReturnController::class, 'show']);
        });

        // Phase 4: B2B / Wholesale / Business Marketplace Engine
        // Feature 92 & 93: Business Account & Verification
        Route::prefix('business')->group(function () {
            Route::get('/account', [BusinessAccountController::class, 'getAccount']);
            Route::post('/account', [BusinessAccountController::class, 'storeOrUpdate']);
        });

        // Features 51 & 82: Request for Quotation (RFQ)
        Route::prefix('rfq')->group(function () {
            Route::get('/', [RfqController::class, 'index']);
            Route::post('/', [RfqController::class, 'store']);
            Route::get('/{rfqNumber}', [RfqController::class, 'show']);
        });

        // Feature 83 & Negotiation: Quotations & Counter Offers
        Route::prefix('quotations')->group(function () {
            Route::post('/{id}/counter', [QuotationController::class, 'counterOffer']);
            Route::post('/{id}/accept', [QuotationController::class, 'acceptQuotation']);
            Route::post('/{id}/reject', [QuotationController::class, 'rejectQuotation']);
        });

        // Feature 88: Purchase Orders
        Route::prefix('purchase-orders')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'index']);
            Route::post('/', [PurchaseOrderController::class, 'store']);
            Route::get('/{poNumber}', [PurchaseOrderController::class, 'show']);
            Route::get('/{poNumber}/pdf', [PurchaseOrderController::class, 'downloadPdf']);
        });

        // Feature 95: Proforma Invoices
        Route::prefix('proforma-invoices')->group(function () {
            Route::get('/', [ProformaInvoiceController::class, 'index']);
            Route::post('/', [ProformaInvoiceController::class, 'store']);
            Route::get('/{proformaNumber}', [ProformaInvoiceController::class, 'show']);
            Route::get('/{proformaNumber}/pdf', [ProformaInvoiceController::class, 'downloadPdf']);
        });

        // Feature 94: Business Credit / Pay-Later
        Route::prefix('business-credit')->group(function () {
            Route::get('/', [BusinessCreditController::class, 'getAccount']);
            Route::post('/apply', [BusinessCreditController::class, 'apply']);
        });

        // Features 84, 85, 86, 87: Samples & Requirements
        Route::prefix('b2b')->group(function () {
            Route::post('/requirements', [B2BMarketplaceController::class, 'postRequirement']);
            Route::post('/requirements/{id}/bid', [B2BMarketplaceController::class, 'bidOnRequirement']);
            Route::post('/samples', [B2BMarketplaceController::class, 'requestSample']);
            Route::get('/samples', [B2BMarketplaceController::class, 'listSampleRequests']);
        });

        // Media Upload Engine (Accessible to authenticated Vendors & Admins)
        Route::post('/media/upload', [MediaController::class, 'upload']);

        // Vendor Dashboard & Store Operations (Module 11)
        Route::prefix('vendor')->group(function () {
            Route::post('/store', [VendorStoreController::class, 'register']);
            Route::get('/store', [VendorStoreController::class, 'currentStore']);
            Route::get('/dashboard', [VendorStoreController::class, 'dashboard']);
            Route::get('/products', [VendorStoreController::class, 'products']);
            Route::post('/products', [VendorStoreController::class, 'storeProduct']);
            Route::put('/products/{id}', [VendorStoreController::class, 'updateProduct']);
            Route::post('/products/{id}/submit', [VendorStoreController::class, 'submitProductForReview']);
            Route::post('/products/{id}/duplicate', [VendorStoreController::class, 'duplicateProduct']);
            Route::delete('/products/{id}', [VendorStoreController::class, 'destroyProduct']);
            Route::post('/subcategories', [SubcategoryController::class, 'storeVendorSubcategory']);
            Route::get('/inventory', [VendorStoreController::class, 'inventory']);
            Route::post('/inventory/update', [VendorStoreController::class, 'updateInventory']);
            Route::get('/orders', [VendorStoreController::class, 'orders']);
            Route::put('/orders/{id}/status', [VendorStoreController::class, 'updateOrderStatus']);
            Route::get('/wallet', [VendorStoreController::class, 'wallet']);
            Route::get('/analytics', [VendorStoreController::class, 'analytics']);
            Route::post('/settlements/request', [VendorStoreController::class, 'requestSettlement']);

            // Phase 4 Vendor B2B Operations
            Route::get('/rfq/inbox', [RfqController::class, 'sellerInbox']);
            Route::post('/rfq/{id}/quote', [QuotationController::class, 'submitQuotation']);
            Route::post('/purchase-orders/{id}/accept', [PurchaseOrderController::class, 'accept']);
            Route::patch('/samples/{id}/status', [B2BMarketplaceController::class, 'updateSampleStatus']);
            Route::put('/products/{id}/tiers', [ProductTierController::class, 'syncTiers']);
        });
    });

    // Protected Admin Operations (Modules 1-14)
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // System Settings Admin
        Route::put('/settings', [SettingController::class, 'update']);

        // Admin Product Management (Modules 1-10)
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::post('/products/bulk-action', [AdminProductController::class, 'bulkAction']);
        Route::post('/products/import/validate', [AdminBulkImportController::class, 'validateImport']);
        Route::post('/products/import/execute', [AdminBulkImportController::class, 'executeImport']);
        Route::get('/products/pending', [AdminProductController::class, 'pending']);
        Route::get('/products/{id}', [AdminProductController::class, 'show']);
        Route::put('/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
        Route::post('/products/{id}/approve', [AdminProductController::class, 'approve']);
        Route::post('/products/{id}/reject', [AdminProductController::class, 'reject']);
        Route::post('/products/{id}/request-changes', [AdminProductController::class, 'requestChanges']);
        Route::post('/products/{id}/unpublish', [AdminProductController::class, 'unpublish']);
        Route::post('/products/{id}/publish', [AdminProductController::class, 'publish']);
        Route::post('/products/{id}/duplicate', [AdminProductController::class, 'duplicate']);
        Route::apiResource('/attribute-templates', AttributeTemplateController::class);

        // Category Management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Subcategory Management
        Route::get('/subcategories', [SubcategoryController::class, 'index']);
        Route::post('/subcategories', [SubcategoryController::class, 'store']);
        Route::put('/subcategories/{id}', [SubcategoryController::class, 'update']);
        Route::delete('/subcategories/{id}', [SubcategoryController::class, 'destroy']);
        Route::patch('/subcategories/{id}/status', [SubcategoryController::class, 'updateStatus']);

        // Brand Management
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{id}', [BrandController::class, 'update']);
        Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

        // Attribute Management
        Route::post('/attributes', [AttributeController::class, 'store']);
        Route::delete('/attributes/{id}', [AttributeController::class, 'destroy']);

        // Media Upload
        Route::post('/media/upload', [MediaController::class, 'upload']);

        // Warehouse Management (Module 4)
        Route::post('/warehouses', [WarehouseController::class, 'store']);
        Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);

        // Inventory Management Engine (Module 4)
        Route::get('/inventories', [InventoryController::class, 'index']);
        Route::post('/inventories/add-stock', [InventoryController::class, 'addStock']);
        Route::post('/inventories/adjust-stock', [InventoryController::class, 'adjustStock']);
        Route::post('/inventories/transfer', [InventoryController::class, 'transfer']);
        Route::get('/inventories/low-stock', [InventoryController::class, 'lowStockReport']);
        Route::get('/stock-movements', [InventoryController::class, 'movements']);

        // Abandoned Carts Report (Module 5)
        Route::get('/carts/abandoned', [CartController::class, 'abandonedCarts']);

        // Admin Order Management (Module 6)
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        // Phase 3E: Admin Order Returns & Reverse Logistics (Features 36 & 37)
        Route::get('/returns', [OrderReturnController::class, 'adminIndex']);
        Route::put('/returns/{id}/status', [OrderReturnController::class, 'updateStatus']);

        // Admin Payment & Refund Management (Module 7)
        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::get('/payments/logs', [AdminPaymentController::class, 'logs']);
        Route::post('/payments/refund', [AdminPaymentController::class, 'refund']);

        // Admin Shipping & Logistics Management (Module 8)
        Route::get('/shipping-zones', [AdminShippingController::class, 'zones']);
        Route::post('/shipping-zones', [AdminShippingController::class, 'storeZone']);
        Route::get('/couriers', [AdminShippingController::class, 'couriers']);
        Route::post('/couriers', [AdminShippingController::class, 'storeCourier']);
        Route::get('/shipments', [AdminShippingController::class, 'shipments']);
        Route::post('/shipments/create', [AdminShippingController::class, 'createShipment']);
        Route::patch('/shipments/{id}/status', [AdminShippingController::class, 'updateStatus']);

        // Admin Review Moderation & Q&A Answers (Module 9)
        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::patch('/reviews/{id}/moderate', [AdminReviewController::class, 'moderate']);
        Route::post('/questions/{id}/answer', [AdminReviewController::class, 'answerQuestion']);

        // Admin Support Tickets (Module 9)
        Route::get('/support/tickets', [AdminReviewController::class, 'tickets']);
        Route::post('/support/tickets/{id}/reply', [AdminReviewController::class, 'ticketReply']);
        Route::patch('/support/tickets/{id}/status', [AdminReviewController::class, 'updateTicketStatus']);

        // Admin BI Analytics & Administration (Module 10)
        Route::prefix('analytics')->group(function () {
            Route::get('/overview', [AdminAnalyticsController::class, 'overview']);
            Route::get('/sales', [AdminAnalyticsController::class, 'sales']);
            Route::get('/customers', [AdminAnalyticsController::class, 'customers']);
            Route::get('/inventory', [AdminAnalyticsController::class, 'inventory']);
        });

        // Admin Report Exports (Module 10)
        Route::get('/reports/sales/export', [AdminAnalyticsController::class, 'exportSales']);
        Route::get('/reports/inventory/export', [AdminAnalyticsController::class, 'exportInventory']);

        // Admin Audit & Activity Logs (Module 10)
        Route::get('/audit-logs', [AdminAnalyticsController::class, 'auditLogs']);
        Route::get('/activity-logs', [AdminAnalyticsController::class, 'activityLogs']);

        // Admin Customer Management
        Route::get('/customers', [AdminCustomerController::class, 'index']);
        Route::get('/customers/{id}', [AdminCustomerController::class, 'show']);
        Route::patch('/customers/{id}/toggle-status', [AdminCustomerController::class, 'toggleStatus']);

        // Admin Multi-Vendor Management (Module 11)
        Route::get('/vendor/stores', [AdminVendorController::class, 'stores']);
        Route::get('/vendor/stores/{id}', [AdminVendorController::class, 'show']);
        Route::get('/vendor/stats', [AdminVendorController::class, 'stats']);
        Route::patch('/vendor/stores/{id}/kyc', [AdminVendorController::class, 'verifyKYC']);
        Route::post('/vendor/stores/{id}/approve', [AdminVendorController::class, 'approveStore']);
        Route::post('/vendor/stores/{id}/reject', [AdminVendorController::class, 'rejectStore']);
        Route::post('/vendor/stores/{id}/suspend', [AdminVendorController::class, 'suspendStore']);
        Route::post('/vendor/stores/{id}/activate', [AdminVendorController::class, 'activateStore']);
        Route::get('/vendor/settlements', [AdminVendorController::class, 'settlements']);
        Route::patch('/vendor/settlements/{id}/process', [AdminVendorController::class, 'processSettlement']);

        // Admin Promotions, Coupons & Flash Sales (Module 12)
        Route::get('/coupons', [AdminPromotionController::class, 'indexCoupons']);
        Route::post('/coupons', [AdminPromotionController::class, 'storeCoupon']);
        Route::put('/coupons/{id}', [AdminPromotionController::class, 'updateCoupon']);
        Route::patch('/coupons/{id}/toggle-status', [AdminPromotionController::class, 'toggleCouponStatus']);
        Route::delete('/coupons/{id}', [AdminPromotionController::class, 'destroyCoupon']);

        Route::get('/flash-sales', [AdminPromotionController::class, 'indexFlashSales']);
        Route::post('/flash-sales', [AdminPromotionController::class, 'storeFlashSale']);
        Route::put('/flash-sales/{id}', [AdminPromotionController::class, 'updateFlashSale']);
        Route::patch('/flash-sales/{id}/toggle-status', [AdminPromotionController::class, 'toggleFlashSaleStatus']);
        Route::delete('/flash-sales/{id}', [AdminPromotionController::class, 'destroyFlashSale']);

        // Admin Search Analytics & Synonyms (Module 13)
        Route::get('/search/analytics', [AdminSearchController::class, 'analytics']);
        Route::get('/search/synonyms', [AdminSearchController::class, 'synonyms']);
        Route::post('/search/synonyms', [AdminSearchController::class, 'storeSynonym']);

        // Admin CMS & Content Management
        Route::get('/cms/banners', [AdminCmsController::class, 'indexBanners']);
        Route::post('/cms/banners', [AdminCmsController::class, 'storeBanner']);
        Route::put('/cms/banners/{id}', [AdminCmsController::class, 'updateBanner']);
        Route::patch('/cms/banners/{id}/toggle-status', [AdminCmsController::class, 'toggleBannerStatus']);
        Route::delete('/cms/banners/{id}', [AdminCmsController::class, 'destroyBanner']);

        Route::get('/cms/popups', [AdminCmsController::class, 'getPopups']);
        Route::put('/cms/popups', [AdminCmsController::class, 'updatePopup']);

        Route::get('/cms/pages', [AdminCmsController::class, 'getPages']);
        Route::put('/cms/pages/{id}', [AdminCmsController::class, 'updatePage']);

        Route::get('/cms/faqs', [AdminCmsController::class, 'getFaqs']);
        Route::post('/cms/faqs', [AdminCmsController::class, 'storeFaq']);
        Route::delete('/cms/faqs/{id}', [AdminCmsController::class, 'destroyFaq']);

        // Admin Staff & RBAC
        Route::get('/staff/roles', [AdminStaffController::class, 'indexRoles']);
        Route::get('/staff', [AdminStaffController::class, 'indexStaff']);
        Route::post('/staff', [AdminStaffController::class, 'storeStaff']);
        Route::put('/staff/{id}', [AdminStaffController::class, 'updateStaff']);
        Route::delete('/staff/{id}', [AdminStaffController::class, 'destroyStaff']);

        // Phase 4: Admin B2B Management
        Route::get('/business/buyers', [BusinessAccountController::class, 'adminList']);
        Route::get('/business/buyers/{id}', [BusinessAccountController::class, 'adminShow']);
        Route::patch('/business/buyers/{id}/verify', [BusinessAccountController::class, 'adminVerify']);
        Route::get('/business-credit', [BusinessCreditController::class, 'adminList']);
        Route::patch('/business-credit/{id}/approve', [BusinessCreditController::class, 'adminApproveLimit']);
        Route::post('/business-credit/{id}/repayment', [BusinessCreditController::class, 'recordRepayment']);
        Route::get('/rfqs', [RfqController::class, 'adminIndex']);
        Route::put('/products/{id}/tiers', [ProductTierController::class, 'syncTiers']);
    });
});
