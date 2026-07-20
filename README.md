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
- Pluggable gateway contract (`PaymentGatewayInterface`), Razorpay primary integration, Stripe foundation, idempotent webhooks, and refund processing.

### Module 8: Shipping, Delivery & Logistics Engine
- **Geographic Shipping Zones & Methods**: Shipping rules based on pincodes/states (`ShippingZone`) and cost calculations by base rate + weight (`ShippingCalculatorService`).
- **Provider-Independent Courier Driver Architecture**: `CourierDriverInterface` contract implemented by `DelhiveryDriver` and `LocalCourierDriver` managed via `CourierManager`.
- **Shipment Management & AWB Generation**: Unique shipment numbers (`SHP-YYYYMMDD-XXXXX`), AWB tracking labels, and real-time tracking logs.
- **Shipment Timeline & Events**: Detailed audit logs (`shipment_logs`) and `ShipmentStatusUpdatedEvent` listeners automatically synchronizing order statuses to `shipped` or `delivered`.

---

## API Endpoints Reference

### Public Authentication & Settings (Module 1)
- `POST /api/v1/auth/register` - Register account
- `POST /api/v1/auth/login` - Login via email/phone
- `GET /api/v1/auth/me` - User profile (*Protected*)

### Public Catalog & Products (Modules 2 & 3)
- `GET /api/v1/categories` - Fetch category tree
- `GET /api/v1/products` - Filtered & paginated product catalog

### Public Shipping & Tracking (Module 8)
- `POST /api/v1/shipping/calculate` - Calculate shipping cost by destination pincode
- `GET /api/v1/shipments/track/{trackingNumber}` - Public AWB tracking timeline

### Protected Customer Operations (Modules 5, 6, 7, 8 - *Sanctum*)
- `GET /api/v1/cart` - Fetch current active cart
- `POST /api/v1/checkout/process` - Execute checkout & place order
- `POST /api/v1/payments/initiate` - Initiate gateway order for frontend popup checkout
- `POST /api/v1/payments/verify` - Verify payment signature & capture payment
- `GET /api/v1/orders` - Customer order history
- `GET /api/v1/orders/{orderNumber}/shipment` - Customer shipment details & tracking timeline

### Admin Management (*Protected: Sanctum + Admin*)
- `GET /api/v1/admin/orders` - List all marketplace orders
- `GET /api/v1/admin/payments` - List all payments
- `GET /api/v1/admin/shipping-zones` - List shipping zones
- `POST /api/v1/admin/shipping-zones` - Add shipping zone
- `GET /api/v1/admin/couriers` - List courier partners
- `POST /api/v1/admin/couriers` - Add courier partner
- `GET /api/v1/admin/shipments` - List all marketplace shipments
- `POST /api/v1/admin/shipments/create` - Create shipment & generate AWB label
- `PATCH /api/v1/admin/shipments/{id}/status` - Update shipment status and location log

---

## Installation & Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```
