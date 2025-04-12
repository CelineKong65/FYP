<?php
session_start();
include 'config.php';

// Log received POST data
error_log("Received POST data: " . print_r($_POST, true));

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit();
}

if (!isset($_POST["productID"], $_POST["qty"], $_POST["size"])) {
    echo json_encode(["success" => false, "message" => "Missing parameters."]);
    exit();
}

$userID = $_SESSION["user_id"];
$productID = (int)$_POST["productID"];
$quantity = (int)$_POST["qty"]; 
$size = trim($_POST["size"]);

// Log the values before processing
error_log("Processing: UserID=$userID, ProductID=$productID, Qty=$quantity, Size=$size");

// Fetch product details
$stmt = $conn->prepare("SELECT ProductName, ProductPrice FROM product WHERE ProductID = ?");
$stmt->execute([$productID]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(["success" => false, "message" => "Product not found."]);
    exit();
}

// Validate quantity
if ($quantity <= 0) {
    echo json_encode(["success" => false, "message" => "Quantity must be at least 1."]);
    exit();
}

$productName = $product["ProductName"];
$productPrice = $product["ProductPrice"];

// Check if item already exists in cart
$stmt = $conn->prepare("SELECT * FROM cart WHERE CustID = ? AND ProductID = ? AND Size = ?");
$stmt->execute([$userID, $productID, $size]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Update quantity (REPLACE existing quantity, not increment)
    $updateQuery = "UPDATE cart SET Quantity = ? WHERE CustID = ? AND ProductID = ? AND Size = ?";
    error_log("Executing UPDATE: $updateQuery with Qty=$quantity");
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([$quantity, $userID, $productID, $size]);
} else {
    // Insert new item
    $insertQuery = "INSERT INTO cart (CustID, ProductID, Quantity, Size, ProductName, ProductPrice) VALUES (?, ?, ?, ?, ?, ?)";
    error_log("Executing INSERT: $insertQuery with Qty=$quantity");
    $stmt = $conn->prepare($insertQuery);
    $stmt->execute([$userID, $productID, $quantity, $size, $productName, $productPrice]);
}

// Get updated cart count
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cart WHERE CustID = ?");
$stmt->execute([$userID]);
$cartCount = $stmt->fetchColumn();

echo json_encode(["success" => true, "cartCount" => $cartCount]);
?>