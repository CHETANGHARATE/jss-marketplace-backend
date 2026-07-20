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
- **Product Core & Pricing Engine**: Original price, offer price, cost price, automatic discount calculation, stock status, rating & reviews count.

### Module 4: Inventory & Warehouse Management
- Multi-warehouse fulfillment centers with stock movement audit ledgers (`stock_movements`).

### Module 5: Shopping Cart & Wishlist System
- Dual session active carts (Auth user or Guest session ID). Stock-validated item additions and guest cart merging.

### Module 6: Orders & Checkout Engine
- Customer saved addresses, atomic checkout engine, order number generation, and cancellation stock restoration.

### Module 7: Payment Gateway Driver Architecture & Transactions
- **Pluggable Gateway Contract**: `PaymentGatewayInterface` decouples payment logic from specific providers.
- **Razorpay Primary Integration**: Primary Indian gateway (`RazorpayGateway`) supporting order generation, HMAC-SHA256 signature verification, and webhook processing.
- **Stripe Foundation**: Pluggable international gateway driver (`StripeGateway`).
- **Idempotent Webhook Listener**: Public webhook handler `/api/v1/payments/webhook/{gateway}` verifying signatures and de-duplicating events via audit logs (`payment_logs`).
- **Events & Listeners**: Dispatches `PaymentSuccessEvent` upon payment capture, automatically setting order `payment_status = paid` and `status = confirmed`.
- **Refund Processing**: `Refund` foundation tracking admin-initiated payment refunds.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog & Products (Modules 2 & 3)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered & paginated product catalog

### Public Gateway Webhook Listener (Module 7)
- `POST /api/v1/payments/webhook/{gateway}` - Public HMAC signature-verified webhook handler

### Protected Customer Operations (Modules 5, 6, 7 - *Sanctum*)
- `GET /api/v1/cart` - Fetch current active cart
- `POST /api/v1/checkout/process` - Execute checkout & place order
- `POST /api/v1/payments/initiate` - Initiate gateway order for frontend popup checkout
- `POST /api/v1/payments/verify` - Verify payment signature & capture payment
- `GET /api/v1/payments/{paymentNumber}` - Payment details
- `GET /api/v1/orders` - Customer order history
- `POST /api/v1/orders/{orderNumber}/cancel` - Cancel order & restore inventory stock

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/orders` - List all marketplace orders
- `GET /api/v1/admin/payments` - List all payments
- `GET /api/v1/admin/payments/logs` - Gateway audit logs
- `POST /api/v1/admin/payments/refund` - Process order refund

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
