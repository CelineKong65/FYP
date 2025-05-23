<?php
session_start();
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$CustID = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productID = $_POST['productID'] ?? null;
    $size = $_POST['size'] ?? null;
    $qty = $_POST['qty'] ?? 1;

    if (!$productID || !$size) {
        echo "<script>alert('Missing product ID or size.'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // 1. Check stock availability first
    $stockQuery = "SELECT Stock FROM product_size WHERE ProductID = :productID AND Size = :size";
    $stockStmt = $conn->prepare($stockQuery);
    $stockStmt->execute([
        ':productID' => $productID,
        ':size' => $size
    ]);
    $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock || $stock['Stock'] <= 0) {
        echo "<script>alert('This product is out of stock!'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // 2. Check if the product already exists in cart (same size)
    $checkCart = $conn->prepare("SELECT * FROM cart WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
    $checkCart->execute([
        ':custID' => $CustID,
        ':productID' => $productID,
        ':size' => $size
    ]);

    if ($checkCart->rowCount() > 0) {
        // Check if adding more would exceed available stock
        $cartItem = $checkCart->fetch(PDO::FETCH_ASSOC);
        $newQty = $cartItem['Quantity'] + $qty;
        
        if ($newQty > $stock['Stock']) {
            echo "<script>alert('Cannot add more than available stock!'); window.location.href='wishlist.php';</script>";
            exit();
        }

        // Update quantity if exists
        $updateCart = $conn->prepare("UPDATE cart SET Quantity = Quantity + :qty WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
        $updateCart->execute([
            ':qty' => $qty,
            ':custID' => $CustID,
            ':productID' => $productID,
            ':size' => $size
        ]);
    } else {
        // Insert new item to cart
        $insertCart = $conn->prepare("INSERT INTO cart (CustID, ProductID, Size, Quantity) VALUES (:custID, :productID, :size, :qty)");
        $insertCart->execute([
            ':custID' => $CustID,
            ':productID' => $productID,
            ':size' => $size,
            ':qty' => $qty
        ]);
    }

    // 3. Remove the item from wishlist after adding to cart
    $removeWishlist = $conn->prepare("DELETE FROM wishlist WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
    $removeWishlist->execute([
        ':custID' => $CustID,
        ':productID' => $productID,
        ':size' => $size
    ]);

    echo "<script>alert('Product added to cart successfully!'); window.location.href='wishlist.php';</script>";
}
?>