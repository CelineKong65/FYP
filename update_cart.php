<?php 
include 'config.php';
session_start();

// Check if the form is submitted via POST and contains a valid CartID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['CartID'])) {
    $cartID = $_POST['CartID'];
    $success = false;

    try {
        // Begin transaction
        $conn->beginTransaction();

        // 1. Handle Size Change
        // Check if Size is set
        if (isset($_POST['Size'])) {
            $newSize = trim($_POST['Size']);
            // Remove any text like " - 3 available" from the size string
            $newSize = preg_replace('/\s*\d+ available.*/', '', $newSize);
            // Convert empty size to 'Standard Only'
            $newSize = trim($newSize) === '' ? 'Standard Only' : trim($newSize);

            // Update the cart's size
            $query = "UPDATE cart SET Size = :size WHERE CartID = :cartID";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':size', $newSize, PDO::PARAM_STR);
            $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
            $stmt->execute();

            // If the size changed, reset quantity to 1 to avoid stock mismatch
            if ($stmt->rowCount() > 0) {
                $query = "UPDATE cart SET Quantity = 1 WHERE CartID = :cartID";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
                $stmt->execute();
                
                $_SESSION['success'] = "Size updated successfully! Quantity reset to 1.";
                $success = true;
            }
        }

        // 2. Handle Quantity Change
        // Check if Quantity is set
        if (isset($_POST['Quantity']) && is_numeric($_POST['Quantity'])) {
            $newQuantity = intval($_POST['Quantity']);

            // Get the current product ID and size from the cart
            $stmt = $conn->prepare("SELECT ProductID, Size FROM cart WHERE CartID = :cartID");
            $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
            $stmt->execute();
            $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cartItem) {
                $productID = $cartItem['ProductID'];
                $size = $cartItem['Size'];
                
                // Clean the size value for comparison (remove text like " - 3 available")
                $cleanSize = preg_replace('/\s*\d+ available.*/', '', $size);
                $cleanSize = trim($cleanSize) === '' ? 'Standard Only' : trim($cleanSize);

                // Get available stock for this product and size
                if ($cleanSize === 'Standard Only') {
                    $stmt = $conn->prepare("SELECT Stock FROM product_size WHERE ProductID = :productID AND Size IS NULL");
                } else {
                    $stmt = $conn->prepare("SELECT Stock FROM product_size WHERE ProductID = :productID AND Size = :size");
                    $stmt->bindParam(':size', $cleanSize, PDO::PARAM_STR);
                }
                
                $stmt->bindParam(':productID', $productID, PDO::PARAM_INT);
                $stmt->execute();
                $stockData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $availableStock = $stockData['Stock'] ?? 0;

                // Validate the quantity input
                if ($newQuantity < 1) {
                    $_SESSION['error'] = "Quantity must be at least 1";
                    $conn->rollBack();
                    header("Location: shopping_cart.php");
                    exit;
                }

                if ($newQuantity > $availableStock) {
                    $_SESSION['error'] = "Only $availableStock items available in stock";
                    $conn->rollBack();
                    header("Location: shopping_cart.php");
                    exit;
                }

                // Update quantity in cart
                $stmt = $conn->prepare("UPDATE cart SET Quantity = :quantity WHERE CartID = :cartID");
                $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
                $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $_SESSION['success'] = "Quantity updated successfully!";
                    $success = true;
                }
            }
        }
        // Commit transaction if everything is successful
        $conn->commit();
        
        if (!$success && !isset($_SESSION['error'])) {
            $_SESSION['error'] = "No changes were made";
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }
}
// Redirect back to shopping cart page
header("Location: shopping_cart.php");
exit;
?>