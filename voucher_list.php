<?php
include 'config.php';
include 'header.php'; 

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Get the logged-in user's ID from the session
$custID = $_SESSION['user_id'];

// Fetch all active, non-expired vouchers that the user has NOT claimed yet
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
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC); // Get results as an associative array

// Handle session-based error messages (if any)
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['error']); // Clear error after displaying
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

        <!-- Show voucher cards if vouchers are available -->
        <?php if (count($vouchers) > 0): ?>
            <div class="voucher-list">
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="voucher-card">
                        <?php if (!empty($voucher['VoucherPicture'])): ?>
                            <img src="image/voucher/<?= htmlspecialchars($voucher['VoucherPicture']) ?>" alt="Voucher Image">
                        <?php endif; ?>
                        <!-- Voucher Information -->
                        <div class="voucher-code"><?= htmlspecialchars($voucher['VoucherCode']) ?></div>
                        <div class="voucher-desc"><?= htmlspecialchars($voucher['VoucherDesc']) ?></div>
                        <div class="discount-value">RM<?= number_format($voucher['DiscountValue'], 2) ?> OFF</div>
                        
                        <!-- Min purchase requirement (if have) -->
                        <?php if ($voucher['MinPurchase'] > 0): ?>
                            <div>Min. Purchase: RM<?= number_format($voucher['MinPurchase'], 2) ?></div>
                        <?php endif; ?>
                        <!-- Expiry date (if have) -->
                        <?php if ($voucher['ExpireDate']): ?>
                            <div>Expires: <?= date('d M Y', strtotime($voucher['ExpireDate'])) ?></div>
                        <?php endif; ?>
                        <!-- Claim button with confirmation prompt -->
                        <a href="claim_voucher.php?voucher_id=<?= $voucher['VoucherID'] ?>" 
                           class="btn" 
                           onclick="return confirm('Claim this voucher?')">
                            Claim Voucher
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- If no vouchers available -->
            <div class="alert alert-info">
                No available vouchers to claim at the moment. Check back later!
            </div>
        <?php endif; ?>
        <!-- Back link to user's claimed vouchers -->
        <div class="back-link">
            <a href="my_vouchers.php">← Back to My Vouchers</a>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>
