<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please log in to add items to your wishlist."]);
    exit();
}

$CustID = $_SESSION['user_id']; 

// Validate product ID
if (!isset($_POST['productID']) || empty($_POST['productID'])) {
    echo json_encode(["success" => false, "message" => "Product ID is missing."]);
    exit();
}

$productID = (int) $_POST['productID'];

try {
    // Check if the product is already in the wishlist
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE CustID = :CustID AND ProductID = :productID");
    $stmt->execute(['CustID' => $CustID, 'productID' => $productID]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Product is already in your wishlist."]);
        exit();
    }

    // Insert into wishlist
    $stmt = $conn->prepare("INSERT INTO wishlist (CustID, ProductID) VALUES (:CustID, :productID)");
    if ($stmt->execute(['CustID' => $CustID, 'productID' => $productID])) {
        echo json_encode(["success" => true, "message" => "Added to wishlist successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add to wishlist."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
