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
use App\Http\Controllers\Api\V1\Admin\AdminNotificationTemplateController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationLogController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
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
use App\Http\Controllers\Api\V1\AiAssistantController;
use App\Http\Controllers\Api\V1\VisualSearchController;
use App\Http\Controllers\Api\V1\VirtualTryOnController;
use App\Http\Controllers\Api\V1\LiveShoppingController;
use App\Http\Controllers\Api\V1\PasskeyController;
use App\Http\Controllers\Api\V1\ProductMedia360Controller;
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

        // Customer Notifications Engine (Module 10 & Phase 5)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::get('/preferences', [NotificationPreferenceController::class, 'getPreferences']);
            Route::put('/preferences', [NotificationPreferenceController::class, 'updatePreferences']);
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

        // Phase 3D & 5B: Real Price Drop, Back-in-Stock & Launch Alerts (Features 40, 41, 123)
        Route::prefix('alerts')->group(function () {
            // Feature 40: Price Drop Alerts
            Route::get('/price-drop', [AlertController::class, 'getPriceDropAlerts']);
            Route::post('/price-drop', [AlertController::class, 'subscribePriceDrop']);
            Route::delete('/price-drop/{productId}', [AlertController::class, 'cancelPriceDrop']);

            // Feature 41: Back-in-Stock Alerts
            Route::get('/back-in-stock', [AlertController::class, 'getBackInStockSubscriptions']);
            Route::post('/back-in-stock', [AlertController::class, 'subscribeBackInStock']);
            Route::delete('/back-in-stock/{productId}', [AlertController::class, 'cancelBackInStock']);

            // Feature 123: Product Launch Alerts
            Route::get('/launch', [AlertController::class, 'getProductLaunchSubscriptions']);
            Route::post('/launch', [AlertController::class, 'subscribeProductLaunch']);
            Route::delete('/launch/{productId}', [AlertController::class, 'cancelProductLaunch']);
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
    Route::middleware(['auth:sanctum', 'ensure.admin_staff'])->prefix('admin')->group(function () {
        // System Settings Admin
        Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.edit');

        // Admin Product Management (Modules 1-10)
        Route::get('/products', [AdminProductController::class, 'index'])->middleware('permission:products.view');
        Route::post('/products', [AdminProductController::class, 'store'])->middleware('permission:products.create');
        Route::post('/products/bulk-action', [AdminProductController::class, 'bulkAction'])->middleware('permission:products.edit');
        Route::post('/products/import/validate', [AdminBulkImportController::class, 'validateImport'])->middleware('permission:products.create');
        Route::post('/products/import/execute', [AdminBulkImportController::class, 'executeImport'])->middleware('permission:products.create');
        Route::get('/products/pending', [AdminProductController::class, 'pending'])->middleware('permission:products.approve');
        Route::get('/products/{id}', [AdminProductController::class, 'show'])->middleware('permission:products.view');
        Route::put('/products/{id}', [AdminProductController::class, 'update'])->middleware('permission:products.edit');
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->middleware('permission:products.delete');
        Route::post('/products/{id}/approve', [AdminProductController::class, 'approve'])->middleware('permission:products.approve');
        Route::post('/products/{id}/reject', [AdminProductController::class, 'reject'])->middleware('permission:products.approve');
        Route::post('/products/{id}/request-changes', [AdminProductController::class, 'requestChanges'])->middleware('permission:products.approve');
        Route::post('/products/{id}/unpublish', [AdminProductController::class, 'unpublish'])->middleware('permission:products.edit');
        Route::post('/products/{id}/publish', [AdminProductController::class, 'publish'])->middleware('permission:products.edit');
        Route::post('/products/{id}/duplicate', [AdminProductController::class, 'duplicate'])->middleware('permission:products.create');
        Route::apiResource('/attribute-templates', AttributeTemplateController::class)->middleware('permission:attributes.view');

        // Category Management
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware('permission:categories.edit');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete');

        // Subcategory Management
        Route::get('/subcategories', [SubcategoryController::class, 'index'])->middleware('permission:categories.view');
        Route::post('/subcategories', [SubcategoryController::class, 'store'])->middleware('permission:categories.create');
        Route::put('/subcategories/{id}', [SubcategoryController::class, 'update'])->middleware('permission:categories.edit');
        Route::delete('/subcategories/{id}', [SubcategoryController::class, 'destroy'])->middleware('permission:categories.delete');
        Route::patch('/subcategories/{id}/status', [SubcategoryController::class, 'updateStatus'])->middleware('permission:categories.edit');

        // Brand Management
        Route::post('/brands', [BrandController::class, 'store'])->middleware('permission:brands.create');
        Route::put('/brands/{id}', [BrandController::class, 'update'])->middleware('permission:brands.edit');
        Route::delete('/brands/{id}', [BrandController::class, 'destroy'])->middleware('permission:brands.delete');

        // Attribute Management
        Route::post('/attributes', [AttributeController::class, 'store'])->middleware('permission:attributes.create');
        Route::delete('/attributes/{id}', [AttributeController::class, 'destroy'])->middleware('permission:attributes.delete');

        // Media Upload
        Route::post('/media/upload', [MediaController::class, 'upload'])->middleware('permission:products.create|products.edit|categories.create|categories.edit|brands.create|brands.edit|cms.create|cms.edit');

        // Warehouse Management (Module 4)
        Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('permission:inventory.edit');
        Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])->middleware('permission:inventory.edit');

        // Inventory Management Engine (Module 4)
        Route::get('/inventories', [InventoryController::class, 'index'])->middleware('permission:inventory.view');
        Route::post('/inventories/add-stock', [InventoryController::class, 'addStock'])->middleware('permission:inventory.edit');
        Route::post('/inventories/adjust-stock', [InventoryController::class, 'adjustStock'])->middleware('permission:inventory.edit');
        Route::post('/inventories/transfer', [InventoryController::class, 'transfer'])->middleware('permission:inventory.edit');
        Route::get('/inventories/low-stock', [InventoryController::class, 'lowStockReport'])->middleware('permission:inventory.view');
        Route::get('/stock-movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view');

        // Abandoned Carts Report (Module 5)
        Route::get('/carts/abandoned', [CartController::class, 'abandonedCarts'])->middleware('permission:orders.view');

        // Admin Order Management (Module 6)
        Route::get('/orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view');
        Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view');
        Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.edit');

        // Phase 3E: Admin Order Returns & Reverse Logistics (Features 36 & 37)
        Route::get('/returns', [OrderReturnController::class, 'adminIndex'])->middleware('permission:orders.view');
        Route::put('/returns/{id}/status', [OrderReturnController::class, 'updateStatus'])->middleware('permission:orders.edit');

        // Admin Payment & Refund Management (Module 7)
        Route::get('/payments', [AdminPaymentController::class, 'index'])->middleware('permission:payments.view');
        Route::get('/payments/logs', [AdminPaymentController::class, 'logs'])->middleware('permission:payments.view');
        Route::post('/payments/refund', [AdminPaymentController::class, 'refund'])->middleware('permission:payments.refund');

        // Admin Shipping & Logistics Management (Module 8)
        Route::get('/shipping-zones', [AdminShippingController::class, 'zones'])->middleware('permission:shipping.view');
        Route::post('/shipping-zones', [AdminShippingController::class, 'storeZone'])->middleware('permission:shipping.edit');
        Route::get('/couriers', [AdminShippingController::class, 'couriers'])->middleware('permission:shipping.view');
        Route::post('/couriers', [AdminShippingController::class, 'storeCourier'])->middleware('permission:shipping.edit');
        Route::get('/shipments', [AdminShippingController::class, 'shipments'])->middleware('permission:shipping.view');
        Route::post('/shipments/create', [AdminShippingController::class, 'createShipment'])->middleware('permission:shipping.edit');
        Route::patch('/shipments/{id}/status', [AdminShippingController::class, 'updateStatus'])->middleware('permission:shipping.edit');

        // Admin Review Moderation & Q&A Answers (Module 9)
        Route::get('/reviews', [AdminReviewController::class, 'index'])->middleware('permission:reviews.view');
        Route::patch('/reviews/{id}/moderate', [AdminReviewController::class, 'moderate'])->middleware('permission:reviews.edit');
        Route::post('/questions/{id}/answer', [AdminReviewController::class, 'answerQuestion'])->middleware('permission:reviews.edit');

        // Admin Support Tickets (Module 9)
        Route::get('/support/tickets', [AdminReviewController::class, 'tickets'])->middleware('permission:support.view');
        Route::post('/support/tickets/{id}/reply', [AdminReviewController::class, 'ticketReply'])->middleware('permission:support.edit');
        Route::patch('/support/tickets/{id}/status', [AdminReviewController::class, 'updateTicketStatus'])->middleware('permission:support.edit');

        // Admin BI Analytics & Administration (Module 10)
        Route::prefix('analytics')->middleware('permission:reports.view')->group(function () {
            Route::get('/overview', [AdminAnalyticsController::class, 'overview']);
            Route::get('/sales', [AdminAnalyticsController::class, 'sales']);
            Route::get('/customers', [AdminAnalyticsController::class, 'customers']);
            Route::get('/inventory', [AdminAnalyticsController::class, 'inventory']);
        });

        // Admin Report Exports (Module 10)
        Route::get('/reports/sales/export', [AdminAnalyticsController::class, 'exportSales'])->middleware('permission:reports.export');
        Route::get('/reports/inventory/export', [AdminAnalyticsController::class, 'exportInventory'])->middleware('permission:reports.export');

        // Admin Audit & Activity Logs (Module 10)
        Route::get('/audit-logs', [AdminAnalyticsController::class, 'auditLogs'])->middleware('permission:security.view');
        Route::get('/activity-logs', [AdminAnalyticsController::class, 'activityLogs'])->middleware('permission:security.view');

        // Admin Customer Management
        Route::get('/customers', [AdminCustomerController::class, 'index'])->middleware('permission:customers.view');
        Route::get('/customers/{id}', [AdminCustomerController::class, 'show'])->middleware('permission:customers.view');
        Route::patch('/customers/{id}/toggle-status', [AdminCustomerController::class, 'toggleStatus'])->middleware('permission:customers.edit');

        // Admin Multi-Vendor Management (Module 11)
        Route::get('/vendor/stores', [AdminVendorController::class, 'stores'])->middleware('permission:vendors.view');
        Route::get('/vendor/stores/{id}', [AdminVendorController::class, 'show'])->middleware('permission:vendors.view');
        Route::get('/vendor/stats', [AdminVendorController::class, 'stats'])->middleware('permission:vendors.view');
        Route::patch('/vendor/stores/{id}/kyc', [AdminVendorController::class, 'verifyKYC'])->middleware('permission:vendors.approve');
        Route::post('/vendor/stores/{id}/approve', [AdminVendorController::class, 'approveStore'])->middleware('permission:vendors.approve');
        Route::post('/vendor/stores/{id}/reject', [AdminVendorController::class, 'rejectStore'])->middleware('permission:vendors.approve');
        Route::post('/vendor/stores/{id}/suspend', [AdminVendorController::class, 'suspendStore'])->middleware('permission:vendors.suspend');
        Route::post('/vendor/stores/{id}/activate', [AdminVendorController::class, 'activateStore'])->middleware('permission:vendors.approve');
        Route::get('/vendor/settlements', [AdminVendorController::class, 'settlements'])->middleware('permission:payments.view');
        Route::patch('/vendor/settlements/{id}/process', [AdminVendorController::class, 'processSettlement'])->middleware('permission:payments.settle');

        // Admin Promotions, Coupons & Flash Sales (Module 12)
        Route::get('/coupons', [AdminPromotionController::class, 'indexCoupons'])->middleware('permission:promotions.view');
        Route::post('/coupons', [AdminPromotionController::class, 'storeCoupon'])->middleware('permission:promotions.create');
        Route::put('/coupons/{id}', [AdminPromotionController::class, 'updateCoupon'])->middleware('permission:promotions.edit');
        Route::patch('/coupons/{id}/toggle-status', [AdminPromotionController::class, 'toggleCouponStatus'])->middleware('permission:promotions.edit');
        Route::delete('/coupons/{id}', [AdminPromotionController::class, 'destroyCoupon'])->middleware('permission:promotions.delete');

        Route::get('/flash-sales', [AdminPromotionController::class, 'indexFlashSales'])->middleware('permission:promotions.view');
        Route::post('/flash-sales', [AdminPromotionController::class, 'storeFlashSale'])->middleware('permission:promotions.create');
        Route::put('/flash-sales/{id}', [AdminPromotionController::class, 'updateFlashSale'])->middleware('permission:promotions.edit');
        Route::patch('/flash-sales/{id}/toggle-status', [AdminPromotionController::class, 'toggleFlashSaleStatus'])->middleware('permission:promotions.edit');
        Route::delete('/flash-sales/{id}', [AdminPromotionController::class, 'destroyFlashSale'])->middleware('permission:promotions.delete');

        // Admin Search Analytics & Synonyms (Module 13)
        Route::get('/search/analytics', [AdminSearchController::class, 'analytics'])->middleware('permission:reports.view');
        Route::get('/search/synonyms', [AdminSearchController::class, 'synonyms'])->middleware('permission:settings.view');
        Route::post('/search/synonyms', [AdminSearchController::class, 'storeSynonym'])->middleware('permission:settings.edit');

        // Admin CMS & Content Management
        Route::get('/cms/banners', [AdminCmsController::class, 'indexBanners'])->middleware('permission:cms.view');
        Route::post('/cms/banners', [AdminCmsController::class, 'storeBanner'])->middleware('permission:cms.create');
        Route::put('/cms/banners/{id}', [AdminCmsController::class, 'updateBanner'])->middleware('permission:cms.edit');
        Route::patch('/cms/banners/{id}/toggle-status', [AdminCmsController::class, 'toggleBannerStatus'])->middleware('permission:cms.edit');
        Route::delete('/cms/banners/{id}', [AdminCmsController::class, 'destroyBanner'])->middleware('permission:cms.delete');

        Route::get('/cms/popups', [AdminCmsController::class, 'getPopups'])->middleware('permission:cms.view');
        Route::put('/cms/popups', [AdminCmsController::class, 'updatePopup'])->middleware('permission:cms.edit');

        Route::get('/cms/pages', [AdminCmsController::class, 'getPages'])->middleware('permission:cms.view');
        Route::put('/cms/pages/{id}', [AdminCmsController::class, 'updatePage'])->middleware('permission:cms.edit');

        Route::get('/cms/faqs', [AdminCmsController::class, 'getFaqs'])->middleware('permission:cms.view');
        Route::post('/cms/faqs', [AdminCmsController::class, 'storeFaq'])->middleware('permission:cms.create');
        Route::delete('/cms/faqs/{id}', [AdminCmsController::class, 'destroyFaq'])->middleware('permission:cms.delete');

        // Admin Staff & RBAC
        Route::get('/staff/roles', [AdminStaffController::class, 'indexRoles'])->middleware('permission:staff.view');
        Route::get('/staff', [AdminStaffController::class, 'indexStaff'])->middleware('permission:staff.view');
        Route::post('/staff', [AdminStaffController::class, 'storeStaff'])->middleware('permission:staff.create');
        Route::put('/staff/{id}', [AdminStaffController::class, 'updateStaff'])->middleware('permission:staff.edit');
        Route::delete('/staff/{id}', [AdminStaffController::class, 'destroyStaff'])->middleware('permission:staff.delete');

        // Phase 4: Admin B2B Management
        Route::get('/business/buyers', [BusinessAccountController::class, 'adminList'])->middleware('permission:b2b.view');
        Route::get('/business/buyers/{id}', [BusinessAccountController::class, 'adminShow'])->middleware('permission:b2b.view');
        Route::patch('/business/buyers/{id}/verify', [BusinessAccountController::class, 'adminVerify'])->middleware('permission:b2b.approve');
        Route::get('/business-credit', [BusinessCreditController::class, 'adminList'])->middleware('permission:b2b.view');
        Route::patch('/business-credit/{id}/approve', [BusinessCreditController::class, 'adminApproveLimit'])->middleware('permission:b2b.approve');
        Route::post('/business-credit/{id}/repayment', [BusinessCreditController::class, 'recordRepayment'])->middleware('permission:b2b.edit');
        Route::get('/rfqs', [RfqController::class, 'adminIndex'])->middleware('permission:b2b.view');
        Route::put('/products/{id}/tiers', [ProductTierController::class, 'syncTiers'])->middleware('permission:products.edit');

        // Phase 5: Admin Notification Engine (Templates & Delivery Logs)
        Route::prefix('notifications')->middleware('permission:notifications.view')->group(function () {
            Route::get('/templates', [AdminNotificationTemplateController::class, 'index']);
            Route::get('/templates/{id}', [AdminNotificationTemplateController::class, 'show']);
            Route::put('/templates/{id}', [AdminNotificationTemplateController::class, 'update'])->middleware('permission:notifications.edit');
            Route::get('/logs', [AdminNotificationLogController::class, 'index']);
            Route::get('/stats', [AdminNotificationLogController::class, 'stats']);
            Route::post('/logs/{id}/retry', [AdminNotificationLogController::class, 'retry'])->middleware('permission:notifications.edit');
        });
    });

    // =========================================================================
    // PHASE 6: ADVANCED / P3 COMMERCE SUITE
    // =========================================================================

    // 1. Feature 57: AI Shopping Assistant
    Route::post('/ai/chat', [AiAssistantController::class, 'chat']);
    Route::get('/ai/history', [AiAssistantController::class, 'history']);
    Route::delete('/ai/history', [AiAssistantController::class, 'clear']);

    // 2. Features 59 + 61: Visual & Image Search
    Route::post('/search/visual', [VisualSearchController::class, 'search']);

    // 3. Feature 156: Virtual Try-On
    Route::get('/try-on/eligibility/{productId}', [VirtualTryOnController::class, 'eligibility']);
    Route::post('/try-on/generate', [VirtualTryOnController::class, 'generate']);

    // 4. Features 157, 158, 159: AR & 360° Media Assets
    Route::get('/products/{id}/media-360-ar', [ProductMedia360Controller::class, 'get360AndAr']);

    // 5. Feature 161: Live Shopping Sessions
    Route::get('/live-sessions', [LiveShoppingController::class, 'index']);
    Route::get('/live-sessions/{idOrSlug}', [LiveShoppingController::class, 'show']);
    Route::post('/live-sessions/{id}/join', [LiveShoppingController::class, 'join']);
    Route::post('/live-sessions/{id}/like', [LiveShoppingController::class, 'like']);

    // 6. Feature 171: Passkey / WebAuthn Public Authentication
    Route::get('/auth/passkey/login-options', [PasskeyController::class, 'generateLoginOptions']);
    Route::post('/auth/passkey/verify-login', [PasskeyController::class, 'verifyLogin']);

    // Phase 6 Authenticated Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        // Passkey Registration & Management
        Route::get('/auth/passkey/register-options', [PasskeyController::class, 'generateRegisterOptions']);
        Route::post('/auth/passkey/verify-register', [PasskeyController::class, 'verifyRegister']);
        Route::get('/auth/passkeys', [PasskeyController::class, 'index']);
        Route::delete('/auth/passkeys/{id}', [PasskeyController::class, 'destroy']);

        // Seller/Admin AR & 360 Asset Management
        Route::post('/products/{id}/media-360', [ProductMedia360Controller::class, 'uploadFrames']);
        Route::put('/products/{id}/ar', [ProductMedia360Controller::class, 'updateAr']);

        // Seller Live Sessions
        Route::post('/seller/live-sessions', [LiveShoppingController::class, 'store']);
        Route::patch('/seller/live-sessions/{id}/status', [LiveShoppingController::class, 'updateStatus']);
    });
});
