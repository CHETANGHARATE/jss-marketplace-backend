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
- **Stock Movement Audit Ledger**: Every stock addition, deduction, audit adjustment, reservation, release, or transfer records a immutable ledger entry in `stock_movements` table.
- **Thread-Safe Inventory Service**: `InventoryService` enforces atomic DB transactions with row-level pessimistic locking (`lockForUpdate`), automatic product stock sync (`products.stock_quantity` & `products.stock_status`).

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
- `GET /api/v1/categories/{slug}` - Category details
- `GET /api/v1/brands` - Active brands list
- `GET /api/v1/attributes` - Filterable attributes
- `GET /api/v1/products` - Filtered & paginated product catalog
- `GET /api/v1/products/featured` - Featured products
- `GET /api/v1/products/trending` - Trending products
- `GET /api/v1/products/{slug}` - Product detail page

### Public Warehouses (Module 4)
- `GET /api/v1/warehouses` - List active fulfillment warehouses
- `GET /api/v1/warehouses/{id}` - Warehouse details

### Admin & Inventory Management (*Protected: Sanctum + Admin*)
- `POST /api/v1/admin/warehouses` - Create warehouse
- `DELETE /api/v1/admin/warehouses/{id}` - SoftDelete warehouse
- `GET /api/v1/admin/inventories` - Inventory list (Filterable by `low_stock=true`, `warehouse_id`, `product_id`)
- `POST /api/v1/admin/inventories/add-stock` - Inbound stock addition to warehouse
- `POST /api/v1/admin/inventories/adjust-stock` - Audit stock adjustment
- `POST /api/v1/admin/inventories/transfer` - Inter-warehouse stock transfer
- `GET /api/v1/admin/inventories/low-stock` - Low stock alert report
- `GET /api/v1/admin/stock-movements` - Stock movement audit trail ledger

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
