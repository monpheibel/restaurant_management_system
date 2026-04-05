<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'], ['vendor', 'cook'], true)) {
    session_destroy();
    header('Location: ../../login.php?error=unauthorized');
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['name'] ?? 'Seller';
$userEmail = $_SESSION['email'] ?? '';

$restaurant = null;
$restaurantId = 0;
$menuCount = 0;
$rating = 0.0;
$totalRevenue = 0.0;
$weeklySales = 0;
$weeklyRevenue = 0.0;
$products = [];
$weekSeries = [];

try {
    $restaurantStmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ? ORDER BY id ASC LIMIT 1');
    $restaurantStmt->execute([$userId]);
    $restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($restaurant) {
        $restaurantId = (int) $restaurant['id'];

        $menuCountStmt = $pdo->prepare('SELECT COUNT(*) AS count FROM menu_items WHERE restaurant_id = ? AND is_available = 1');
        $menuCountStmt->execute([$restaurantId]);
        $menuCount = (int) (($menuCountStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0));

        $ratingStmt = $pdo->prepare('SELECT COALESCE(AVG(rating), 0) AS rating FROM reviews WHERE restaurant_id = ?');
        $ratingStmt->execute([$restaurantId]);
        $rating = (float) (($ratingStmt->fetch(PDO::FETCH_ASSOC)['rating'] ?? 0));

        $revenueStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM orders WHERE restaurant_id = ? AND status <> 'cancelled'");
        $revenueStmt->execute([$restaurantId]);
        $totalRevenue = (float) (($revenueStmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0));

        $weekStmt = $pdo->prepare("\n            SELECT\n                COUNT(*) AS sales_count,\n                COALESCE(SUM(total_amount), 0) AS weekly_revenue\n            FROM orders\n            WHERE restaurant_id = ?\n              AND status <> 'cancelled'\n              AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)\n        ");
        $weekStmt->execute([$restaurantId]);
        $weekAgg = $weekStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $weeklySales = (int) ($weekAgg['sales_count'] ?? 0);
        $weeklyRevenue = (float) ($weekAgg['weekly_revenue'] ?? 0);

        $dailyStmt = $pdo->prepare("\n            SELECT DATE(created_at) AS day_key, COUNT(*) AS orders_count, COALESCE(SUM(total_amount), 0) AS day_revenue\n            FROM orders\n            WHERE restaurant_id = ?\n              AND status <> 'cancelled'\n              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)\n            GROUP BY DATE(created_at)\n            ORDER BY day_key ASC\n        ");
        $dailyStmt->execute([$restaurantId]);
        $dailyRows = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

        $dailyMap = [];
        foreach ($dailyRows as $row) {
            $dailyMap[$row['day_key']] = [
                'orders' => (int) $row['orders_count'],
                'revenue' => (float) $row['day_revenue'],
            ];
        }

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} day"));
            $short = date('D', strtotime($date));
            $weekSeries[] = [
                'date' => $date,
                'day' => $short,
                'orders' => $dailyMap[$date]['orders'] ?? 0,
                'revenue' => $dailyMap[$date]['revenue'] ?? 0,
            ];
        }

        $productStmt = $pdo->prepare("\n            SELECT id, name, description, price, image, is_available, created_at\n            FROM menu_items\n            WHERE restaurant_id = ?\n            ORDER BY created_at DESC\n            LIMIT 100\n        ");
        $productStmt->execute([$restaurantId]);
        $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Vendor dashboard load error: ' . $e->getMessage());
}

if (empty($weekSeries)) {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} day"));
        $weekSeries[] = [
            'date' => $date,
            'day' => date('D', strtotime($date)),
            'orders' => 0,
            'revenue' => 0,
        ];
    }
}

