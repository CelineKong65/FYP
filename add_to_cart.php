<?php
session_start();
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit();
}

if (!isset($_POST["productID"], $_POST["qty"], $_POST["size"], $_POST["color"])) {
    echo json_encode(["success" => false, "message" => "Missing parameters."]);
    exit();
}

$userID = $_SESSION["user_id"];
$productID = (int)$_POST["productID"];
$quantity = (int)$_POST["qty"];
$size = $_POST["size"];
$color = $_POST["color"];

// Fetch product details
$stmt = $conn->prepare("SELECT ProductName, ProductPrice FROM product WHERE ProductID = ?");
$stmt->execute([$productID]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(["success" => false, "message" => "Product not found."]);
    exit();
}

$productName = $product["ProductName"];
$productPrice = $product["ProductPrice"];

// Check if product with the same ID, color, and size already exists in cart
$stmt = $conn->prepare("SELECT * FROM cart WHERE CustID = ? AND ProductID = ? AND Color = ? AND Size = ?");
$stmt->execute([$userID, $productID, $color, $size]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Update quantity if the same product with the same color and size is already in the cart
    $stmt = $conn->prepare("UPDATE cart SET Quantity = Quantity + ? WHERE CustID = ? AND ProductID = ? AND Color = ? AND Size = ?");
    $stmt->execute([$quantity, $userID, $productID, $color, $size]);
} else {
    // Insert new item into cart if it doesn't exist
    $stmt = $conn->prepare("INSERT INTO cart (CustID, ProductID, Quantity, Size, Color, ProductName, ProductPrice) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userID, $productID, $quantity, $size, $color, $productName, $productPrice]);
}

// Get updated cart count (total number of items, including variations)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cart WHERE CustID = ?");
$stmt->execute([$userID]);
$cartCount = $stmt->fetchColumn();

echo json_encode(["success" => true, "cartCount" => $cartCount]);
?>