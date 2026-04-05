<?php
/**
 * Common functions for the Restaurant Management System application
 */

/**
 * Redirect user based on their role
 */
function redirectBasedOnRole($role) {
    switch ($role) {
        case 'customer':
            header('Location: pages/buyer/menu_gallery.php');
            break;
        case 'vendor':
            header('Location: pages/restaurant/dashboard.php');
            break;
        case 'admin':
            header('Location: pages/admin/dashboard.php');
            break;
        default:
            header('Location: login.php');
            break;
    }
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'] ?? null
    ];
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Display error message
 */
function displayError($message) {
    return '<div class="alert alert-error">' . sanitize($message) . '</div>';
}

/**
 * Display success message
 */
function displaySuccess($message) {
    return '<div class="alert alert-success">' . sanitize($message) . '</div>';
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'preparing' => 'badge-primary',
        'ready' => 'badge-success',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger'
    ];

    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Check if user has permission
 */
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }

    $userRole = $_SESSION['role'];

    // Admin has all permissions
    if ($userRole === 'admin') {
        return true;
    }

    // Vendor permissions
    if ($userRole === 'vendor' && in_array($requiredRole, ['vendor'])) {
        return true;
    }

    // Customer permissions
    if ($userRole === 'customer' && in_array($requiredRole, ['customer'])) {
        return true;
    }

    return false;
}

/**
 * Get database connection
 */
function getDBConnection() {
    require_once __DIR__ . '/../config/database.php';
    return $GLOBALS['db'];
}
?>
