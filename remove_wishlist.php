<?php
session_start();
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$CustID = $_SESSION['user_id'];

// Validate wishlist ID
if (!isset($_POST['wish_id']) || empty($_POST['wish_id'])) {
    echo "<script>alert('Wishlist ID is missing.'); window.location.href='wishlist.php';</script>";
    exit();
}

$wishID = (int) $_POST['wish_id'];

try {
    // Check if the wishlist item exists and belongs to the user
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE WishID = :wishID AND CustID = :CustID");
    $stmt->execute(['wishID' => $wishID, 'CustID' => $CustID]);

    if ($stmt->rowCount() == 0) {
        echo "<script>alert('Wishlist item not found or does not belong to you.'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // Delete the wishlist item
    $deleteStmt = $conn->prepare("DELETE FROM wishlist WHERE WishID = :wishID AND CustID = :CustID");
    if ($deleteStmt->execute(['wishID' => $wishID, 'CustID' => $CustID])) {
        echo "<script>alert('Item removed from wishlist successfully!'); window.location.href='wishlist.php';</script>";
    } else {
        echo "<script>alert('Failed to remove the item from wishlist.'); window.location.href='wishlist.php';</script>";
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "'); window.location.href='wishlist.php';</script>";
}
?>
