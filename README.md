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
- **Vendor Registration & KYC Verification**: Vendors register storefront profiles (`vendor_stores`). Admins moderate KYC documents (`kyc_status`) to activate stores.
- **Vendor Storefronts**: Public storefront URLs (`/api/v1/stores/{slug}`) displaying vendor info and seller products.
- **Automatic Commission Engine (`VendorCommissionService`)**: Upon order payment capture (`PaymentSuccessEvent`), automatically calculates marketplace commission (e.g. 10%), credits net earnings to the vendor wallet, and logs transaction ledgers.
- **Vendor Wallet & Payout Settlements**: Vendor wallets (`vendor_wallets`) tracking balances and withdrawal requests (`SET-YYYYMMDD-XXXXX`).

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog & Stores (Modules 2, 3, 11)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered product catalog
- `GET /api/v1/stores` - List active vendor stores
- `GET /api/v1/stores/{slug}` - Public vendor storefront & products

### Protected Vendor Operations (Module 11 - *Sanctum + Seller*)
- `POST /api/v1/vendor/store` - Register vendor store profile
- `GET /api/v1/vendor/store` - Current vendor store details
- `GET /api/v1/vendor/dashboard` - Vendor dashboard metrics
- `GET /api/v1/vendor/products` - Vendor's owned products
- `GET /api/v1/vendor/orders` - Vendor's line item orders
- `GET /api/v1/vendor/wallet` - Vendor wallet balance & transaction ledger
- `POST /api/v1/vendor/settlements/request` - Request payout settlement

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/analytics/overview` - Admin dashboard BI overview
- `GET /api/v1/admin/vendor/stores` - List vendor stores
- `PATCH /api/v1/admin/vendor/stores/{id}/kyc` - Moderate vendor KYC & activate store
- `GET /api/v1/admin/vendor/settlements` - List payout settlement requests
- `PATCH /api/v1/admin/vendor/settlements/{id}/process` - Process payout settlement (`paid`/`rejected`)

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
