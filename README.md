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
- Rule-based discount engine (`PromotionEngineService`), flash sale campaigns (`FlashSaleService`), customer loyalty points, and referral rewards.

### Module 13: Search, Recommendations & Personalization
- **Pluggable Search Driver Architecture (`SearchDriverInterface`)**: Native database driver (`DatabaseSearchDriver`) with fulltext text searching, dynamic facets aggregation (category counts, brand counts, min/max price bounds), and Meilisearch driver foundation (`MeilisearchDriver`).
- **Search Autocomplete & Query Analytics**: Quick autocomplete suggestions (`/api/v1/search/autocomplete`) and query logging (`search_logs`).
- **Recommendation Engine (`RecommendationService`)**: Category & brand related products, trending products, and personalized customer recommendations based on wishlist categories and purchase history.
- **Search Synonyms & Admin BI Analytics**: Search query popularity, zero-result query tracking, and search synonyms management (`search_synonyms`).

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog, Discovery & Search (Modules 2, 3, 11, 13)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered product catalog
- `GET /api/v1/search` - Advanced search with dynamic facets & sorting
- `GET /api/v1/search/autocomplete` - Fast query autocomplete suggestions
- `GET /api/v1/products/{id}/related` - Related product recommendations
- `GET /api/v1/recommendations/trending` - Trending product recommendations

### Protected Customer Operations (Modules 5-13 - *Sanctum*)
- `GET /api/v1/cart` - Fetch active cart
- `POST /api/v1/checkout/process` - Execute checkout
- `GET /api/v1/recommendations/personalized` - Customer personalized recommendations

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/analytics/overview` - Admin dashboard BI overview
- `GET /api/v1/admin/search/analytics` - Search queries analytics & zero-result terms
- `GET /api/v1/admin/search/synonyms` - Search synonyms list
- `POST /api/v1/admin/search/synonyms` - Add search synonym mapping

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
