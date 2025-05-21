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
    AND v.VorcherStatus = 'Active'
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

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        min-height: 100vh;
    }

    h1 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
        margin-top: 150px;
    }

    .voucher-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .voucher-card {
        border: 1px solid #ddd;
        border-radius: 10px;
        background-color: #fff;
        padding: 20px;
        transition: box-shadow 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        position: relative;
    }

    .voucher-card:hover {
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .voucher-code {
        font-weight: 700;
        font-size: 1.4em;
        color: #007bff;
        margin-bottom: 5px;
    }

    .voucher-desc {
        font-size: 1em;
        color: #555;
        margin-bottom: 10px;
    }

    .discount-value {
        color: #28a745;
        font-weight: 700;
        font-size: 1.1em;
        margin-bottom: 5px;
    }

    .voucher-card div {
        margin-bottom: 6px;
        font-size: 0.95em;
        color: #444;
    }

    .btn {
        display: inline-block;
        padding: 10px 18px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        transition: background-color 0.2s ease;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-size: 0.95em;
    }

    .alert-danger {
        color: #a94442;
        background-color: #f2dede;
        border-left: 5px solid #a94442;
    }

    .alert-info {
        color: #31708f;
        background-color: #d9edf7;
        border-left: 5px solid #31708f;
    }

    .back-link {
        text-align: center;
        margin-top: 30px;
    }

    .back-link a {
        text-decoration: none;
        color: #555;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .back-link a:hover {
        color: #007bff;
    }

    /* Optional: If you want to show voucher images */
    .voucher-card img {
        max-width: 100%;
        border-radius: 6px;
        margin-bottom: 10px;
        height: auto;
        object-fit: cover;
    }
</style>

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
