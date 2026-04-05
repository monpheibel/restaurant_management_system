<?php
session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$requestHost = $_SERVER['HTTP_HOST'] ?? '';

function isAllowedDevelopmentOrigin(string $origin, string $requestScheme, string $requestHost): bool
{
    $parts = parse_url($origin);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }

    $originHost = strtolower($parts['host']);
    $currentOrigin = $requestHost ? sprintf('%s://%s', $requestScheme, $requestHost) : '';
    if ($currentOrigin !== '' && rtrim($origin, '/') === $currentOrigin) {
        return true;
    }

    if (in_array($originHost, ['localhost', '127.0.0.1'], true)) {
        return true;
    }

    foreach (['.test', '.localhost', '.local'] as $suffix) {
        if (str_ends_with($originHost, $suffix)) {
            return true;
        }
    }

    return false;
}

if ($origin && isAllowedDevelopmentOrigin($origin, $requestScheme, $requestHost)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Use POST.'
        ]);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }

    $email = trim($data['email']);
    $password = $data['password'];

    if (empty($email) || empty($password)) {
        throw new Exception('Email and password cannot be empty');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $conn = getDBConnection();

    // Get user from database
    $stmt = $conn->prepare("SELECT id, email, password, name, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Invalid email or password');
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password');
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    // Log successful login
    error_log("User {$user['email']} logged in as {$user['role']}");

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'role' => $user['role'],
        'name' => $user['name']
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
