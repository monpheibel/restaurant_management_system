# Changelog

## 2026-04-05

### Updated
- Switched local hosting guidance from `localhost/restaurant_management_system` to an Apache virtual host example (`rms.test`).
- Updated frontend auth URL resolution to work cleanly from a virtual-host root or a configured `app-base-url`.
- Relaxed login API CORS checks to allow common local virtual-host domains such as `.test` and `.local`.
- Added Apache virtual-host setup documentation and aligned the academic report with the new deployment model.
- Aligned local URLs with the current LAMPP Apache port (`8080`) and updated the sample vhost accordingly.

## 2026-03-30

### Updated
- Documentation refresh for current runtime flow:
  - clarified static (`:5500`) vs PHP (`localhost`) behavior
  - updated entry-point URLs in `README.md` and `docs/INSTALLATION.md`
  - updated architecture and payment docs to include both `receipt.html` and `pages/payment/receipt.php`
  - added troubleshooting guidance for:
    - `ERR_CONNECTION_REFUSED`
    - `405 Method Not Allowed` on `:5500/api/...`
- Fixed markdown formatting issue in `docs/API_CURL_EXAMPLES.md` (unclosed code fence).

## 2026-03-27

### Added
- New order/search API infrastructure:
  - `api/common.php`
  - `api/search.php`
  - `api/orders/create.php`
  - `api/orders/get.php`
  - `api/orders/get_pending.php`
  - `api/orders/items.php`
  - `api/orders/list.php`
  - `api/orders/search.php`
  - `api/orders/update_status.php`
- Seller product upload page:
  - `pages/restaurant/upload_products.php`

### Updated
- `config/database.php`
  - fixed trailing output issue
  - preserved global PDO compatibility
- `pages/restaurant/dashboard.php`
  - rebuilt seller hub layout
  - integrated order polling/status API usage
  - integrated local uploaded product display

### Payment Context Improvements
- End-to-end forwarding of payment verification metadata to receipt flow.
