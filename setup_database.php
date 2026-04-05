<?php
/**
 * Database setup script
 * Run this to initialize the database with tables and sample data
 */

// Load environment variables
$env = [];
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$host = $env['DB_HOST'] ?? 'localhost';
$dbname = $env['DB_NAME'] ?? 'restaurant_management_db';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';

try {
    // Connect to MySQL without specifying database
    $db = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if not exists
    $db->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");

    // Now connect to the specific database
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('customer', 'vendor', 'admin') NOT NULL DEFAULT 'customer',
            status ENUM('active', 'inactive') DEFAULT 'active',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create restaurants table
    $db->exec("
        CREATE TABLE IF NOT EXISTS restaurants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Create menu_items table
    $db->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(255),
            is_available TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )
    ");

    // Create orders table
    $db->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            restaurant_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            delivery_address TEXT,
            customer_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id),
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
        )
    ");

    // Create reviews table
    $db->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            restaurant_id INT NOT NULL,
            order_id INT,
            rating INT CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id),
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ");

    // Create payments table
    $db->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            transaction_id VARCHAR(255) UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            payment_method VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ");

    // Insert sample data
    // Admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES ('Admin User', 'admin@example.com', '$adminPassword', 'admin')");

    // Vendor user
    $vendorPassword = password_hash('vendor123', PASSWORD_DEFAULT);
    $db->exec("INSERT IGNORE INTO users (name, email, password, phone, role) VALUES ('Mireille Vendor', 'vendor.mireille@pheibel.com', '$vendorPassword', '+237676883257', 'vendor')");

    // Customer user
    $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
    $db->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES ('Customer User', 'customer@example.com', '$customerPassword', 'customer')");

    // Update all vendor phone numbers
    $db->exec("UPDATE users SET phone = '+237676883257' WHERE role = 'vendor'");

    // Deactivate all vendors except vendor.mireille@pheibel.com
    $db->exec("UPDATE users SET is_active = 0 WHERE role = 'vendor' AND email != 'vendor.mireille@pheibel.com'");
    $db->exec("UPDATE users SET is_active = 1 WHERE email = 'vendor.mireille@pheibel.com'");

    // Get vendor id
    $stmt = $db->query("SELECT id FROM users WHERE email = 'vendor.mireille@pheibel.com'");
    $vendorId = $stmt->fetch()['id'];

    // Insert restaurant
    $db->exec("INSERT IGNORE INTO restaurants (owner_id, name, location) VALUES ($vendorId, 'Campus Cafe', 'Main Campus')");

    // Update all restaurants to be owned by this vendor
    $db->exec("UPDATE restaurants SET owner_id = $vendorId");

    // Insert menu items
    $db->exec("INSERT IGNORE INTO menu_items (restaurant_id, name, description, price) VALUES
        ($vendorId, 'Achu', 'Traditional Cameroonian dish', 1000.00, '../../images/achu.jpeg'),
        ($vendorId, 'Ndole', 'Cameroonian vegetable stew', 1000.00, '../../images/ndole.jpeg'),
        ($vendorId, 'Ekwang', 'Cocoyam leaves wrapped in banana leaves', 1000.00, '../../images/ekwang.jpeg')
    ");

    echo "Database setup completed successfully!\n";
    echo "Database: $dbname\n";
    echo "Sample accounts:\n";
    echo "Admin: admin@example.com / admin123\n";
    echo "Vendor: vendor.mireille@pheibel.com / vendor123 (Active - receives all payments via Transak)\n";
    echo "Customer: customer@example.com / customer123\n";

} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
?>