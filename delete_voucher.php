<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: my_vouchers.php");
    exit();
}

$custID = $_SESSION['user_id'];
$usageID = $_GET['id'];

$stmt = $conn->prepare("
    DELETE FROM voucher_usage 
    WHERE UsageID = ? 
    AND CustID = ? 
    AND UsedAt IS NULL 
    AND VoucherID IN (
        SELECT VoucherID FROM voucher 
        WHERE ExpireDate IS NOT NULL AND ExpireDate < CURDATE()
    )
");

if ($stmt->execute([$usageID, $custID])) {
    $_SESSION['success'] = "Expired voucher deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete the voucher.";
}

header("Location: my_vouchers.php");
exit();
?>
