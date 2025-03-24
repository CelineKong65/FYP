<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to remove items from your wishlist.'); window.location.href='login.php';</script>";
    exit();
}

$CustID = $_SESSION['user_id'];

// Validate product ID
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo "<script>alert('Product ID is missing.'); window.location.href='wishlist.php';</script>";
    exit();
}

$productID = (int) $_POST['product_id'];

try {
    // Check if the product exists in the wishlist
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE CustID = :CustID AND ProductID = :productID");
    $stmt->execute(['CustID' => $CustID, 'productID' => $productID]);

    if ($stmt->rowCount() == 0) {
        echo "<script>alert('Product is not in your wishlist.'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // Remove product from wishlist
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE CustID = :CustID AND ProductID = :productID");
    if ($stmt->execute(['CustID' => $CustID, 'productID' => $productID])) {
        echo "<script>alert('Removed from wishlist successfully!'); window.location.href='wishlist.php';</script>";
    } else {
        echo "<script>alert('Failed to remove from wishlist.'); window.location.href='wishlist.php';</script>";
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "'); window.location.href='wishlist.php';</script>";
}
?>
