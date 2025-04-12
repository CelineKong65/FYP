<?php 
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['CartID'])) {
    $cartID = $_POST['CartID'];

    // Check if Size is set
    if (isset($_POST['Size'])) {
        $newSize = $_POST['Size'];
        $query = "UPDATE cart SET Size = :size WHERE CartID = :cartID";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':size', $newSize, PDO::PARAM_STR);
        $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Check if Quantity is set and greater than zero
    if (isset($_POST['Quantity']) && is_numeric($_POST['Quantity']) && $_POST['Quantity'] > 0) {
        $newQuantity = intval($_POST['Quantity']);

        // Get ProductID and Size from cart
        $stmt = $conn->prepare("SELECT ProductID, Size FROM cart WHERE CartID = :cartID");
        $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            $productID = $cartItem['ProductID'];
            $size = $cartItem['Size'];

            // Get available stock for this product and size
            $stmt = $conn->prepare("SELECT Stock FROM product_size WHERE ProductID = :productID AND Size = :size");
            $stmt->bindParam(':productID', $productID, PDO::PARAM_INT);
            $stmt->bindParam(':size', $size, PDO::PARAM_STR);
            $stmt->execute();
            $stockData = $stmt->fetch(PDO::FETCH_ASSOC);
            $availableStock = $stockData['Stock'] ?? 0;

            // Limit quantity to available stock and ensure it's not zero
            $finalQuantity = max(1, min($newQuantity, $availableStock));

            // Update quantity in cart
            $stmt = $conn->prepare("UPDATE cart SET Quantity = :quantity WHERE CartID = :cartID");
            $stmt->bindParam(':quantity', $finalQuantity, PDO::PARAM_INT);
            $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

// Refresh the shopping cart page
header("Location: shopping_cart.php");
exit;
?>
