# Restaurant Management System (Cameroon Edition)

A PHP + MySQL restaurant marketplace and operations platform with role-based dashboards, order APIs, and Cameroon-focused mobile-money payment flow.

## Highlights
- Ghibli/Warm earthy design theme across static and dashboard pages.
- Role-based experience for `admin`, `vendor`, and `customer`.
- Order/search API layer for buyer and vendor workflows.
- BELPAY payment flow with receipt verification metadata (provider, mode, reference, IPN status).
- Seller Hub UI with product upload and dashboard views.

## Quick Start
1. Install dependencies: PHP 7.4+, MySQL/MariaDB, Apache (XAMPP/LAMPP recommended).
2. Initialize database:
   ```bash
   php setup_database.php
   ```
3. Create a local Apache virtual host such as `rms.test` and point it to this project directory.
4. Open in browser:
   - `http://rms.test:8080/`
   - `http://rms.test:8080/login.php`

## Default Accounts
Password for seeded users: `password`
- Admin: `admin@pheibel.com`
- Vendor: `vendor.jacques@pheibel.com`
- Vendor: `vendor.mireille@pheibel.com`
- Vendor: `vendor.samuel@pheibel.com`
- Customer: `customer.brenda@pheibel.com`

## Documentation
- [Installation Guide](docs/INSTALLATION.md)
- [System Architecture](docs/SYSTEM_ARCHITECTURE.md)
- [Database Reference](docs/DATABASE.md)
- [API Reference](docs/API_REFERENCE.md)
- [API Curl Examples](docs/API_CURL_EXAMPLES.md)
- [Postman Collection](docs/postman_collection.json)
- [Apache Virtual Host Example](docs/apache-vhost.conf)
- [Payment Module](docs/PAYMENT_MODULE.md)
- [Seller Module](docs/SELLER_MODULE.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)
- [Changelog](docs/CHANGELOG.md)

## Key Paths
- UI entry points:
  - Static: `index.html`, `login.html`, `dashboard.html`, `admin.html`, `belpay.html`, `receipt.html`
  - PHP-aware: `index.php`, `login.php`, `pages/payment/receipt.php`
- Server dashboards: `pages/admin/dashboard.php`, `pages/buyer/dashboard.php`, `pages/restaurant/dashboard.php`
- APIs: `api/`
- Database config: `config/database.php`
- Schemas: `simple_schema.sql`, `database_schema.sql`

## Notes
- Current seller product publishing in `pages/restaurant/upload_products.php` stores drafts/products in browser `localStorage` for the Seller Hub view.
- Order management APIs are server-backed and role-aware.
- The recommended local runtime is a named Apache virtual host such as `rms.test`, not `localhost/restaurant_management_system`.
- In this LAMPP setup Apache listens on `8080`, so the virtual-host URL is `http://rms.test:8080/` unless you reconfigure Apache back to port `80`.
- If you use a static preview server, set the page `<meta name="app-base-url">` to your virtual-host URL so auth requests still reach PHP.
