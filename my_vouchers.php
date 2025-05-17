<?php
include 'config.php';
include 'header.php'; 
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
           v.MinPurchase, v.ExpireDate, v.VoucherPicture, vu.ClaimedAt, 
           CASE 
               WHEN v.ExpireDate IS NULL THEN 1
               WHEN v.ExpireDate >= CURDATE() THEN 1
               ELSE 0
           END AS IsValid
    FROM voucher_usage vu
    JOIN voucher v ON vu.VoucherID = v.VoucherID
    WHERE vu.CustID = ? AND vu.UsedAt IS NULL
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
    <link rel="stylesheet" href="">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Sidebar Styles */
.sidebar {
    width: 250px;
    height: 100%;
    background-color: #007BFF;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 80px;
    color: white;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h2 {
    margin: 0;
    color: white;
    font-size: 1.5rem;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    transition: background-color 0.3s;
}

.sidebar-menu li:hover {
    background-color: rgba(255,255,255,0.1);
}

.sidebar-menu li.active {
    background-color: rgba(255,255,255,0.2);
}

.sidebar-menu li a {
    color: white;
    text-decoration: none;
    display: block;
}

.sidebar-menu li i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    padding: 20px;
    text-align: center;
}

.logout-btn {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    font-weight: bold;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background-color: #c0392b;
}


.voucher-list {
    margin-left: 270px;
    padding: 20px;
    margin-top: 100px;
    margin-bottom: 50px;
}

.voucher-card {
    display: flex;
    align-items: flex-start;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    background: linear-gradient(to right, #f9f9f9, #ffffff);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
}

.voucher-card:hover {
    transform: translateY(-3px);
}

.voucher-card.valid {
    border-left: 5px solid #28a745;
}

.voucher-card.expired {
    border-left: 5px solid #dc3545;
    opacity: 0.7;
}

.voucher-image {
    flex: 0 0 320px;
    margin-right: 15px;
}

.voucher-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #ccc;
}

.voucher-details {
    flex: 1;
}

.voucher-code {
    font-weight: bold;
    font-size: 1.3em;
    color: #007bff;
    margin-bottom: 5px;
}

.voucher-desc {
    margin-bottom: 10px;
    color: #333;
}

.discount-value {
    color: #28a745;
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 8px;
}

.text-danger {
    color: #dc3545;
    font-weight: bold;
}


.alert {
    margin-left: 270px;
    padding: 15px 20px;
    border-radius: 8px;
    max-width: 900px;
}

.alert-info a {
    color: #007bff;
    text-decoration: underline;
}

    </style>
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
                                <img src="image/voucher/<?= htmlspecialchars($voucher['VoucherPicture']) ?>" alt="Voucher Image">
                            </div>
                        <?php endif; ?>

                        <div class="voucher-details">
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
                    </div>

                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't claimed any vouchers yet. <a href="voucher_list.php">Browse available vouchers</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>