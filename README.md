# JSS Solutions Multi Vendor Marketplace - Laravel 12 REST API Backend

Production-ready backend API service powering the JSS Solutions Multi Vendor Marketplace. Built using **Laravel 12 (PHP 8.3+)**, **Laravel Sanctum API Authentication**, and **Spatie Laravel-Permission (RBAC)**.

---

## Architecture Summary by Module

### Module 1: Auth, Users & Settings
- Token-based Sanctum auth, dual login (email/phone), rate limiting (6/min), role escalation prevention.
- Spatie Roles (`admin`, `seller`, `customer`).

### Module 2: Catalog Foundation
- Unlimited nesting categories (multilingual `en`, `hi`, `mr`), Brands, Attributes & Values, and Polymorphic Media Library.

### Module 3: Product Management Engine
- Product core & pricing engine, status approval workflow, multi-parameter filtering engine.

### Module 4: Inventory & Warehouse Management
- Multi-warehouse fulfillment centers with stock movement audit ledgers (`stock_movements`).

### Module 5: Shopping Cart & Wishlist System
- Dual session active carts (Auth user or Guest session ID). Stock-validated item additions and guest cart merging.

### Module 6: Orders & Checkout Engine
- Customer saved addresses, atomic checkout engine, order number generation, and cancellation stock restoration.

### Module 7: Payment Gateway Driver Architecture & Transactions
- Pluggable gateway contract (`PaymentGatewayInterface`), Razorpay primary integration, Stripe foundation, idempotent webhooks, and refund processing.

### Module 8: Shipping, Delivery & Logistics Engine
- Geographic shipping zones, `CourierDriverInterface` (Delhivery & Local courier drivers), AWB tracking, and automated order status listeners.

### Module 9: Customer Reviews, Ratings & Support System
- Verified purchase reviews, moderation workflow, rating recalculation listeners, product Q&A, and threaded support tickets.

### Module 10: Notifications, Business Intelligence Analytics & Administration
- User notifications engine (`user_notifications`), admin BI analytics (`AnalyticsService`), CSV report exports (`ReportExportService`), and separate `audit_logs` & `activity_logs`.

### Module 11: Multi-Vendor Marketplace Management
- Vendor storefronts (`vendor_stores`), KYC verification, automated commission engine (`VendorCommissionService`), vendor wallets (`vendor_wallets`), and payout settlements (`SET-YYYYMMDD-XXXXX`).

### Module 12: Promotions, Coupons & Marketing Automation
- **Coupon & Rule-Based Discount Engine (`PromotionEngineService`)**: Percentage or fixed-amount discounts with minimum order thresholds, max discount caps, global usage limits, and per-user usage caps (`coupons`, `coupon_usages`).
- **Flash Sales Campaigns (`FlashSaleService`)**: Time-bound flash sale campaigns (`flash_sales`, `flash_sale_products`) calculating live discounted flash prices.
- **Loyalty Program & Referral System**: Automatic loyalty point accrual (1 point per 10 currency spent) and referrer rewards on referee purchases (`loyalty_points`, `referrals`).

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog, Stores & Promotions (Modules 2, 3, 11, 12)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered product catalog
- `GET /api/v1/stores` - List active vendor stores
- `POST /api/v1/promotions/coupons/apply` - Validate & calculate coupon discount
- `GET /api/v1/promotions/flash-sales` - View active flash sale campaigns

### Protected Customer Operations (Modules 5-12 - *Sanctum*)
- `GET /api/v1/cart` - Fetch active cart
- `POST /api/v1/checkout/process` - Execute checkout
- `GET /api/v1/loyalty/points` - Customer loyalty points balance

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/analytics/overview` - Admin dashboard BI overview
- `GET /api/v1/admin/coupons` - List coupons
- `POST /api/v1/admin/coupons` - Create new coupon code
- `DELETE /api/v1/admin/coupons/{id}` - Delete coupon
- `GET /api/v1/admin/flash-sales` - List flash sales
- `POST /api/v1/admin/flash-sales` - Create flash sale campaign

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
