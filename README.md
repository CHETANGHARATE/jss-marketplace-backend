# JSS Solutions Multi Vendor Marketplace - Laravel 12 REST API Backend

Production-ready backend API service powering the JSS Solutions Multi Vendor Marketplace. Built using **Laravel 12 (PHP 8.3+)**, **Laravel Sanctum API Authentication**, and **Spatie Laravel-Permission (RBAC)**.

---

## Module 1 Architecture Summary

Module 1 establishes the foundational infrastructure, security rules, and user management for the marketplace:

- **Authentication & Security**:
  - Token-based API Authentication via **Laravel Sanctum**.
  - Dual login support using either `email` or `phone`.
  - Rate limiting on auth endpoints (6 attempts/minute).
  - Explicit role escalation blocking during public registration (`POST /api/v1/auth/register` defaults to `customer` and rejects `admin` requests).
  - Password Reset & Email Verification notification pipelines.
- **Role-Based Access Control (RBAC)**:
  - Powered by `spatie/laravel-permission` as the single source of truth for authorization policies.
  - Pre-seeded roles: `admin`, `seller`, `customer`.
- **System Settings Architecture**:
  - Configured via key-value JSON storage (`settings` model) with group tags (`general`, `payment`, `smtp`, `shipping`, `social`, `maintenance`).
  - **Security Separation**: Public settings (`is_public = true`) are cached (`3600s`) and exposed via `GET /api/v1/settings`. Private settings (e.g. SMTP passwords, Razorpay secrets) are restricted to authenticated Admins (`GET /api/v1/settings?all=true`).

---

## API Endpoints Reference (Module 1)

### Public Authentication Endpoints (Rate Limited 6/min)
- `POST /api/v1/auth/register` - Register a customer/vendor account
- `POST /api/v1/auth/login` - Login via email or phone & return Bearer token
- `POST /api/v1/auth/forgot-password` - Send password reset link to user email
- `POST /api/v1/auth/reset-password` - Complete password reset using token

### Authenticated Account Operations (`Authorization: Bearer <token>`)
- `GET /api/v1/auth/me` - Fetch authenticated user profile and permissions
- `PUT /api/v1/auth/profile` - Update user profile & password
- `POST /api/v1/auth/logout` - Revoke current Bearer token
- `POST /api/v1/auth/email/verification-notification` - Resend verification email

### System Settings Endpoints
- `GET /api/v1/settings` - Public application settings (Cached)
- `PUT /api/v1/admin/settings` - Create or update settings (*Admin Only*)

---

## Installation & Setup Instructions

### Prerequisites
- PHP 8.3 or higher
- Composer 2.x
- MySQL 8.0+ / PostgreSQL 15+

### Installation Steps

1. **Install Dependencies**:
   ```bash
   composer install
   ```

2. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Migration & Seeding**:
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Default Test Accounts**:
   - **Admin**: `admin@jss.solutions` | Password: `Password123!`
   - **Vendor**: `seller@jss.solutions` | Password: `Password123!`
   - **Customer**: `customer@jss.solutions` | Password: `Password123!`

5. **Run Automated Test Suite**:
   ```bash
   php artisan test
   ```

---

## Verification & Test Coverage

- `tests/Feature/AuthTest.php`: Verifies customer registration, role escalation blocking, login, Sanctum profile retrieval, and logout.
- `tests/Feature/SettingTest.php`: Verifies public vs. private settings isolation and Admin authorization checks.
