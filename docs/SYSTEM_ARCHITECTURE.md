# System Architecture

## Overview
The system is organized into three main layers:
- Presentation: static HTML pages + PHP dashboard pages.
- Application/API: REST-style PHP endpoints under `api/`.
- Data: MySQL database accessed via PDO singleton (`config/database.php`).

## Layers

### 1. Presentation Layer
- Static pages: `index.html`, `login.html`, `admin.html`, `dashboard.html`, `belpay.html`, `receipt.html`.
- PHP entry pages: `index.php`, `login.php`.
- Role pages:
  - Buyer: `pages/buyer/dashboard.php`
  - Vendor: `pages/restaurant/dashboard.php`, `pages/restaurant/upload_products.php`
  - Admin: `pages/admin/dashboard.php`
  - Receipt (server-rendered): `pages/payment/receipt.php`

### 2. API Layer
- Auth: `api/auth/login.php`
- Search: `api/search.php`
- Orders: `api/orders/*.php`
- Admin controls: `api/admin/toggle_restaurant.php`
- Cart: `api/cart/add.php`

Shared API helpers:
- `api/common.php` (JSON responses, method checks, session auth, PDO access)

### 3. Model/Data Layer
- Database singleton: `config/database.php`
- Models: `backend/php/models/*`
- Core model base: `backend/php/core/BaseModel.php`
- SQL schemas: `simple_schema.sql`, `database_schema.sql`

## Core Flows

### Login
1. UI calls `api/auth/login.php` (JSON) or submits `login_process.php` form.
2. Session is established with user role.
3. User is redirected to role dashboard.

### Buyer Search
1. Buyer dashboard JS calls `api/search.php?q=...`.
2. API returns matching restaurants, items, and optionally orders.

### Vendor Orders
1. Seller dashboard polls `api/orders/get_pending.php`.
2. Vendor accepts/rejects via `api/orders/update_status.php`.

### Payment
1. `belpay.html` captures mobile-money checkout metadata.
2. `pages/payment/process.php` builds payment context and callback.
3. `pages/payment/process_momo.php` processes MoMo and redirects to `receipt.html`.
4. Buyer dashboard can also open `pages/payment/receipt.php` for DB-backed receipt history links.
