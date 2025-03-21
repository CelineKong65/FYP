<?php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please log in to remove items from your wishlist.";
    exit();
}

$CustID = $_SESSION['user_id'];

// Validate product ID
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo "Product ID is missing.";
    exit();
}

$productID = (int) $_POST['product_id'];

try {
    // Check if the product exists in the wishlist
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE CustID = :CustID AND ProductID = :productID");
    $stmt->execute(['CustID' => $CustID, 'productID' => $productID]);

    if ($stmt->rowCount() == 0) {
        echo "Product is not in your wishlist.";
        exit();
    }

    // Remove product from wishlist
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE CustID = :CustID AND ProductID = :productID");
    if ($stmt->execute(['CustID' => $CustID, 'productID' => $productID])) {
        echo "Removed from wishlist successfully!";
    } else {
        echo "Failed to remove from wishlist.";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