$restaurantName = $restaurant['name'] ?? ($userName . "'s Kitchen");
$restaurantLocation = $restaurant['location'] ?? 'N/A';
$restaurantPhone = $restaurant['phone'] ?? 'N/A';
$restaurantStatus = isset($restaurant['is_active']) ? ((int) $restaurant['is_active'] === 1 ? 'Active' : 'Inactive') : 'Pending Setup';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management System | Seller Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .dashboard-container {
            display: flex;
            gap: 1.5rem;
            margin: 1.8rem 0;
            align-items: flex-start;
        }
        .sidebar {
            flex: 0 0 255px;
            background: var(--card-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.1rem;
            padding: 1.2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 95px;
        }
        .sidebar h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.8rem;
            font-size: 1.12rem;
        }
        .sidebar-item {
            padding: 0.7rem 0.8rem;
            margin-bottom: 0.4rem;
            border-radius: 0.55rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-color);
            border: 1px solid transparent;
            font-weight: 600;
        }
        .sidebar-item:hover,
        .sidebar-item.active {
            background: rgba(217, 139, 58, 0.12);
            color: var(--primary-color);
            border-color: var(--border-color);
        }
        .sidebar-item i {
            width: 20px;
            margin-right: 0.35rem;
        }
        .main-content {
            flex: 1;
            min-width: 0;
        }
        .tab-content {
            background: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            padding: 1.4rem;
        }
        .tab-content + .tab-content {
            margin-top: 1rem;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--card-glass);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
        }
        .stat-icon {
            font-size: 1.45rem;
            color: var(--primary-color);
        }
        .stat-value {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0.25rem 0;
        }
        .stat-label {
            color: var(--text-light);
            font-size: 0.92rem;
        }
        .chart-container {
            background: var(--card-glass);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .weekly-sales {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.75rem;
            margin-top: 0.8rem;
        }
        .day-bar {
            text-align: center;
        }
        .bar {
            width: 100%;
            height: 130px;
            border-radius: 0.6rem 0.6rem 0.2rem 0.2rem;
            background: rgba(217, 139, 58, 0.14);
            overflow: hidden;
            display: flex;
            align-items: flex-end;
        }
        .bar-fill {
            width: 100%;
            background: linear-gradient(180deg, var(--secondary-color), var(--primary-color));
            transition: height 0.3s ease;
        }
        .day-label {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 0.45rem;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .product-card {
            background: var(--card-glass);
            border: 1px solid var(--border-color);
            border-radius: 0.9rem;
            overflow: hidden;
        }
        .product-image {
            height: 170px;
            background: rgba(63, 106, 63, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 0.9rem;
        }
        .product-info h4 {
            color: var(--primary-color);
            margin: 0 0 0.35rem;
            font-size: 1rem;
        }
        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--secondary-color);
        }
        .product-meta {
            color: var(--text-light);
            font-size: 0.82rem;
            margin-top: 0.25rem;
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.7rem;
        }
        .action-btn {
            flex: 1;
            border: none;
            border-radius: 0.4rem;
            padding: 0.5rem;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .edit-btn {
            background: var(--primary-color);
            color: #fff;
        }
        .delete-btn {
            background: var(--error-color);
            color: #fff;
        }
        .add-product-btn {
            display: inline-block;
            text-decoration: none;
            background: var(--primary-color);
            color: #fff;
            padding: 0.75rem 1rem;
            border-radius: 0.6rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.9rem;
            margin-top: 0.8rem;
        }
        .order-card {
            background: var(--card-glass);
            border: 1px solid var(--border-color);
            border-radius: 0.8rem;
            padding: 0.9rem;
        }
        .order-head {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .order-head strong {
            color: var(--primary-color);
        }
        .order-meta {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        .order-actions button {
            flex: 1;
            border: none;
            border-radius: 0.45rem;
            padding: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            color: #fff;
        }
        .ok-btn { background: var(--success-color); }
        .no-btn { background: var(--error-color); }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.9rem;
        }
        .settings-box {
            background: var(--card-glass);
            border: 1px solid var(--border-color);
            border-radius: 0.7rem;
            padding: 0.8rem;
        }
        .settings-box strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.3rem;
        }
        .empty-note {
            color: var(--text-light);
            padding: 1rem;
            text-align: center;
            border: 1px dashed var(--border-color);
            border-radius: 0.7rem;
            background: var(--card-glass);
        }
        @media (max-width: 980px) {
            .dashboard-container {
                flex-direction: column;
            }
            .sidebar {
                position: static;
                width: 100%;
            }
            .weekly-sales {
                gap: 0.45rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-flex">
            <div class="logo">
                <h1>Restaurant Seller Hub</h1>
            </div>
            <div style="display:flex; align-items:center; gap:0.9rem;">
                <span id="sellerName" style="font-weight:600; color:#f8e8cb;">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="../../logout.php" class="action-btn" style="background:var(--error-color); color:#fff; text-decoration:none;">Logout</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-container">
                <aside class="sidebar">
                    <h3><i class="fas fa-seedling"></i> Seller Menu</h3>
                    <div class="sidebar-item active" data-tab="overview"><i class="fas fa-chart-pie"></i> Dashboard Overview</div>
                    <div class="sidebar-item" data-tab="orders"><i class="fas fa-receipt"></i> Pending Orders</div>
                    <div class="sidebar-item" data-tab="products"><i class="fas fa-box-open"></i> My Products</div>
                    <div class="sidebar-item" data-tab="sales"><i class="fas fa-chart-line"></i> Sales Analytics</div>
                    <div class="sidebar-item" data-tab="settings"><i class="fas fa-cog"></i> Settings</div>
                </aside>

                <section class="main-content">
                    <div id="overviewTab" class="tab-content">
                        <h2 style="color:var(--primary-color); margin-bottom:1rem;">Dashboard Overview</h2>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-box"></i></div>
                                <div class="stat-label">Total Products</div>
                                <div class="stat-value" id="totalProducts">0</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-shopping-basket"></i></div>
                                <div class="stat-label">Weekly Sales</div>
                                <div class="stat-value" id="weeklySales">0</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="stat-label">Revenue This Week</div>
                                <div class="stat-value" id="weeklyRevenue">0 FCFA</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-star"></i></div>
                                <div class="stat-label">Rating</div>
                                <div class="stat-value" id="ratingValue"><?php echo number_format($rating, 1); ?>/5</div>
                            </div>
                        </div>

                        <div class="chart-container">
                            <h3 style="color:var(--primary-color);">Weekly Sales Performance</h3>
                            <div class="weekly-sales" id="weeklySalesChart"></div>
                        </div>
                    </div>

                    <div id="ordersTab" class="tab-content" style="display:none;">
                        <h2 style="color:var(--primary-color); margin-bottom:0.8rem;">Pending Orders</h2>
                        <p style="color:var(--text-light); margin-bottom:0.6rem;">Live order feed from your restaurant queue.</p>
                        <div id="ordersContainer" class="orders-grid"></div>
                    </div>

                    <div id="productsTab" class="tab-content" style="display:none;">
                        <h2 style="color:var(--primary-color); margin-bottom:0.8rem;">My Products</h2>
                        <a class="add-product-btn" href="upload_products.php?restaurant_id=<?php echo $restaurantId; ?>">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                        <div class="products-grid" id="productsGrid"></div>
                    </div>

                    <div id="salesTab" class="tab-content" style="display:none;">
                        <h2 style="color:var(--primary-color); margin-bottom:0.8rem;">Sales Analytics</h2>
                        <div class="chart-container">
                            <h3 style="color:var(--primary-color); margin-bottom:0.8rem;">This Week's Performance</h3>
                            <table style="width:100%; border-collapse: collapse; color:var(--text-color);">
                                <thead>
                                    <tr style="border-bottom:1px solid var(--border-color);">
                                        <th style="text-align:left; padding:0.7rem;">Day</th>
                                        <th style="text-align:right; padding:0.7rem;">Orders</th>
                                        <th style="text-align:right; padding:0.7rem;">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="salesTable"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="settingsTab" class="tab-content" style="display:none;">
                        <h2 style="color:var(--primary-color); margin-bottom:0.8rem;">Account & Store Settings</h2>
                        <div class="settings-grid" id="profileInfo"></div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        body.setAttribute('data-theme', savedTheme);
        themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeToggle.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });

        const restaurantId = <?php echo json_encode($restaurantId); ?>;
        const restaurantName = <?php echo json_encode($restaurantName); ?>;
        const initialProducts = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
        const weekSeries = <?php echo json_encode($weekSeries, JSON_UNESCAPED_UNICODE); ?>;
        const sellerProfile = {
            sellerName: <?php echo json_encode($userName); ?>,
            email: <?php echo json_encode($userEmail); ?>,
            restaurantName: <?php echo json_encode($restaurantName); ?>,
            location: <?php echo json_encode($restaurantLocation); ?>,
            phone: <?php echo json_encode($restaurantPhone); ?>,
            status: <?php echo json_encode($restaurantStatus); ?>,
            menuCount: <?php echo json_encode($menuCount); ?>,
            totalRevenue: <?php echo json_encode($totalRevenue); ?>,
            rating: <?php echo json_encode(number_format($rating, 1)); ?>
        };

        const localProductsKey = `rms_vendor_products_${restaurantId || 'global'}`;
        const vendorImagePool = [
            '../../assets/images/achu.jpeg',
            '../../assets/images/ndole.jpeg',
            '../../assets/images/ekwang.jpeg',
            '../../assets/images/garri and eru.jpeg',
            '../../assets/images/okra fish.jpeg',
            '../../assets/images/koki.jpeg',
            '../../assets/images/pepper soup fish.jpeg',
            '../../assets/images/potatoes hot pot.jpeg',
            '../../assets/images/poulet deje.jpeg',
            '../../assets/images/sese plantains.jpeg',
            '../../assets/images/water fufu and eru.jpeg',
            '../../assets/images/fufu and kati kati.jpeg'
        ];

        function formatFcfa(value) {
            const amount = Number(value || 0);
            return `${amount.toLocaleString('fr-FR')} FCFA`;
        }

        function pickVendorImage(seed) {
            return vendorImagePool[Math.abs(Number(seed || 0)) % vendorImagePool.length];
        }

        function getLocalProducts() {
            try {
                const parsed = JSON.parse(localStorage.getItem(localProductsKey));
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function saveLocalProducts(items) {
            localStorage.setItem(localProductsKey, JSON.stringify(items));
        }

        function getAllProducts() {
            const dbProducts = initialProducts.map((item) => ({
                id: item.id,
                name: item.name,
                description: item.description || '',
                price: 1000,
                stock: item.is_available === 1 || item.is_available === '1' ? 'Available' : 'Unavailable',
                image: pickVendorImage(item.id),
                source: 'database'
            }));

            const localProducts = getLocalProducts().map((item) => ({
                ...item,
                price: 1000,
                image: pickVendorImage(item.local_id || item.id || item.name?.length || 0),
                source: 'local'
            }));

            return [...localProducts, ...dbProducts];
        }

        function initializeDashboard() {
            const allProducts = getAllProducts();
            const totalOrders = weekSeries.reduce((sum, row) => sum + Number(row.orders || 0), 0);
            const totalRevenue = weekSeries.reduce((sum, row) => sum + Number(row.revenue || 0), 0);

            document.getElementById('totalProducts').textContent = allProducts.length;
            document.getElementById('weeklySales').textContent = totalOrders;
            document.getElementById('weeklyRevenue').textContent = formatFcfa(totalRevenue);

            renderWeeklyChart();
            loadProducts();
            loadSalesTable();
            loadProfileInfo();
            fetchPendingOrders();

            setInterval(fetchPendingOrders, 30000);
        }

        function renderWeeklyChart() {
            const chart = document.getElementById('weeklySalesChart');
            const max = Math.max(...weekSeries.map(row => Number(row.orders || 0)), 1);
            chart.innerHTML = weekSeries.map(row => {
                const height = Math.max((Number(row.orders || 0) / max) * 100, 4);
                return `
                    <div class="day-bar">
                        <div class="bar">
                            <div class="bar-fill" style="height:${height}%"></div>
                        </div>
                        <div class="day-label">${row.day}</div>
                    </div>
                `;
            }).join('');
        }

        function loadProducts() {
            const grid = document.getElementById('productsGrid');
            const products = getAllProducts();

            if (!products.length) {
                grid.innerHTML = '<div class="empty-note" style="grid-column:1/-1;">No products yet. Use "Add New Product" to start listing items.</div>';
                return;
            }

            grid.innerHTML = products.map((product, idx) => {
                const isLocal = product.source === 'local';
                return `
                    <div class="product-card">
                        <div class="product-image">
                            ${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<i class="fas fa-image"></i>'}
                        </div>
                        <div class="product-info">
                            <h4>${product.name}</h4>
                            <div class="product-price">${formatFcfa(product.price)}</div>
                            <div class="product-meta">${product.stock ? `Stock: ${product.stock}` : 'Stock: N/A'}</div>
                            <div class="product-meta">${isLocal ? 'Source: Upload Draft' : 'Source: Database'}</div>
                            <div class="product-actions">
                                <button class="action-btn edit-btn" onclick="editProduct(${idx})"><i class="fas fa-edit"></i> Edit</button>
                                <button class="action-btn delete-btn" onclick="deleteProduct(${idx})"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function loadSalesTable() {
            const table = document.getElementById('salesTable');
            table.innerHTML = weekSeries.map((row) => `
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:0.7rem;">${row.day} (${row.date})</td>
                    <td style="padding:0.7rem; text-align:right; color:var(--primary-color); font-weight:700;">${row.orders}</td>
                    <td style="padding:0.7rem; text-align:right;">${formatFcfa(row.revenue)}</td>
                </tr>
            `).join('');
        }

        function loadProfileInfo() {
            const wrap = document.getElementById('profileInfo');
            wrap.innerHTML = `
                <div class="settings-box"><strong>Seller</strong>${sellerProfile.sellerName}</div>
                <div class="settings-box"><strong>Email</strong>${sellerProfile.email || 'N/A'}</div>
                <div class="settings-box"><strong>Restaurant</strong>${sellerProfile.restaurantName}</div>
                <div class="settings-box"><strong>Location</strong>${sellerProfile.location || 'N/A'}</div>
                <div class="settings-box"><strong>Phone</strong>${sellerProfile.phone || 'N/A'}</div>
                <div class="settings-box"><strong>Status</strong>${sellerProfile.status}</div>
                <div class="settings-box"><strong>Menu Items</strong>${sellerProfile.menuCount}</div>
                <div class="settings-box"><strong>Total Revenue</strong>${formatFcfa(sellerProfile.totalRevenue)}</div>
                <div class="settings-box"><strong>Rating</strong>${sellerProfile.rating}/5</div>
            `;
        }

        async function fetchPendingOrders() {
            const container = document.getElementById('ordersContainer');
            if (!restaurantId) {
                container.innerHTML = '<div class="empty-note" style="grid-column:1/-1;">No restaurant profile found for this seller yet.</div>';
                return;
            }

            try {
                const response = await fetch(`../../api/orders/get_pending.php?restaurant_id=${encodeURIComponent(restaurantId)}`);
                const data = await response.json();
                if (!data.success) {
                    container.innerHTML = `<div class="empty-note" style="grid-column:1/-1;">${data.message || 'Unable to load pending orders.'}</div>`;
                    return;
                }

                const orders = Array.isArray(data.orders) ? data.orders : [];
                if (!orders.length) {
                    container.innerHTML = '<div class="empty-note" style="grid-column:1/-1;">No pending orders at the moment.</div>';
                    return;
                }

                container.innerHTML = orders.map(order => `
                    <div class="order-card" data-order-id="${order.id}">
                        <div class="order-head">
                            <strong>Order #${order.id}</strong>
                            <span style="color:var(--text-light); font-size:0.85rem;">${order.created_at || ''}</span>
                        </div>
                        <div class="order-meta">Customer: ${order.customer_name || 'N/A'}</div>
                        <div class="order-meta">Items: ${order.item_count || 0}</div>
                        <div class="order-meta">Total: ${formatFcfa(order.total_amount || 0)}</div>
                        <div class="order-meta">Address: ${order.delivery_address || 'N/A'}</div>
                        <div class="order-actions">
                            <button class="ok-btn" onclick="updateOrderStatus(${order.id}, 'confirmed')">Accept</button>
                            <button class="no-btn" onclick="updateOrderStatus(${order.id}, 'cancelled')">Reject</button>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                container.innerHTML = '<div class="empty-note" style="grid-column:1/-1;">Order feed unavailable right now.</div>';
            }
        }

        async function updateOrderStatus(orderId, status) {
            try {
                const response = await fetch('../../api/orders/update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status })
                });
                const data = await response.json();
                if (data.success) {
                    fetchPendingOrders();
                } else {
                    alert(data.message || 'Failed to update order status.');
                }
            } catch (error) {
                alert('Failed to update order status.');
            }
        }

        function switchTab(tabName) {
            const tabs = ['overview', 'orders', 'products', 'sales', 'settings'];
            tabs.forEach((name) => {
                const tab = document.getElementById(`${name}Tab`);
                if (tab) {
                    tab.style.display = name === tabName ? 'block' : 'none';
                }
            });

            document.querySelectorAll('.sidebar-item').forEach((item) => {
                item.classList.toggle('active', item.dataset.tab === tabName);
            });
        }

        function editProduct(index) {
            const products = getAllProducts();
            const target = products[index];
            if (!target) return;
            if (target.source === 'database') {
                alert('Database product editing is not wired on this screen yet. Use menu management for full edits.');
                return;
            }
            alert('Local uploaded product editing is coming next.');
        }

        function deleteProduct(index) {
            const all = getAllProducts();
            const target = all[index];
            if (!target) return;
            if (target.source === 'database') {
                alert('Database products cannot be deleted from this local draft panel.');
                return;
            }

            const localOnly = getLocalProducts();
            const localIndex = localOnly.findIndex((item) => item.local_id === target.local_id);
            if (localIndex === -1) return;

            if (confirm('Delete this locally uploaded product draft?')) {
                localOnly.splice(localIndex, 1);
                saveLocalProducts(localOnly);
                loadProducts();
                document.getElementById('totalProducts').textContent = getAllProducts().length;
            }
        }

        document.querySelectorAll('.sidebar-item').forEach((item) => {
            item.addEventListener('click', () => switchTab(item.dataset.tab));
        });

        const params = new URLSearchParams(window.location.search);
        const startTab = params.get('tab');
        if (startTab && ['overview', 'orders', 'products', 'sales', 'settings'].includes(startTab)) {
            switchTab(startTab);
        }

        initializeDashboard();

        window.updateOrderStatus = updateOrderStatus;
        window.editProduct = editProduct;
        window.deleteProduct = deleteProduct;
    </script>
</body>
</html>
