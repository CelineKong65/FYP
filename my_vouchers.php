<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$custID = $_SESSION['user_id'];

// Get customer data
$stmt = $conn->prepare("SELECT * FROM customer WHERE CustID = ?");
$stmt->execute([$custID]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: login.php");
    exit();
}

// Get all vouchers claimed by the customer
$stmt = $conn->prepare("
    SELECT v.VoucherID, v.VoucherCode, v.VoucherDesc, v.DiscountValue, 
           v.MinPurchase, v.ExpireDate, vu.ClaimedAt, 
           CASE 
               WHEN v.ExpireDate IS NULL THEN 1
               WHEN v.ExpireDate >= CURDATE() THEN 1
               ELSE 0
           END AS IsValid
    FROM voucher_usage vu
    JOIN voucher v ON vu.VoucherID = v.VoucherID
    WHERE vu.CustID = ?
    ORDER BY vu.ClaimedAt DESC
");
$stmt->execute([$custID]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vouchers - Watersport Equipment Shop</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .voucher-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .valid {
            border-left: 5px solid #28a745;
        }
        .expired {
            border-left: 5px solid #dc3545;
            opacity: 0.7;
        }
        .voucher-code {
            font-weight: bold;
            font-size: 1.2em;
            color: #007bff;
        }
        .discount-value {
            color: #28a745;
            font-weight: bold;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="container">
        <h1>🎫 My Vouchers</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if (count($vouchers) > 0): ?>
            <div class="voucher-list">
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="voucher-card <?= $voucher['IsValid'] ? 'valid' : 'expired' ?>">
                        <div class="voucher-code"><?= htmlspecialchars($voucher['VoucherCode']) ?></div>
                        <div class="voucher-desc"><?= htmlspecialchars($voucher['VoucherDesc']) ?></div>
                        <div class="discount-value">RM<?= number_format($voucher['DiscountValue'], 2) ?> OFF</div>
                        
                        <?php if ($voucher['MinPurchase'] > 0): ?>
                            <div>Min. Purchase: RM<?= number_format($voucher['MinPurchase'], 2) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($voucher['ExpireDate']): ?>
                            <div>Expires: <?= date('d M Y', strtotime($voucher['ExpireDate'])) ?></div>
                        <?php endif; ?>
                        
                        <div>Claimed: <?= date('d M Y H:i', strtotime($voucher['ClaimedAt'])) ?></div>
                        
                        <?php if (!$voucher['IsValid']): ?>
                            <div class="text-danger">This voucher has expired</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't claimed any vouchers yet. <a href="voucher_list.php">Browse available vouchers</a>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>