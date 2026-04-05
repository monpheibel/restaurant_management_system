<?php
// login_process.php
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

// Strict check: Only process if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_login'])) {

    // 1. Input Sanitization
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic Validation
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    try {
        $userColumns = getUsersTableColumns($db);
        $selectFields = ['id', 'name', 'email', 'password', 'role'];
        if (isset($userColumns['status'])) {
            $selectFields[] = 'status';
        }
        if (isset($userColumns['is_active'])) {
            $selectFields[] = 'is_active';
        }

        // 2. Database Verification using PDO Prepared Statements
        // We select the role and status along with credentials
        $stmt = $db->prepare("SELECT " . implode(', ', $selectFields) . " FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 3. Credential and Security Verification
        // Use password_verify() against the hashed password in DB
        if ($user && password_verify($password, $user['password'])) {

            // Authorization Check: Is the account active?
            $isInactiveStatus = isset($userColumns['status']) && (($user['status'] ?? '') !== 'active');
            $isInactiveFlag = isset($userColumns['is_active']) && !(bool) ($user['is_active'] ?? true);
            if ($isInactiveStatus || $isInactiveFlag) {
                header("Location: login.php?error=inactive");
                exit();
            }

            // 4. Success: Establish authenticated session
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['name']      = $user['name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role']; // Store the defining role
            $_SESSION['logged_in'] = true;

            // 5. DYNAMIC ROLE REDIRECTION
            // The redirection logic flows directly from the verified database data.

            switch ($user['role']) {
                case 'customer':
                case 'client':
                    header("Location: pages/buyer/menu_gallery.php");
                    break;
                case 'vendor':
                case 'cook':
                    header("Location: pages/restaurant/dashboard.php");
                    break;
                case 'admin':
                case 'courier':
                    header("Location: pages/admin/dashboard.php");
                    break;
                default:
                    // ENUM fallback (critical failure safety)
                    session_destroy();
                    header("Location: login.php?error=unknown_role");
                    break;
            }
            exit(); // Ensure execution stops immediately after redirect

        } else {
            // Generic error message for security (don't reveal if email or password was wrong)
            header("Location: login.php?error=invalid");
            exit();
        }

    } catch (PDOException $e) {
        // Log error properly in production environment
        error_log("Login error: " . $e->getMessage());
        header("Location: login.php?error=invalid");
        exit();
    }

} else {
    // If accessed directly without a POST request
    header("Location: login.php");
    exit();
}
?>
