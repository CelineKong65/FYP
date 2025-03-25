<?php
session_start();
include 'config.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to add items to your wishlist.');
}

 // Get logged-in user's ID
$userID = $_SESSION['user_id'];

// Validate product ID exists in POST request
if (!isset($_POST['productID']) || empty($_POST['productID'])) {
    die('Product ID is missing.');
}

$productID = (int) $_POST['productID']; // Convert to integer for safety

try {
    // Check if product already exists in user's wishlist
    $checkQuery = $conn->prepare("SELECT * FROM wishlist WHERE CustID = ? AND ProductID = ?");
    $checkQuery->execute([$userID, $productID]);

    if ($checkQuery->rowCount() > 0) {
        die('This product is already in your wishlist.');
    }

    // Add product to wishlist
    $insertQuery = $conn->prepare("INSERT INTO wishlist (CustID, ProductID) VALUES (?, ?)");
    
    if ($insertQuery->execute([$userID, $productID])) {
        die('Product added to wishlist successfully!');
    } else {
        die('Failed to add to wishlist.');
    }
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>