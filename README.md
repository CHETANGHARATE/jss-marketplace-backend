# JSS Solutions Multi Vendor Marketplace - Laravel 12 REST API Backend

Production-ready backend API service powering the JSS Solutions Multi Vendor Marketplace. Built using **Laravel 12 (PHP 8.3+)**, **Laravel Sanctum API Authentication**, and **Spatie Laravel-Permission (RBAC)**.

---

## Architecture Summary by Module

### Module 1: Auth, Users & Settings
- Token-based Sanctum auth, dual login (email/phone), rate limiting (6/min), role escalation prevention.
- Spatie Roles (`admin`, `seller`, `customer`).
- Public cached settings vs. Private admin credentials.

### Module 2: Catalog Foundation
- Unlimited nesting categories (multilingual `en`, `hi`, `mr`), Brands, Attributes & Values, and Polymorphic Media Library.

### Module 3: Product Management Engine
- **Product Core & Pricing Engine**: Original price, offer price, cost price, automatic discount calculation, stock status (`in_stock`, `out_of_stock`, `pre_order`), rating & reviews count.
- **Product Gallery & Specifications**: Multi-image galleries, key-value technical specifications, attribute mappings (Color, Size, RAM/Storage), and tags.
- **Approval Workflow**: Status transitions (`draft`, `pending_approval`, `approved`, `rejected`, `archived`).

### Module 4: Inventory & Warehouse Management
- **Multi-Warehouse Fulfillment**: Fulfillment centers (`warehouses` table) with contact details, primary flags, addresses, and soft deletes.
- **Warehouse Inventory Tracking**: Warehouse-product inventory mapping (`quantity`, `reserved_quantity`, `available_quantity`, `low_stock_threshold`).
- **Stock Movement Audit Ledger**: Immutable audit trail ledger entries for all stock operations in `stock_movements` table.

### Module 5: Shopping Cart & Wishlist System
- **Dual Session Cart Support**: Single active cart per authenticated user or guest session identified via `X-Session-ID` header.
- **Inventory Stock Validation**: Item additions and quantity updates are validated against real-time physical stock (`products.stock_quantity`).
- **Guest-to-User Cart Merge**: `CartMergeService` merges guest cart items into authenticated user accounts upon login.
- **Customer Wishlist Engine**: Toggle add/remove items to user wishlists with instant feedback.
- **Abandoned Cart Analytics**: Admin report for carts abandoned >24 hours with active items.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `POST /api/v1/auth/forgot-password` - Send reset link
- `POST /api/v1/auth/reset-password` - Reset password
- `GET /api/v1/auth/me` - User profile (*Protected*)
- `GET /api/v1/settings` - Public settings

### Public Catalog & Products (Modules 2 & 3)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/brands` - Active brands list
- `GET /api/v1/products` - Filtered & paginated product catalog
- `GET /api/v1/products/{slug}` - Product detail page

### Shopping Cart & Wishlist (Module 5)
- `GET /api/v1/cart` - Fetch current cart (uses `auth:sanctum` or `X-Session-ID` header)
- `POST /api/v1/cart/items` - Add item to cart with stock validation
- `PUT /api/v1/cart/items/{id}` - Update item quantity in cart
- `DELETE /api/v1/cart/items/{id}` - Remove item from cart
- `POST /api/v1/cart/clear` - Clear all items from cart
- `POST /api/v1/cart/merge` - Merge guest cart into user account (*Protected*)
- `GET /api/v1/wishlist` - View user wishlist (*Protected*)
- `POST /api/v1/wishlist/toggle` - Toggle product in user wishlist (*Protected*)
- `DELETE /api/v1/wishlist/{productId}` - Remove product from wishlist (*Protected*)

### Admin Management (*Protected: Sanctum + Admin*)
- `POST /api/v1/admin/products` - Create product
- `POST /api/v1/admin/inventories/add-stock` - Inbound stock addition to warehouse
- `POST /api/v1/admin/inventories/transfer` - Inter-warehouse stock transfer
- `GET /api/v1/admin/carts/abandoned` - Abandoned carts report

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
