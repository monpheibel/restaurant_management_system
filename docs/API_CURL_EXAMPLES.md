# API Curl Examples

Base URL used below:
```bash
BASE_URL="http://rms.test:8080"
```

Session-dependent endpoints require login first. These examples persist cookies in `cookies.txt`.

## 1) Login
```bash
curl -i -c cookies.txt -X POST "$BASE_URL/api/auth/login.php" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer.brenda@pheibel.com",
    "password": "password"
  }'
```

## 2) Global Search
```bash
curl -s "$BASE_URL/api/search.php?q=fish&include_orders=true"
```

## 3) Create Order
```bash
curl -i -b cookies.txt -X POST "$BASE_URL/api/orders/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_id": 1,
    "delivery_address": "Buea Campus Gate",
    "payment_method": "mobile_money",
    "items": [
      { "menu_item_id": 1, "quantity": 2, "special_instructions": "No pepper" }
    ]
  }'
```

## 4) List Orders
```bash
curl -s -b cookies.txt \
  "$BASE_URL/api/orders/list.php?page=1&limit=20&include_items=true"
```

## 5) Get Order by ID
```bash
curl -s -b cookies.txt "$BASE_URL/api/orders/get.php?order_id=1"
```

## 6) Get Order Items
```bash
curl -s -b cookies.txt "$BASE_URL/api/orders/items.php?order_id=1"
```

## 7) Search Orders
```bash
curl -s -b cookies.txt "$BASE_URL/api/orders/search.php?q=ORD&limit=10"
```

## 8) Get Pending Orders (Vendor/Admin)
```bash
curl -s -b cookies.txt "$BASE_URL/api/orders/get_pending.php?restaurant_id=1&limit=25"
```

## 9) Update Order Status (Vendor/Admin)
```bash
curl -i -b cookies.txt -X POST "$BASE_URL/api/orders/update_status.php" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1,
    "status": "confirmed"
  }'
```

## 10) Add Item to Cart
```bash
curl -i -b cookies.txt -X POST "$BASE_URL/api/cart/add.php" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": 1,
    "quantity": 1
  }'
```

## 11) Toggle Restaurant Status (Admin)
```bash
curl -i -b cookies.txt -X POST "$BASE_URL/api/admin/toggle_restaurant.php" \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_id": 1,
    "action": "activate"
  }'
```

## Notes
- Login/session checks are role-aware (`customer`, `vendor`, `admin`).
- For vendor/admin endpoints, log in with an account that has permission.
- To reset cookies, remove `cookies.txt` and log in again.
- Replace `rms.test:8080` with your configured virtual-host URL if different.
- API examples must run against Apache/LAMPP, not `127.0.0.1:5500`.
