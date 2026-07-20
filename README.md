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
- **Verified Purchase Reviews**: Only customers with delivered/confirmed orders for a product can submit 1-5 star ratings & reviews.
- **Review Moderation & Aggregate Statistics**: Moderation workflow (`pending`, `approved`, `rejected`). Dispatches `ReviewApprovedEvent` to recalculate `products.rating` average and `products.reviews_count`.
- **Customer Product Q&A**: Public product question submission with admin/seller answers.
- **Review Reporting**: Mechanism for customers to flag inappropriate or abusive reviews.
- **Threaded Support Tickets**: Multi-category support ticket system (`TKT-YYYYMMDD-XXXXX`) supporting threaded conversation replies between customers and support admins.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog & Products (Modules 2, 3, 9)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered product catalog
- `GET /api/v1/products/{id}/reviews` - Approved reviews & rating summary
- `GET /api/v1/products/{id}/questions` - Answered product Q&As

### Protected Customer Operations (Modules 5-9 - *Sanctum*)
- `GET /api/v1/cart` - Fetch active cart
- `POST /api/v1/checkout/process` - Execute checkout
- `POST /api/v1/payments/initiate` - Initiate gateway checkout popup
- `POST /api/v1/payments/verify` - Verify payment signature
- `POST /api/v1/reviews` - Submit review for verified purchase
- `POST /api/v1/questions` - Ask product question
- `POST /api/v1/support/tickets` - Create support ticket
- `GET /api/v1/support/tickets/{ticketNumber}` - View ticket conversation
- `POST /api/v1/support/tickets/{ticketNumber}/reply` - Reply to support ticket

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/orders` - List all marketplace orders
- `GET /api/v1/admin/payments` - List all payments
- `GET /api/v1/admin/reviews` - Review moderation queue
- `PATCH /api/v1/admin/reviews/{id}/moderate` - Moderate review (`approved`/`rejected`)
- `POST /api/v1/admin/questions/{id}/answer` - Answer product question
- `GET /api/v1/admin/support/tickets` - Support tickets queue
- `POST /api/v1/admin/support/tickets/{id}/reply` - Admin reply to ticket

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
