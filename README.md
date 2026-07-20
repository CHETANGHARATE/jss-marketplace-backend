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
- **Product Core & Pricing Engine**: Original price, offer price, cost price, automatic discount calculation, stock status (`in_stock`, `out_of_stock`, `pre_order`), rating & reviews count.
- **Approval Workflow**: Status transitions (`draft`, `pending_approval`, `approved`, `rejected`, `archived`).

### Module 4: Inventory & Warehouse Management
- **Multi-Warehouse Fulfillment**: Fulfillment centers (`warehouses` table) with contact details, primary flags, addresses, and soft deletes.
- **Stock Movement Audit Ledger**: Immutable audit trail ledger entries for all stock operations in `stock_movements` table.

### Module 5: Shopping Cart & Wishlist System
- Dual session active carts (Auth user or Guest session ID). Stock-validated item additions and guest cart merging.

### Module 6: Orders & Checkout Engine
- **Customer Address Management**: Saved shipping/billing addresses with default preferences.
- **Atomic Checkout Engine**: `CheckoutService` validates cart items, real-time inventory stock, captures address snapshots, generates unique `ORD-YYYYMMDD-XXXXX` order numbers, creates order line items, deducts warehouse stock with movement logs, and converts active carts inside a single database transaction.
- **Order Cancellation Workflow**: `OrderService` validates cancellation eligibility (`pending`/`confirmed`), restores deducted stock back to warehouse inventory, updates order status to `cancelled`, and records cancellation reasons.
- **Admin Marketplace Order Management**: Admin overview of all marketplace orders across users with filtering by status and search.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog & Products (Modules 2 & 3)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered & paginated product catalog
- `GET /api/v1/products/{slug}` - Product detail page

### Shopping Cart & Wishlist (Module 5)
- `GET /api/v1/cart` - Fetch current cart (uses `auth:sanctum` or `X-Session-ID` header)
- `POST /api/v1/cart/items` - Add item to cart with stock validation
- `POST /api/v1/cart/merge` - Merge guest cart into user account (*Protected*)

### Customer Addresses & Orders Engine (Module 6 - *Protected: Sanctum*)
- `GET /api/v1/addresses` - List saved customer addresses
- `POST /api/v1/addresses` - Save new shipping/billing address
- `DELETE /api/v1/addresses/{id}` - Delete saved address
- `POST /api/v1/checkout/process` - Execute checkout & place order
- `GET /api/v1/orders` - Customer order history
- `GET /api/v1/orders/{orderNumber}` - Single order details
- `POST /api/v1/orders/{orderNumber}/cancel` - Cancel order & restore inventory stock

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/orders` - List all marketplace orders (Status/Payment/Search filter)
- `GET /api/v1/admin/orders/{id}` - Admin order details
- `PATCH /api/v1/admin/orders/{id}/status` - Update order status (`confirmed`, `processing`, `shipped`, `delivered`, `cancelled`)

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
