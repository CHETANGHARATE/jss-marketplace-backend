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
- Pluggable search driver architecture (`SearchDriverInterface`), native database driver (`DatabaseSearchDriver`) with dynamic facets, autocomplete query suggestions (`/api/v1/search/autocomplete`), and recommendation services (`RecommendationService`).

### Module 14: Performance, Security & DevOps Infrastructure
- **System Health Diagnostics (`GET /api/v1/health`)**: Verifies MySQL database connectivity, Redis cache layer status, and available disk storage.
- **Scheduled Maintenance Jobs (`routes/console.php`)**: Automated daily cleanup of abandoned carts (`carts:clean-expired`) and daily BI analytics pre-computation (`analytics:generate-daily`).
- **Security Hardening Middleware (`SecurityHeadersMiddleware`)**: Enforces HTTP security headers (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Strict-Transport-Security`).
- **Docker & Container Stack**: Multi-stage `Dockerfile` and `docker-compose.yml` orchestrating App (Laravel FPM), Nginx, MySQL 8.0, and Redis containers.
- **CI/CD Pipeline (`.github/workflows/ci.yml`)**: Automated GitHub Actions workflow for linting, database migrations, and feature testing.

---

## API Endpoints Reference

### Public Health & Diagnostics (Module 14)
- `GET /api/v1/health` - System health diagnostic status (DB, Redis, Storage)

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

### Protected Customer Operations (Modules 5-14 - *Sanctum*)
- `GET /api/v1/cart` - Fetch active cart
- `POST /api/v1/checkout/process` - Execute checkout
- `GET /api/v1/recommendations/personalized` - Customer personalized recommendations

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/analytics/overview` - Admin dashboard BI overview
- `GET /api/v1/admin/search/analytics` - Search queries analytics & zero-result terms
- `GET /api/v1/admin/search/synonyms` - Search synonyms list

---

## Installation & Setup Instructions

### Local Environment
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```

### Docker Deployment
```bash
docker-compose up -d --build
docker-compose exec app php artisan migrate --seed
```
