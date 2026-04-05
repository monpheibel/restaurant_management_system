<?php
session_start();
require_once 'includes/functions.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    redirectBasedOnRole($_SESSION['role'] ?? '');
}

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty':
            $error = 'Please fill in all required fields.';
            break;
        case 'invalid_email':
            $error = 'Please enter a valid email address.';
            break;
        case 'password_short':
            $error = 'Password must be at least 8 characters long.';
            break;
        case 'password_mismatch':
            $error = 'Passwords do not match.';
            break;
        case 'email_exists':
            $error = 'An account with that email already exists.';
            break;
        default:
            $error = 'Unable to create your account. Please try again.';
            break;
    }
}

$name = trim($_GET['name'] ?? '');
$email = trim($_GET['email'] ?? '');
$phone = trim($_GET['phone'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Customer Account</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root { --bg:#f6efe2; --surface:#fff8eb; --primary:#3f6a3f; --primary-dark:#2e4f2e; --text:#2f3a2f; --muted:#6c705f; --border:#dccfb8; --error-bg:#f4ddd5; --error-text:#8d3c2b; --error-border:#e5b9aa; }
        body { margin:0; background:linear-gradient(rgba(32, 39, 28, 0.55), rgba(32, 39, 28, 0.55)), url('assets/images/achu.jpeg') center/cover fixed no-repeat; font-family:'Segoe UI', Arial, sans-serif; }
        .register-wrapper { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
        .register-box { width:100%; max-width:470px; background:var(--surface); border-radius:12px; box-shadow:0 12px 30px rgba(69,61,52,.18); padding:2rem; }
        .register-box h1 { margin:0 0 .55rem; font-size:1.8rem; color:var(--primary); }
        .register-box p { margin:0 0 1rem; color:var(--muted); line-height:1.5; }
        .form-group { margin-bottom:1rem; }
        label { display:block; margin-bottom:.35rem; color:var(--muted); font-weight:600; }
        input { width:100%; padding:.75rem 10px; border:1px solid var(--border); border-radius:8px; }
        button { width:100%; padding:.85rem; font-size:1rem; border:none; border-radius:8px; background:var(--primary); color:#fff; cursor:pointer; font-weight:700; }
        button:hover { background:var(--primary-dark); }
        .link-group { margin-top:.9rem; text-align:center; }
        a { color:var(--primary); text-decoration:none; font-weight:600; }
        .error-box { background:var(--error-bg); color:var(--error-text); border:1px solid var(--error-border); border-left:4px solid #b5513a; border-radius:8px; padding:10px 12px; margin:0 0 1rem; font-size:.92rem; }
        .help-text { margin-top:-.35rem; margin-bottom:1rem; font-size:.9rem; color:var(--muted); }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-box">
            <h1>Create Customer Account</h1>
            <p>Register as a customer to browse restaurants, place orders, and track your receipts.</p>

            <?php if ($error !== ''): ?>
                <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="register_process.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Your full name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" placeholder="+2376..." value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="At least 8 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat your password" required>
                </div>
                <div class="help-text">Customer registration only. Vendor and admin accounts are created by the platform team.</div>
                <button type="submit" name="submit_register">Create Account</button>
            </form>
            <div class="link-group"><a href="login.php">Already have an account? Sign in</a></div>
        </div>
    </div>
</body>
</html>
