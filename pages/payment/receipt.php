<?php
session_start();
require_once '../../includes/functions.php';
require_once '../../config/database.php';

if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['customer', 'client'], true)) {
    header('Location: ../../login.php');
    exit;
}

function normalizeValue(?string $value, string $fallback = 'N/A'): string
{
    $trimmed = trim((string) $value);
    return $trimmed !== '' ? $trimmed : $fallback;
}

$receiptId = $_GET['receipt_id'] ?? null;
$paymentId = $_GET['payment_id'] ?? null;
$orderId = (string) ($_GET['order_id'] ?? 'N/A');
$status = strtolower((string) ($_GET['status'] ?? 'failed'));
$amount = (string) ($_GET['amount'] ?? '0');
$restaurant = (string) ($_GET['restaurant'] ?? 'Unknown');
$momoNumber = (string) ($_GET['momo_number'] ?? ($_GET['momo'] ?? ''));
$provider = (string) ($_GET['provider'] ?? '');
$checkoutMode = (string) ($_GET['checkout_mode'] ?? '');
$integrationMode = (string) ($_GET['integration_mode'] ?? 'aggregator');
$settlementProfile = (string) ($_GET['settlement_profile'] ?? 'local_sme');
$reference = (string) ($_GET['reference'] ?? '');
$ipnStatus = (string) ($_GET['ipn_status'] ?? '');
$deadlineAt = (string) ($_GET['deadline_at'] ?? '');

