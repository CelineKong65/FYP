<?php
include 'config.php';
session_start();

// Check if the user is logged in, if not redirect to my vouchers page
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: my_vouchers.php");
    exit();
}
// Get the logged-in user's ID from the session
$custID = $_SESSION['user_id'];
// Get the voucher usage ID from the URL
$usageID = $_GET['id'];
// Prepare SQL statement to delete expired voucher that hasn't been used yet
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
// Execute the query with provided usage ID and customer ID
if ($stmt->execute([$usageID, $custID])) {
    // If successful, store success message in session
    $_SESSION['success'] = "Expired voucher deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete the voucher.";
}
// Redirect back to the my vouchers page
header("Location: my_vouchers.php");
exit();
?>
