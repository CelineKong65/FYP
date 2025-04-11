<?php
session_start();
include 'config.php';

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
$size = $_POST["size"];

// Validate quantity
if ($quantity <= 0) {
    echo json_encode(["success" => false, "message" => "Quantity must be at least 1."]);
    exit();
}

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

// Check if product with the same ID and size already exists in cart
$stmt = $conn->prepare("SELECT * FROM cart WHERE CustID = ? AND ProductID = ? AND Size = ?");
$stmt->execute([$userID, $productID, $size]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Update quantity
    $stmt = $conn->prepare("UPDATE cart SET Quantity = Quantity + ? WHERE CustID = ? AND ProductID = ? AND Size = ?");
    $stmt->execute([$quantity, $userID, $productID, $size]);
} else {
    // Insert new item
    $stmt = $conn->prepare("INSERT INTO cart (CustID, ProductID, Quantity, Size, ProductName, ProductPrice) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userID, $productID, $quantity, $size, $productName, $productPrice]);
}

// Get updated cart count
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cart WHERE CustID = ?");
$stmt->execute([$userID]);
$cartCount = $stmt->fetchColumn();

echo json_encode(["success" => true, "cartCount" => $cartCount]);
?>
