# Installation Guide

## Requirements
- PHP 7.4+
- MySQL or MariaDB
- Apache/Nginx (XAMPP/LAMPP recommended)
- `pdo_mysql` enabled

## Local Setup (LAMPP/XAMPP)
1. Place project in web root:
   - Example: `/opt/lampp/htdocs/restaurant_management_system`
2. Start services:
   - Apache
   - MySQL
3. Initialize DB and seed data:
   ```bash
   php setup_database.php
   ```
4. Create a virtual host:
   - Add `127.0.0.1 rms.test` to `/etc/hosts`
   - Add an Apache vhost on port `8080` pointing `rms.test` to `/opt/lampp/htdocs/restaurant_management_system`
   - Use [docs/apache-vhost.conf](/opt/lampp/htdocs/restaurant_management_system/docs/apache-vhost.conf) as the starting template
5. Reload Apache.

## Environment
The app reads database settings from environment variables and falls back to defaults:
- `DB_HOST` (default `localhost`)
- `DB_NAME` (default `restaurant_management_db`)
- `DB_USER` (default `root`)
- `DB_PASS` (default empty)

Socket used by default in this project:
- `/opt/lampp/var/mysql/mysql.sock`

## Run
Open:
- `http://rms.test:8080/`
- `http://rms.test:8080/login.php`

## Verify
- Login with seeded account (see README).
- Open buyer/vendor/admin dashboard by role.
- For API checks, hit endpoints under `/api/...` while logged in.

## Static Preview Note
- PHP pages/APIs (`*.php`, `/api/*`) should be served from the virtual host.
- In the current LAMPP configuration Apache listens on `8080`, so use `http://rms.test:8080/`.
- If you open `login.html` in a static preview tool, set `<meta name="app-base-url" content="http://rms.test:8080/">` so the JavaScript login flow still targets the PHP backend.
