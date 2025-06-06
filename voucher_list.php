<?php
include 'config.php';
include 'header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$custID = $_SESSION['user_id'];

// Get available vouchers the user hasn't claimed yet
$stmt = $conn->prepare("
    SELECT v.VoucherID, v.VoucherCode, v.VoucherDesc, v.DiscountValue, 
           v.MinPurchase, v.ExpireDate, v.VoucherPicture
    FROM voucher v
    WHERE v.VoucherID NOT IN (
        SELECT VoucherID FROM voucher_usage WHERE CustID = ?
    )
    AND v.VoucherStatus = 'Active'
    AND (v.ExpireDate IS NULL OR v.ExpireDate >= CURDATE())
");
$stmt->execute([$custID]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Vouchers - Watersport Equipment Shop</title>
    <link rel="stylesheet" href="voucher_list.css">
</head>
<body>

    
    <main class="container">
        <h1>🎁 Available Vouchers</h1>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (count($vouchers) > 0): ?>
            <div class="voucher-list">
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="voucher-card">
                        <?php if (!empty($voucher['VoucherPicture'])): ?>
                            <img src="image/voucher/<?= htmlspecialchars($voucher['VoucherPicture']) ?>" alt="Voucher Image">
                        <?php endif; ?>
                        <div class="voucher-code"><?= htmlspecialchars($voucher['VoucherCode']) ?></div>
                        <div class="voucher-desc"><?= htmlspecialchars($voucher['VoucherDesc']) ?></div>
                        <div class="discount-value">RM<?= number_format($voucher['DiscountValue'], 2) ?> OFF</div>
                        
                        <?php if ($voucher['MinPurchase'] > 0): ?>
                            <div>Min. Purchase: RM<?= number_format($voucher['MinPurchase'], 2) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($voucher['ExpireDate']): ?>
                            <div>Expires: <?= date('d M Y', strtotime($voucher['ExpireDate'])) ?></div>
                        <?php endif; ?>

                        <a href="claim_voucher.php?voucher_id=<?= $voucher['VoucherID'] ?>" 
                           class="btn" 
                           onclick="return confirm('Claim this voucher?')">
                            Claim Voucher
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No available vouchers to claim at the moment. Check back later!
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="my_vouchers.php">← Back to My Vouchers</a>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>
