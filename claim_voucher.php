<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to claim vouchers.";
    header("Location: login.php");
    exit();
}

$custID = $_SESSION['user_id'];
$voucherID = $_GET['voucher_id'] ?? null;

if (!$voucherID) {
    $_SESSION['error'] = "No voucher specified.";
    header("Location: voucher_list.php");
    exit();
}

try {
    $conn->beginTransaction();

    // Check if voucher exists and is active
    $stmt = $conn->prepare("
        SELECT VoucherID, ExpireDate 
        FROM voucher 
        WHERE VoucherID = ? 
        AND VorcherStatus = 'Active'
        AND (ExpireDate IS NULL OR ExpireDate >= CURDATE())
    ");
    $stmt->execute([$voucherID]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        throw new Exception("Voucher not available or has expired.");
    }

    // Check if voucher is already claimed by this user
    $stmt = $conn->prepare("
        SELECT 1 FROM voucher_usage 
        WHERE VoucherID = ? AND CustID = ?
    ");
    $stmt->execute([$voucherID, $custID]);
    
    if ($stmt->fetch()) {
        throw new Exception("You have already claimed this voucher.");
    }

    // Insert claim record
    $stmt = $conn->prepare("
        INSERT INTO voucher_usage (VoucherID, CustID, ClaimedAt) 
        VALUES (?, ?, NOW())
    ");
    
    if (!$stmt->execute([$voucherID, $custID])) {
        throw new Exception("Failed to claim voucher.");
    }

    $conn->commit();
    $_SESSION['success'] = "Voucher claimed successfully!";
    header("Location: my_vouchers.php");
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: voucher_list.php");
    exit();
}
?>
