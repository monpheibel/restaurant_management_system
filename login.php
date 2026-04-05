<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    switch ($_SESSION['role'] ?? '') {
        case 'customer':
        case 'client':
            header('Location: pages/buyer/menu_gallery.php');
            break;
        case 'vendor':
        case 'cook':
            header('Location: pages/restaurant/dashboard.php');
            break;
        case 'admin':
        case 'courier':
            header('Location: pages/admin/dashboard.php');
            break;
        default:
            header('Location: logout.php');
            break;
    }
    exit;
}

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':
            $error = 'Invalid email or password.';
            break;
        case 'inactive':
            $error = 'Account inactive. Contact administration.';
            break;
        case 'unauthorized':
            $error = 'Please login to access that page.';
            break;
        case 'empty':
            $error = 'Please fill in all fields.';
            break;
        case 'unknown_role':
            $error = 'Unknown user role. Contact administration.';
            break;
        default:
            $error = 'Unable to sign in. Please try again.';
            break;
    }
}

$success = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':
            $success = 'Account created successfully. You can now sign in.';
            break;
        default:
            $success = '';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management System Login</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root { --bg:#f6efe2; --surface:#fff8eb; --primary:#3f6a3f; --primary-dark:#2e4f2e; --text:#2f3a2f; --muted:#6c705f; --border:#dccfb8; --error-bg:#f4ddd5; --error-text:#8d3c2b; --error-border:#e5b9aa; --success-bg:#e3f1de; --success-text:#2f5e2f; --success-border:#b9d7ad; }
        body { margin:0; background:linear-gradient(rgba(32, 39, 28, 0.55), rgba(32, 39, 28, 0.55)), url('assets/images/cameroon dishes.jpeg') center/cover fixed no-repeat; font-family:'Segoe UI', Arial, sans-serif; }
        .login-wrapper { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
        .login-box { width:100%; max-width:420px; background:var(--surface); border-radius:12px; box-shadow:0 12px 30px rgba(69,61,52,.18); padding:2rem; }
        .login-box h1 { margin:0 0 .75rem; font-size:1.8rem; color:var(--primary); }
        .login-box .form-group { margin-bottom:1rem; }
        .login-box label { display:block; margin-bottom:.35rem; color:var(--muted); font-weight:600; }
        .login-box input { width:100%; padding:.75rem 10px; border:1px solid var(--border); border-radius:8px; }
        .login-box button { width:100%; padding:.8rem; font-size:1rem; border:none; border-radius:8px; background:var(--primary); color:#fff; cursor:pointer; font-weight:700; }
        .login-box button:hover { background:var(--primary-dark); }
        .login-box .link-group { margin-top:.85rem; text-align:center; }
        .login-box a { color:var(--primary); text-decoration:none; font-weight:600; }
        .error-box { background:var(--error-bg); color:var(--error-text); border:1px solid var(--error-border); border-left:4px solid #b5513a; border-radius:8px; padding:10px 12px; margin:0 0 1rem; font-size:.92rem; }
        .success-box { background:var(--success-bg); color:var(--success-text); border:1px solid var(--success-border); border-left:4px solid #4d8d43; border-radius:8px; padding:10px 12px; margin:0 0 1rem; font-size:.92rem; }
        .register-note { margin:1rem 0 0; color:var(--muted); font-size:.95rem; text-align:center; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h1>Login</h1>

            <?php if ($error !== ''): ?>
                <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="success-box"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form action="login_process.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="you@pheibel.com" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="******" required>
                </div>
                <button type="submit" name="submit_login">Sign In</button>
            </form>
            <p class="register-note">New customer? <a href="register.php">Create an account</a></p>
            <div class="link-group"><a href="index.php">Back to Home</a></div>
        </div>
    </div>
</body>
</html>
