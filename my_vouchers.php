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

// Get customer data from `customer` table
$stmt = $conn->prepare("SELECT * FROM customer WHERE CustID = ?");
$stmt->execute([$custID]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// If customer not found, redirect to login
if (!$customer) {
    header("Location: login.php");
    exit();
}

// Get all vouchers claimed by the customer (not yet used)
$stmt = $conn->prepare("
    SELECT vu.UsageID, v.VoucherID, v.VoucherCode, v.VoucherDesc, v.DiscountValue, 
           v.MinPurchase, v.ExpireDate, v.VoucherPicture, vu.ClaimedAt, 
           CASE 
               WHEN v.ExpireDate IS NULL THEN 1
               WHEN v.ExpireDate >= CURDATE() THEN 1
               ELSE 0
           END AS IsValid
    FROM voucher_usage vu
    JOIN voucher v ON vu.VoucherID = v.VoucherID
    WHERE vu.CustID = ? AND vu.UsedAt IS NULL AND v.VoucherStatus = 'Active'
    ORDER BY vu.ClaimedAt DESC
");
$stmt->execute([$custID]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get and clear any success or error messages stored in session
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Vouchers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="my_vouchers.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
        </div>
        <ul class="sidebar-menu">
            <li><a href="account.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="order_history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="rate_products.php"><i class="fa fa-star" style="color: white;"></i>Rate</a></li>
            <li><a href="topup.php"><i class="fa-solid fa-money-check-dollar" style="color: white;"></i>Top Up</a></li>
            <li class="active"><a href="my_vouchers.php"><i class="fa-solid fa-ticket" style="color: white;"></i>My Voucher</a></li>
        </ul>
        <div class="sidebar-footer">
            <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> LOG OUT</button>
        </div>
    </div>

    <div class="container">
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
                        <?php if (!empty($voucher['VoucherPicture'])): ?>
                            <div class="voucher-image">
                                <img src="image/voucher/<?= htmlspecialchars($voucher['VoucherPicture']) ?>" alt="Voucher">
                            </div>
                        <?php endif; ?>

                        <div class="voucher-details">
                            <div class="voucher-code"><?= htmlspecialchars($voucher['VoucherCode']) ?></div>
                            <div><?= htmlspecialchars($voucher['VoucherDesc']) ?></div>
                            <div class="discount-value">RM <?= number_format($voucher['DiscountValue'], 2) ?> OFF</div>
                            <?php if ($voucher['MinPurchase'] > 0): ?>
                                <div>Min. Purchase: RM <?= number_format($voucher['MinPurchase'], 2) ?></div>
                            <?php endif; ?>
                            <?php if ($voucher['ExpireDate']): ?>
                                <div>Expires: <?= date('d M Y', strtotime($voucher['ExpireDate'])) ?></div>
                            <?php endif; ?>
                            <div>Claimed: <?= date('d M Y H:i', strtotime($voucher['ClaimedAt'])) ?></div>

                            <?php if (!$voucher['IsValid']): ?>
                                <div class="text-danger">This voucher has expired</div>
                                <a href="delete_voucher.php?id=<?= $voucher['UsageID'] ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this expired voucher?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't claimed any vouchers yet. <a href="voucher_list.php">Browse available vouchers</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>

<?php include 'footer.php'; ?>
