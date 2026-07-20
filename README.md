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
- **User Notifications Engine**: In-app notifications (`user_notifications`) for order updates, payment confirmations, and stock alerts.
- **Business Intelligence Analytics (`AnalyticsService`)**: Admin dashboard overview stats, 30-day sales trend, sales BI (AOV, payment gateway breakdown), customer analytics, and inventory health metrics.
- **Report Export Engine (`ReportExportService`)**: Export sales orders and inventory health reports as CSV downloads or JSON payloads.
- **Audit & Activity Logging**: Separate `audit_logs` tracking critical administrative mutations and `activity_logs` tracking user actions.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog & Products (Modules 2, 3, 9)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered product catalog

### Protected Customer Operations (Modules 5-10 - *Sanctum*)
- `GET /api/v1/cart` - Fetch active cart
- `POST /api/v1/checkout/process` - Execute checkout
- `POST /api/v1/payments/initiate` - Initiate gateway checkout popup
- `POST /api/v1/payments/verify` - Verify payment signature
- `GET /api/v1/notifications` - Customer in-app notifications
- `PATCH /api/v1/notifications/{id}/read` - Mark notification read

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/analytics/overview` - Admin dashboard overview & sales chart
- `GET /api/v1/admin/analytics/sales` - Detailed sales BI analytics
- `GET /api/v1/admin/analytics/customers` - Customer metrics & top buyers
- `GET /api/v1/admin/analytics/inventory` - Inventory health & stock value
- `GET /api/v1/admin/reports/sales/export` - Export sales report (CSV/JSON)
- `GET /api/v1/admin/reports/inventory/export` - Export inventory report (CSV/JSON)
- `GET /api/v1/admin/audit-logs` - System audit logs
- `GET /api/v1/admin/activity-logs` - User activity logs

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
