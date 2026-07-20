# JSS Solutions Multi Vendor Marketplace - Laravel 12 REST API Backend

Production-ready backend API service powering the JSS Solutions Multi Vendor Marketplace. Built using **Laravel 12 (PHP 8.3+)**, **Laravel Sanctum API Authentication**, and **Spatie Laravel-Permission (RBAC)**.

---

## Module 1 Architecture Summary

Module 1 establishes foundational infrastructure, security rules, and user account management:

- **Authentication & Security**: Token-based Sanctum auth, dual login (email/phone), rate limiting (6/min), role escalation prevention.
- **Role-Based Access Control (RBAC)**: Powered by `spatie/laravel-permission` (`admin`, `seller`, `customer`).
- **Settings Architecture**: Public cached settings (`GET /api/v1/settings`) vs. Private admin credentials (`GET /api/v1/settings?all=true`).

---

## Module 2 Architecture Summary (Catalog Foundation)

Module 2 provides the core catalog foundation and taxonomies:

- **Unlimited Nesting Categories**: Multilingual translatable names (`en`, `hi`, `mr`), recursive `children()` subcategories, Lucide icons, SEO tags, and soft deletes.
- **Brand Management**: Logos, websites, descriptions, featured flags, and category associations.
- **Attribute & Value System**: Translatable attribute definitions (`code`, `type`, `is_filterable`) and value options (e.g. Size, Color with hex codes, RAM/Storage, Material).
- **Polymorphic Media Library**: Reusable file uploads associated across models and media collections (`default`, `logo`, `banner`).

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register customer/seller account
- `POST /api/v1/auth/login` - Login via email or phone
- `POST /api/v1/auth/forgot-password` - Send reset link
- `POST /api/v1/auth/reset-password` - Reset password
- `GET /api/v1/auth/me` - Authenticated profile (*Protected*)
- `GET /api/v1/settings` - Public settings

### Public Catalog Foundation (Module 2)
- `GET /api/v1/categories` - Fetch nested category tree (Cached)
- `GET /api/v1/categories/{slug}` - Category details with subcategories & brands
- `GET /api/v1/brands` - Active brands list (Cached)
- `GET /api/v1/brands/{slug}` - Brand details with categories
- `GET /api/v1/attributes` - Filterable attributes with values
- `GET /api/v1/attributes/{id}` - Attribute details with values

### Admin Operations (*Protected: Sanctum + Admin Role*)
- `PUT /api/v1/admin/settings` - Manage system settings
- `POST /api/v1/admin/categories` - Create category
- `PUT /api/v1/admin/categories/{id}` - Update category
- `DELETE /api/v1/admin/categories/{id}` - Delete category
- `POST /api/v1/admin/brands` - Create brand
- `PUT /api/v1/admin/brands/{id}` - Update brand
- `DELETE /api/v1/admin/brands/{id}` - Delete brand
- `POST /api/v1/admin/attributes` - Create attribute & values
- `DELETE /api/v1/admin/attributes/{id}` - Delete attribute
- `POST /api/v1/admin/media/upload` - Upload file to media library

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
