<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'], ['customer', 'client'], true)) {
    session_destroy();
    header('Location: ../../login.php?error=unauthorized');
    exit();
}

$pdo = Database::getInstance()->getConnection();
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['name'] ?? 'Customer';

$imagePool = [
    '../../images/achu.jpeg',
    '../../images/ndole.jpeg',
    '../../images/ekwang.jpeg',
    '../../images/garri and eru.jpeg',
    '../../images/okra fish.jpeg',
    '../../images/koki.jpeg',
];

$menuByRestaurant = [];
$error = '';
$success = '';
$latestPendingOrder = null;

function pickGalleryImage(array $pool, int $seed): string
{
    return $pool[$seed % count($pool)];
}

try {
    $stmt = $pdo->query("
        SELECT
            r.id AS restaurant_id,
            r.name AS restaurant_name,
            r.location AS restaurant_location,
            mi.id AS menu_item_id,
            mi.name AS item_name,
            mi.description AS item_description
        FROM restaurants r
        JOIN menu_items mi ON mi.restaurant_id = r.id
        WHERE r.is_active = 1
          AND mi.is_available = 1
        ORDER BY r.name ASC, mi.name ASC
        LIMIT 8
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
        $restaurantId = (int) $row['restaurant_id'];
        if (!isset($menuByRestaurant[$restaurantId])) {
            $menuByRestaurant[$restaurantId] = [
                'id' => $restaurantId,
                'name' => $row['restaurant_name'],
                'location' => $row['restaurant_location'] ?: 'Campus service point',
                'items' => [],
            ];
        }

        $image = pickGalleryImage($imagePool, (int) $row['menu_item_id']);
        $imageName = ucwords(str_replace(['_', '-', '.jpeg', '.jpg', '.png'], [' ', ' ', '', '', ''], basename($image)));

        $menuByRestaurant[$restaurantId]['items'][] = [
            'id' => (int) $row['menu_item_id'],
            'name' => $imageName,
            'description' => $row['item_description'] ?: 'Freshly prepared and ready for order.',
            'display_price' => 1000,
            'image' => $image,
        ];
    }

    $pendingStmt = $pdo->prepare("
        SELECT id, order_number, total_amount
        FROM orders
        WHERE customer_id = ?
          AND COALESCE(payment_status, 'pending') <> 'paid'
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");
    $pendingStmt->execute([$userId]);
    $latestPendingOrder = $pendingStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    error_log('Menu gallery load error: ' . $e->getMessage());
    $error = 'Unable to load the menu gallery right now.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
    $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
    $customerNotes = trim((string) ($_POST['customer_notes'] ?? ''));
    $quantities = $_POST['quantities'] ?? [];

    if ($restaurantId <= 0 || !isset($menuByRestaurant[$restaurantId])) {
        $error = 'Please choose a valid restaurant menu before placing your order.';
    } else {
        $selectedItems = [];
        foreach ($menuByRestaurant[$restaurantId]['items'] as $item) {
            $quantity = max(0, (int) ($quantities[$item['id']] ?? 0));
            if ($quantity > 0) {
                $selectedItems[] = [
                    'menu_item_id' => $item['id'],
                    'quantity' => $quantity,
                    'unit_price' => 1000,
                    'total_price' => 1000 * $quantity,
                ];
            }
        }

        if (empty($selectedItems)) {
            $error = 'Select at least one food or drink before placing your order.';
        } else {
            $totalAmount = array_sum(array_column($selectedItems, 'total_price'));
            $orderNumber = 'ORD' . date('ymdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            try {
                $pdo->beginTransaction();

                $insertOrder = $pdo->prepare("
                    INSERT INTO orders (
                        customer_id,
                        restaurant_id,
                        order_number,
                        total_amount,
                        status,
                        payment_status,
                        payment_method,
                        delivery_address,
                        customer_notes,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, NOW(), NOW())
                ");
                $insertOrder->execute([
                    $userId,
                    $restaurantId,
                    $orderNumber,
                    $totalAmount,
                    'mobile_money',
                    $deliveryAddress !== '' ? $deliveryAddress : 'Pickup at counter',
                    $customerNotes !== '' ? $customerNotes : null,
                ]);

                $orderId = (int) $pdo->lastInsertId();

                $insertItem = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id,
                        menu_item_id,
                        quantity,
                        unit_price,
                        total_price,
                        special_instructions,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");

                foreach ($selectedItems as $item) {
                    $insertItem->execute([
                        $orderId,
                        $item['menu_item_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['total_price'],
                        null,
                    ]);
                }

                $pdo->commit();
                header('Location: menu_gallery.php?success=order_created&order_id=' . urlencode((string) $orderId));
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Menu gallery order error: ' . $e->getMessage());
                $error = 'Unable to place your order right now. Please try again.';
            }
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] === 'order_created' && isset($_GET['order_id'])) {
    $orderId = (int) $_GET['order_id'];
    if ($orderId > 0) {
        $latestPendingOrder = [
            'id' => $orderId,
            'order_number' => 'Recent order',
            'total_amount' => $latestPendingOrder['total_amount'] ?? 0,
        ];
        $success = 'Your order has been placed. You can now continue to payment.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Gallery</title>
    <link rel="icon" href="../../favicon.svg">
    <style>
        :root { --bg:#f6efe2; --surface:#fff8eb; --surface-strong:#fffdf7; --primary:#355c3d; --primary-dark:#27452d; --accent:#c97d2f; --text:#2f3a2f; --muted:#6c705f; --border:#dccfb8; --success:#e0efd8; --success-text:#2d5a2d; --error:#f4ddd5; --error-text:#8d3c2b; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:'Segoe UI', Arial, sans-serif; color:var(--text); background:linear-gradient(rgba(246,239,226,.92), rgba(246,239,226,.96)), url('../../images/cameroon dishes.jpeg') center/cover fixed no-repeat; }
        .topbar { background:rgba(53,92,61,.96); color:#fff7ec; padding:1rem 0; position:sticky; top:0; z-index:10; box-shadow:0 8px 20px rgba(27,33,24,.15); }
        .container { width:min(1180px, calc(100% - 2rem)); margin:0 auto; }
        .topbar-flex { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
        .brand h1 { margin:0; font-size:1.45rem; }
        .brand p { margin:.2rem 0 0; color:#e7d9bd; font-size:.92rem; }
        .top-actions { display:flex; gap:.7rem; flex-wrap:wrap; }
        .top-actions a { text-decoration:none; padding:.7rem 1rem; border-radius:.7rem; font-weight:700; }
        .pay-btn { background:var(--accent); color:#fff; }
        .dash-btn { background:#fff0d8; color:var(--primary); }
        .logout-btn { background:#b5513a; color:#fff; }
        .pay-btn.disabled { pointer-events:none; opacity:.6; }
        .hero { padding:2rem 0 1rem; }
        .hero-card { background:rgba(255,248,235,.92); border:1px solid rgba(220,207,184,.95); border-radius:1.1rem; padding:1.2rem; box-shadow:0 18px 35px rgba(55,47,39,.12); }
        .hero-card h2 { margin:0; color:var(--primary); }
        .hero-card p { margin:.45rem 0 0; color:var(--muted); line-height:1.6; }
        .banner { margin-top:1rem; padding:.9rem 1rem; border-radius:.9rem; font-weight:600; }
        .banner.success { background:var(--success); color:var(--success-text); }
        .banner.error { background:var(--error); color:var(--error-text); }
        .pending-box { margin-top:1rem; background:var(--surface); border:1px solid var(--border); border-radius:1rem; padding:1rem; display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap; }
        .pending-box strong { color:var(--primary); display:block; margin-bottom:.2rem; }
        .pending-box a { background:var(--accent); color:#fff; text-decoration:none; padding:.75rem 1rem; border-radius:.75rem; font-weight:700; }
        .restaurant-section { margin:1.4rem 0 2rem; background:rgba(255,248,235,.93); border:1px solid rgba(220,207,184,.95); border-radius:1.2rem; padding:1.1rem; box-shadow:0 14px 28px rgba(55,47,39,.10); }
        .restaurant-head { display:flex; justify-content:space-between; gap:1rem; align-items:end; flex-wrap:wrap; margin-bottom:1rem; }
        .restaurant-head h3 { margin:0; color:var(--primary); font-size:1.35rem; }
        .restaurant-head p { margin:.25rem 0 0; color:var(--muted); }
        .menu-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1rem; }
        .dish-card { background:var(--surface-strong); border:1px solid var(--border); border-radius:1rem; overflow:hidden; box-shadow:0 10px 20px rgba(55,47,39,.08); }
        .dish-card img { width:100%; height:165px; object-fit:cover; display:block; }
        .dish-info { padding:.9rem; }
        .dish-info h4 { margin:0 0 .35rem; color:var(--primary); font-size:1.05rem; }
        .dish-info p { margin:0 0 .75rem; color:var(--muted); font-size:.92rem; min-height:3.1rem; }
        .dish-meta { display:flex; justify-content:space-between; align-items:center; gap:.8rem; }
        .price { color:var(--accent); font-weight:800; font-size:1.08rem; }
        .qty-wrap label { display:block; margin-bottom:.25rem; color:var(--muted); font-size:.83rem; }
        .qty-wrap input { width:86px; padding:.55rem .65rem; border:1px solid var(--border); border-radius:.65rem; }
        .order-form-footer { display:grid; grid-template-columns:2fr 2fr auto; gap:.8rem; align-items:end; }
        .order-form-footer label { display:block; margin-bottom:.35rem; color:var(--muted); font-weight:600; }
        .order-form-footer input, .order-form-footer textarea { width:100%; padding:.8rem .9rem; border:1px solid var(--border); border-radius:.75rem; font-family:inherit; }
        .order-form-footer textarea { resize:vertical; min-height:50px; }
        .submit-btn { border:none; background:var(--primary); color:#fff; padding:.95rem 1.2rem; border-radius:.8rem; font-weight:800; cursor:pointer; }
        .submit-btn:hover { background:var(--primary-dark); }
        .empty-note { background:rgba(255,248,235,.92); border:1px dashed var(--border); color:var(--muted); padding:2rem 1rem; border-radius:1rem; text-align:center; }
        @media (max-width: 860px) {
            .order-form-footer { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container topbar-flex">
            <div class="brand">
                <h1>Menu Gallery</h1>
                <p>Welcome, <?php echo htmlspecialchars($userName); ?>. Pick your dishes, place an order, then continue to payment.</p>
            </div>
            <div class="top-actions">
                <a class="pay-btn <?php echo $latestPendingOrder ? '' : 'disabled'; ?>" href="<?php echo $latestPendingOrder ? '../payment/index.php?order_id=' . urlencode((string) $latestPendingOrder['id']) : '#'; ?>">Payment Form</a>
                <a class="dash-btn" href="dashboard.php">My Dashboard</a>
                <a class="logout-btn" href="../../logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <div class="hero-card">
                <h2>Customer Ordering Gallery</h2>
                <p>Browse meals and drinks by restaurant, choose the quantities you want, place your order, and use the payment form link above to complete checkout.</p>
                <?php if ($success !== ''): ?>
                    <div class="banner success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="banner error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($latestPendingOrder): ?>
                    <div class="pending-box">
                        <div>
                            <strong>Current unpaid order</strong>
                            <span>Order #<?php echo htmlspecialchars((string) ($latestPendingOrder['order_number'] ?? $latestPendingOrder['id'])); ?> is ready for checkout.</span>
                        </div>
                        <a href="../payment/index.php?order_id=<?php echo urlencode((string) $latestPendingOrder['id']); ?>">Pay for This Order</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (empty($menuByRestaurant)): ?>
            <div class="empty-note">No menu items are available right now. Please check back later.</div>
        <?php else: ?>
            <?php foreach ($menuByRestaurant as $restaurant): ?>
                <section class="restaurant-section">
                    <div class="restaurant-head">
                        <div>
                            <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                            <p><?php echo htmlspecialchars($restaurant['location']); ?></p>
                        </div>
                        <div style="color:var(--accent); font-weight:800;">All items: 1,000 FCFA</div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="restaurant_id" value="<?php echo (int) $restaurant['id']; ?>">
                        <div class="menu-grid">
                            <?php foreach ($restaurant['items'] as $item): ?>
                                <article class="dish-card">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <div class="dish-info">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                                        <div class="dish-meta">
                                            <div class="price"><?php echo number_format((float) $item['display_price'], 0, ',', ' '); ?> FCFA</div>
                                            <div class="qty-wrap">
                                                <label for="qty_<?php echo (int) $item['id']; ?>">Quantity</label>
                                                <input type="number" min="0" max="20" id="qty_<?php echo (int) $item['id']; ?>" name="quantities[<?php echo (int) $item['id']; ?>]" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-form-footer">
                            <div>
                                <label for="delivery_address_<?php echo (int) $restaurant['id']; ?>">Pickup or delivery note</label>
                                <input type="text" id="delivery_address_<?php echo (int) $restaurant['id']; ?>" name="delivery_address" placeholder="Pickup at counter or your delivery point">
                            </div>
                            <div>
                                <label for="customer_notes_<?php echo (int) $restaurant['id']; ?>">Order note</label>
                                <textarea id="customer_notes_<?php echo (int) $restaurant['id']; ?>" name="customer_notes" placeholder="Optional note for this restaurant"></textarea>
                            </div>
                            <div>
                                <button class="submit-btn" type="submit" name="place_order">Place Order</button>
                            </div>
                        </div>
                    </form>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
