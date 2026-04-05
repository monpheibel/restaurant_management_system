<?php
session_start();
require_once '../../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    header('Location: ../../login.php');
    exit;
}

// Keep existing order flow: to process new payment, user must have order_id passed.
$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header('Location: ../../pages/buyer/menu_gallery.php');
    exit;
}

$paymentUrl = "process.php?order_id={$orderId}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management System - Payment Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/payment.css">
</head>
<body class="payment-page">
    <div class="payment-wrap">
        <div class="payment-card">
            <div class="payment-title">BELPAY Quick Payment</div>
            <div class="payment-subtitle">Seamless wallet-style payment flow for food orders.</div>

            <div class="payment-grid">
                <div class="payment-panel">
                    <h3>Step 1: Review Order</h3>
                    <ul class="payment-list">
                        <li>Order ID: <?php echo htmlspecialchars($orderId); ?></li>
                        <li>Payment gateway: BELPAY</li>
                        <li>Currency: XAF</li>
                    </ul>
                </div>
                <div class="payment-panel">
                    <h3>Step 2: Pay</h3>
                    <p>Click the button to start secure payment processing.</p>
                    <a class="pay-btn" href="<?php echo $paymentUrl; ?>">Pay with BELPAY</a>
                </div>
            </div>

            <div class="payment-panel">
                <h3>Need Help?</h3>
                <p>If your payment is incomplete, you can retry or contact support.</p>
                <a href="../../pages/buyer/menu_gallery.php" class="receipt-btn">Back to Menu Gallery</a>
            </div>
        </div>
    </div>
</body>
</html>
