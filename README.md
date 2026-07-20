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
- **Approval Workflow**: Status transitions (`draft`, `pending_approval`, `approved`, `rejected`, `archived`) with rejection reason audit log.
- **Multi-Criteria Product Filtering Engine**: Scoped filtering by category, subcategory, brand[], price min/max, rating, discount %, stock status, keyword search, and sorting.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `POST /api/v1/auth/forgot-password` - Send reset link
- `POST /api/v1/auth/reset-password` - Reset password
- `GET /api/v1/auth/me` - User profile (*Protected*)
- `GET /api/v1/settings` - Public settings

### Public Catalog Foundation (Module 2)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/categories/{slug}` - Category details
- `GET /api/v1/brands` - Active brands list
- `GET /api/v1/brands/{slug}` - Brand details
- `GET /api/v1/attributes` - Filterable attributes

### Public Product Engine (Module 3)
- `GET /api/v1/products` - Filtered & paginated product catalog  
  *Query Parameters*: `category`, `subcategory`, `brand`, `min_price`, `max_price`, `rating`, `discount`, `stock_status`, `search`, `sort_by` (`newest`, `price_low_high`, `price_high_low`, `rating`, `popularity`)
- `GET /api/v1/products/featured` - List featured products
- `GET /api/v1/products/trending` - List trending products
- `GET /api/v1/products/{slug}` - Product detail page with gallery, specs & attributes

### Admin & Vendor Product Management (*Protected: Sanctum*)
- `POST /api/v1/admin/products` - Create product (atomic DB transaction with images, specs & attributes)
- `PUT /api/v1/admin/products/{id}` - Update product details
- `DELETE /api/v1/admin/products/{id}` - SoftDelete product
- `PATCH /api/v1/admin/products/{id}/status` - Update approval status (`approved`, `rejected`, `archived`)

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
