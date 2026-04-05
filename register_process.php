<?php
session_start();
require_once 'config/database.php';

function getUsersTableColumns(PDO $db): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    $columns = [];
    $stmt = $db->query('SHOW COLUMNS FROM users');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($row['Field'])) {
            $columns[$row['Field']] = true;
        }
    }

    return $columns;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_register'])) {
    header('Location: register.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$redirectBase = 'register.php?name=' . urlencode($name) . '&email=' . urlencode($email) . '&phone=' . urlencode($phone);

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
    header('Location: ' . $redirectBase . '&error=empty');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $redirectBase . '&error=invalid_email');
    exit;
}

if (strlen($password) < 8) {
    header('Location: ' . $redirectBase . '&error=password_short');
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: ' . $redirectBase . '&error=password_mismatch');
    exit;
}

try {
    $userColumns = getUsersTableColumns($db);
    $checkStmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $checkStmt->execute([$email]);

    if ($checkStmt->fetch()) {
        header('Location: ' . $redirectBase . '&error=email_exists');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertData = [
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
    ];

    if (isset($userColumns['phone'])) {
        $insertData['phone'] = $phone !== '' ? $phone : null;
    }

    if (isset($userColumns['role'])) {
        $insertData['role'] = 'customer';
    }

    if (isset($userColumns['status'])) {
        $insertData['status'] = 'active';
    }

    if (isset($userColumns['is_active'])) {
        $insertData['is_active'] = 1;
    }

    $columns = array_keys($insertData);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $insertStmt = $db->prepare(
        'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
    );
    $insertStmt->execute(array_values($insertData));

    $userId = (int) $db->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'customer';
    $_SESSION['logged_in'] = true;

    header('Location: pages/buyer/menu_gallery.php');
    exit;
} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    header('Location: ' . $redirectBase . '&error=failed');
    exit;
}
?>
