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

    if (!$productID) {
        echo "<script>alert('Missing product ID.'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // Standardize 'Standard Only' usage
    $sizeForQuery = ($size === 'Standard Only' || $size === null) ? 'Standard Only' : $size;

    // 1. Check stock availability
    $stockQuery = "SELECT Stock FROM product_size WHERE ProductID = :productID AND 
                  (Size = :size OR (Size IS NULL AND :size = 'Standard Only'))";
    $stockStmt = $conn->prepare($stockQuery);
    $stockStmt->execute([
        ':productID' => $productID,
        ':size' => $sizeForQuery
    ]);
    $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock || $stock['Stock'] <= 0) {
        echo "<script>alert('This product is out of stock!'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // 2. Check if already in cart
    $checkCart = $conn->prepare("SELECT * FROM cart WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
    $checkCart->execute([
        ':custID' => $CustID,
        ':productID' => $productID,
        ':size' => $sizeForQuery
    ]);

    if ($checkCart->rowCount() > 0) {
        // Already in cart: update quantity
        $cartItem = $checkCart->fetch(PDO::FETCH_ASSOC);
        $newQty = $cartItem['Quantity'] + $qty;

        if ($newQty > $stock['Stock']) {
            echo "<script>alert('Cannot add more than available stock!'); window.location.href='wishlist.php';</script>";
            exit();
        }

        $updateCart = $conn->prepare("UPDATE cart SET Quantity = Quantity + :qty 
                                      WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
        $updateCart->execute([
            ':qty' => $qty,
            ':custID' => $CustID,
            ':productID' => $productID,
            ':size' => $sizeForQuery
        ]);
    } else {
        // Not in cart: insert new item
        $insertCart = $conn->prepare("INSERT INTO cart (CustID, ProductID, ProductName, Size, ProductPrice, Quantity) 
            SELECT :custID, p.ProductID, p.ProductName, :size, p.ProductPrice, :qty
            FROM product p
            WHERE p.ProductID = :productID");
        $insertCart->execute([
            ':custID' => $CustID,
            ':productID' => $productID,
            ':size' => $sizeForQuery,
            ':qty' => $qty
        ]);
    }

    // 3. Remove from wishlist
    $removeWishlist = $conn->prepare("DELETE FROM wishlist 
                                      WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
    $removeWishlist->execute([
        ':custID' => $CustID,
        ':productID' => $productID,
        ':size' => $sizeForQuery
    ]);

    echo "<script>alert('Product added to cart successfully!'); window.location.href='wishlist.php';</script>";
}
?>
