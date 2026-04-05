<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'], ['customer', 'client'], true)) {
    session_destroy();
    header('Location: ../../login.php?error=unauthorized');
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['name'] ?? 'Customer';
$userEmail = $_SESSION['email'] ?? '';

$orders = [];
$totalOrders = 0;
$totalSpent = 0.0;

try {
    $orderStmt = $pdo->prepare("
        SELECT id, order_number, total_amount, status, payment_status, created_at
        FROM orders
        WHERE customer_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $orderStmt->execute([$userId]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalOrders = count($orders);
    $totalSpent = (float) array_sum(array_column($orders, 'total_amount'));
} catch (Throwable $e) {
    error_log('Customer dashboard error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Customer</title>
    <link rel="icon" href="../../favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../cameroon_theme.css">
    <style>
        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }
        .customer-header-content {
            flex: 1;
        }
        .customer-header h1 { margin:0; font-size:1.6rem; color: #ffffff; }
        .customer-header p { margin:.3rem 0 0; color:rgba(255,255,255,0.85); }
        .nav-buttons { display:flex; gap:.7rem; margin-top:1rem; flex-wrap:wrap; }
        .nav-buttons a, .nav-buttons button { text-decoration:none; padding:.7rem 1.2rem; border-radius:.7rem; font-weight:700; border:none; cursor:pointer; transition: all 0.3s ease; }
        .back-btn { background:#f0e9d8; color:var(--primary-color); }
        .back-btn:hover { background:#e8dfc8; transform: translateY(-2px); }
        .logout-btn { background:var(--accent-danger); color:#fff; }
        .logout-btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .main { padding:2rem 0; }
        .empty-message { text-align:center; color:var(--text-light); padding:2rem 1rem; }
        .empty-message a { color:var(--primary-color); font-weight:600; }
        @media (max-width: 768px) {
            .customer-header { flex-direction: column; gap:1rem; text-align:center; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="customer-header">
                <div class="customer-header-content">
                    <h1><i class="fas fa-user-circle"></i> My Dashboard</h1>
                    <p>Welcome, <?php echo htmlspecialchars($userName); ?></p>
                    <div class="nav-buttons">
                        <a href="menu_gallery.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                        <a href="../../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </header>
    <header class="header">
        <div class="container">
            <h1>My Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($userName); ?></p>
            <div class="nav-buttons">
                <a href="menu_gallery.php" class="back-btn">← Back to Menu</a>
                <a href="../../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <main class="container main">
        <section class="card">
            <h2><i class="fas fa-user-circle"></i> Account Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Name</strong>
                    <p><?php echo htmlspecialchars($userName); ?></p>
                </div>
                <div class="info-item">
                    <strong>Email</strong>
                    <p><?php echo htmlspecialchars($userEmail); ?></p>
                </div>
                <div class="info-item">
                    <strong>Total Orders</strong>
                    <p><?php echo $totalOrders; ?></p>
                </div>
                <div class="info-item">
                    <strong>Total Spent</strong>
                    <p><?php echo number_format($totalSpent, 2, '.', ','); ?> FCFA</p>
                </div>
            </div>
        </section>

        <section class="card">
            <h2><i class="fas fa-history"></i> Order History</h2>
            <?php if (!empty($orders)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Amount (FCFA)</th>
                            <th>Order Status</th>
                            <th>Payment Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo number_format((float)$order['total_amount'], 2, '.', ','); ?></td>
                                <td><span class="status-pending"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></td>
                                <td><span class="status-<?php echo strtolower($order['payment_status']); ?>"><?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-message">
                    <p><i class="fas fa-inbox"></i></p>
                    <p>You haven't placed any orders yet. <a href="menu_gallery.php">Start ordering now!</a></p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('rms_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        html.setAttribute('data-theme', savedTheme);
        themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('rms_theme', newTheme);
            themeToggle.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    </script>
</body>
</html>