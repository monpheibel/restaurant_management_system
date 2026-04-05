# API Reference

Base path: `/api`

Runtime note:
- API endpoints should be served by Apache/LAMPP from your virtual host, for example `http://rms.test:8080`.
- Live Server (`127.0.0.1:5500`) cannot execute PHP API routes.

## Auth & Session
Most endpoints rely on PHP session auth. Log in first via:
- `POST /api/auth/login.php`

`/api/auth/login.php` accepts JSON body and form POST fields.

Sample payload:
```json
{ "email": "customer.brenda@pheibel.com", "password": "password" }
```

---

## Search APIs

### `GET /api/search.php`
Search restaurants, menu items, and optionally orders.

Query params:
- `q` (required, min 2 chars)
- `restaurant_limit`, `item_limit`, `order_limit`
- `include_orders` (`true/false`)

Response:
- `results.restaurants[]`
- `results.items[]`
- `results.orders[]`

---

## Order APIs

### `GET /api/orders/list.php`
List orders with filters and pagination.

Query params:
- `page`, `limit`
- `status`, `payment_status`
- `restaurant_id`, `customer_id` (admin)
- `q`
- `include_items=true|false`

### `GET /api/orders/get.php?order_id=...`
Get one order with `items` and `payments`.

### `GET /api/orders/items.php?order_id=...`
Get only order items for a specific order.

### `GET /api/orders/search.php?q=...`
Search orders by order number/id/customer/restaurant.

### `GET /api/orders/get_pending.php?restaurant_id=...`
Vendor/admin endpoint used by seller dashboard polling.

### `POST /api/orders/update_status.php`
Update order lifecycle status.

Payload:
```json
{ "order_id": 123, "status": "confirmed" }
```
Allowed statuses:
- `pending`, `confirmed`, `preparing`, `ready`, `delivered`, `cancelled`

### `POST /api/orders/create.php`
Create a new order and line items.

Payload:
```json
{
  "restaurant_id": 1,
  "delivery_address": "Buea Campus Gate",
  "payment_method": "mobile_money",
  "items": [
    { "menu_item_id": 10, "quantity": 2, "special_instructions": "No pepper" }
  ]
}
```

---

## Admin API

### `POST /api/admin/toggle_restaurant.php`
Activate/deactivate restaurant.

Payload:
```json
{ "restaurant_id": 1, "action": "activate" }
```

---

## Cart API

### `POST /api/cart/add.php`
Add an item to user cart.

Payload:
```json
{ "item_id": 10, "quantity": 1 }
```

---

## Response Pattern
Typical responses:
- Success:
```json
{ "success": true, "message": "...", "data": {} }
```
- Error:
```json
{ "success": false, "message": "..." }
```
