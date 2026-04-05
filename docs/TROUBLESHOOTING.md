# Troubleshooting

## 1. Database connection failed
Check:
- MySQL service is running.
- Socket path exists: `/opt/lampp/var/mysql/mysql.sock`.
- DB credentials in environment match your setup.

Quick checks:
```bash
sudo /opt/lampp/lampp status
curl -I http://rms.test/login.php
curl -I http://rms.test:8080/login.php
```

## 2. Login succeeds but page denies access
Some pages check `$_SESSION['logged_in']`, others check `user_id/role`.
If you customize login flow, ensure all of these are set:
- `$_SESSION['logged_in'] = true`
- `$_SESSION['user_id']`
- `$_SESSION['role']`
- `$_SESSION['email']` (optional for display)

## 3. phpMyAdmin `pma` controluser error
If you use XAMPP/LAMPP phpMyAdmin and see controluser auth errors, verify:
- `controluser` / `controlpass` in `config.inc.php`
- `pma` MySQL user credentials match config
- MySQL port matches your local server

## 4. Search returns empty results
- Ensure query has at least 2 characters.
- Ensure restaurants/items are active/available.
- Confirm role session is valid if requesting order search data.

## 5. Seller pending orders not appearing
- Confirm vendor owns the restaurant ID requested.
- Confirm there are `pending` orders in `orders` table.
- Check browser console/network for API 403/500.

## 6. Payment callback/receipt data missing
- Start from `belpay.html` so required query params are generated.
- Ensure redirects keep `reference`, `provider`, and `ipn_status` fields.

## 7. Virtual host does not open
Cause: Apache/LAMPP is not running, the virtual host is not loaded, or the host name is missing from `/etc/hosts`.

Fix:
```bash
sudo /opt/lampp/lampp start
sudo /opt/lampp/lampp status
grep rms.test /etc/hosts
```

If port 80 is occupied:
```bash
sudo lsof -i :80
sudo systemctl stop apache2 nginx
sudo /opt/lampp/lampp restart
```

If Apache is running but the domain still fails:
```bash
sudo /opt/lampp/bin/apachectl -S
```

If `http://rms.test/` fails with connection refused:
- Check Apache's listen port in `/opt/lampp/etc/httpd.conf`
- This machine is configured with `Listen 8080`, so use `http://rms.test:8080/`

## 8. `405 Method Not Allowed` on `127.0.0.1:5500/api/...`
Cause: a static preview server serves files only and does not execute PHP routes.

Fix:
- Use `http://rms.test:8080/login.php` for the full PHP flow, or
- Set `<meta name="app-base-url" content="http://rms.test:8080/">` before using `login.html` in a static preview.
- Do not send API POSTs to `http://127.0.0.1:5500/api/...`.

## Useful Commands
```bash
# PHP syntax check
php -l pages/restaurant/dashboard.php
php -l api/orders/list.php

# Find API endpoints
find api -maxdepth 3 -type f | sort
```
