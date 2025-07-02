<?php
session_start();
include 'config.php';

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
// Get the logged-in user's ID from the session
$CustID = $_SESSION['user_id'];

// Check if the request method is POST (form submission)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get product ID from the POST data, or set to null if not provided
    $productID = $_POST['productID'] ?? null;
    // Get selected size from POST data, or null if not provided
    $size = $_POST['size'] ?? null;
    // Get quantity from POST data, default to 1 if not provided
    $qty = $_POST['qty'] ?? 1;

    // If product ID is missing, show an alert and redirect back to wishlist
    if (!$productID) {
        echo "<script>alert('Missing product ID.'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // Standardize 'Standard Only' usage
    $sizeForQuery = ($size === 'Standard Only' || $size === null) ? 'Standard Only' : $size;

    // 1. Check stock availability from product_size table for given product and size
    $stockQuery = "SELECT Stock FROM product_size WHERE ProductID = :productID AND 
                  (Size = :size OR (Size IS NULL AND :size = 'Standard Only'))";
    $stockStmt = $conn->prepare($stockQuery);
    $stockStmt->execute([
        ':productID' => $productID,
        ':size' => $sizeForQuery
    ]);
    // Fetch stock information as associative array
    $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);
    // If no stock data found or stock is 0 or less, show alert and redirect
    if (!$stock || $stock['Stock'] <= 0) {
        echo "<script>alert('This product is out of stock!'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // 2. Check if the product already exists in the cart for the current user and size
    $checkCart = $conn->prepare("SELECT * FROM cart WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
    $checkCart->execute([
        ':custID' => $CustID,
        ':productID' => $productID,
        ':size' => $sizeForQuery
    ]);

    // If item is already in cart, update the quantity
    if ($checkCart->rowCount() > 0) {
        // Fetch existing cart item
        $cartItem = $checkCart->fetch(PDO::FETCH_ASSOC);
        // Calculate new quantity
        $newQty = $cartItem['Quantity'] + $qty;
        // If new quantity exceeds stock, alert and redirect
        if ($newQty > $stock['Stock']) {
            echo "<script>alert('Cannot add more than available stock!'); window.location.href='wishlist.php';</script>";
            exit();
        }
        // Update cart item quantity
        $updateCart = $conn->prepare("UPDATE cart SET Quantity = Quantity + :qty 
                                      WHERE CustID = :custID AND ProductID = :productID AND Size = :size");
        $updateCart->execute([
            ':qty' => $qty,
            ':custID' => $CustID,
            ':productID' => $productID,
            ':size' => $sizeForQuery
        ]);
    } else {
        // If item is not in cart, insert a new row into the cart table
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

    // 3. Remove the product from wishlist once it is added to the cart
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