try {
    $db = Database::getInstance();

    if ($receiptId || $paymentId) {
        if ($receiptId) {
            $paymentRow = $db->fetch('SELECT * FROM payments WHERE id = ? LIMIT 1', [$receiptId]);
        } else {
            $paymentRow = $db->fetch('SELECT * FROM payments WHERE transaction_id = ? LIMIT 1', [$paymentId]);
        }

        if ($paymentRow) {
            $receiptId = (string) ($paymentRow['id'] ?? $receiptId);
            $paymentId = (string) ($paymentRow['transaction_id'] ?? $paymentId);
            $orderId = (string) ($paymentRow['order_id'] ?? $orderId);
            $status = strtolower((string) ($paymentRow['status'] ?? $status));
            $amount = (string) ($paymentRow['amount'] ?? $amount);

            if ($reference === '' && isset($paymentRow['transaction_id'])) {
                $reference = (string) $paymentRow['transaction_id'];
            }

            if ($orderId !== 'N/A') {
                $orderData = $db->fetch('SELECT restaurant_id FROM orders WHERE id = ? LIMIT 1', [$orderId]);
                if ($orderData && !empty($orderData['restaurant_id'])) {
                    $restInfo = $db->fetch('SELECT name FROM restaurants WHERE id = ? LIMIT 1', [$orderData['restaurant_id']]);
                    if ($restInfo && !empty($restInfo['name'])) {
                        $restaurant = (string) $restInfo['name'];
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log('Receipt fetch error: ' . $e->getMessage());
}

$isSuccess = in_array($status, ['success', 'completed', 'paid'], true);
if ($ipnStatus === '') {
    $ipnStatus = $isSuccess ? 'verified' : 'pending';
}

$metaBlocks = [
    'Provider' => strtoupper(str_replace('_', ' ', normalizeValue($provider))),
    'Checkout Mode' => strtoupper(str_replace('_', ' ', normalizeValue($checkoutMode))),
    'Integration' => strtoupper(str_replace('_', ' ', normalizeValue($integrationMode, 'aggregator'))),
    'Settlement' => strtoupper(str_replace('_', ' ', normalizeValue($settlementProfile, 'local_sme'))),
    'Reference' => normalizeValue($reference),
    'IPN Status' => strtoupper(normalizeValue($ipnStatus, 'pending')),
];

$detailBlocks = [
    'Receipt ID' => normalizeValue((string) $receiptId),
    'Order ID' => normalizeValue($orderId),
    'Transaction ID' => normalizeValue((string) $paymentId),
    'Restaurant' => normalizeValue($restaurant),
    'Amount (XAF)' => number_format((float) $amount, 2, '.', ','),
    'MoMo Number' => normalizeValue($momoNumber),
    'Reference Deadline' => normalizeValue($deadlineAt),
    'Status' => strtoupper(normalizeValue($status)),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Management System Receipt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --bg:#f6efe2; --surface:#fff8eb; --primary:#3f6a3f; --secondary:#d98b3a; --text:#2f3a2f; --border:#dccfb8; --muted:#6c705f; }
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .receipt-container { max-width: 860px; margin: 34px auto; background: var(--surface); border-radius: 14px; box-shadow: 0 10px 25px rgba(69,61,52,.16); padding: 24px; }
        .receipt-title { margin-bottom: 8px; color: var(--primary); }
        .receipt-subtitle { margin: 0 0 14px; color: var(--muted); }
        .receipt-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:10px; margin: 0 0 14px; }
        .receipt-grid-item { background:#f3e7d2; border:1px solid var(--border); border-radius:10px; padding:10px; }
        .receipt-grid-item strong { display:block; color: var(--primary); margin-bottom:3px; }
        .receipt-list { list-style:none; padding:0; margin:0 0 20px; }
        .receipt-list li { padding:10px 0; border-bottom:1px solid var(--border); }
        .receipt-list li:last-child { border-bottom:none; }
        .label { font-weight:700; color:var(--primary); }
        .value { margin-left:8px; }
        .receipt-actions { display:flex; gap:12px; margin-top:18px; flex-wrap:wrap; }
        .receipt-btn { text-decoration:none; padding:10px 16px; border-radius:8px; background:var(--primary); color:#fff; font-weight:600; border:none; cursor:pointer; }
        .receipt-btn.secondary { background:var(--secondary); color:#fff; }
        .status-success { color:#5c8d3c; font-weight:700; }
        .status-failed { color:#b5513a; font-weight:700; }
        .verify-box { margin-top:8px; background:#f3e7d2; border-left:4px solid var(--secondary); border-radius:8px; padding:10px; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <h1 class="receipt-title">Payment Receipt</h1>
        <p class="receipt-subtitle">Cameroon mobile payment confirmation with reference validation details.</p>

        <div class="receipt-grid">
            <?php foreach ($metaBlocks as $label => $value): ?>
                <div class="receipt-grid-item">
                    <strong><?php echo htmlspecialchars($label); ?></strong>
                    <?php echo htmlspecialchars($value); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p>
            <?php if ($isSuccess): ?>
                Payment confirmed. Fulfilment can continue after IPN and reference checks.
            <?php else: ?>
                Payment pending or failed. Do not fulfil until verification succeeds.
            <?php endif; ?>
        </p>

        <ul class="receipt-list">
            <?php foreach ($detailBlocks as $label => $value): ?>
                <?php if ($value === 'N/A' || $value === '') continue; ?>
                <li>
                    <span class="label"><?php echo htmlspecialchars($label); ?>:</span>
                    <?php if ($label === 'Status'): ?>
                        <span class="value <?php echo $isSuccess ? 'status-success' : 'status-failed'; ?>"><?php echo htmlspecialchars($value); ?></span>
                    <?php else: ?>
                        <span class="value"><?php echo htmlspecialchars($value); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="verify-box">
            <strong>Verification Checklist:</strong>
            IPN=<?php echo htmlspecialchars(strtoupper($ipnStatus)); ?> |
            Reference=<?php echo htmlspecialchars(normalizeValue($reference)); ?> |
            Deadline=<?php echo htmlspecialchars(normalizeValue($deadlineAt)); ?>
        </div>

        <div class="receipt-actions">
            <button class="receipt-btn" onclick="window.print()">Print Receipt</button>
            <a href="../../pages/buyer/dashboard.php" class="receipt-btn secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
