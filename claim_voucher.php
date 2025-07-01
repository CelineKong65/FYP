<?php
session_start();
include 'config.php';

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to claim vouchers.";
    header("Location: login.php");
    exit();
}
// Get the logged-in user's ID from the session
$custID = $_SESSION['user_id'];
// Get the voucher ID from the URL query parameter, or null if not set
$voucherID = $_GET['voucher_id'] ?? null;

// If no voucher ID is provided, redirect with error
if (!$voucherID) {
    $_SESSION['error'] = "No voucher specified.";
    header("Location: voucher_list.php");
    exit();
}

try {
    // Begin database transaction
    $conn->beginTransaction();

    // Check if voucher exists and is active and not expired
    $stmt = $conn->prepare("
        SELECT VoucherID, ExpireDate 
        FROM voucher 
        WHERE VoucherID = ? 
        AND VoucherStatus = 'Active'
        AND (ExpireDate IS NULL OR ExpireDate >= CURDATE())
    ");
    $stmt->execute([$voucherID]);  // Execute the query with the provided voucher ID
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);  // Fetch result as associative array

    // If voucher not found or expired, throw an error
    if (!$voucher) {
        throw new Exception("Voucher not available or has expired.");
    }

    // Check if voucher is already claimed by this user
    $stmt = $conn->prepare("
        SELECT 1 FROM voucher_usage 
        WHERE VoucherID = ? AND CustID = ?
    ");
    $stmt->execute([$voucherID, $custID]);

    // If a record is found, throw an error
    if ($stmt->fetch()) {
        throw new Exception("You have already claimed this voucher.");
    }

    // Insert a new claim record into the voucher_usage table
    $stmt = $conn->prepare("
        INSERT INTO voucher_usage (VoucherID, CustID, ClaimedAt) 
        VALUES (?, ?, NOW())
    ");

    // If the insert fails, throw an error
    if (!$stmt->execute([$voucherID, $custID])) {
        throw new Exception("Failed to claim voucher.");
    }
    // If all is successful, commit the transaction
    $conn->commit();
    $_SESSION['success'] = "Voucher claimed successfully!";
    header("Location: my_vouchers.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    // Store the error message in session and redirect
    $_SESSION['error'] = $e->getMessage();
    header("Location: voucher_list.php");
    exit();
}
?>
