<?php 
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['CartID'])) {
    $cartID = $_POST['CartID'];
    $success = false;

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Check if Size is set
        if (isset($_POST['Size'])) {
            $newSize = trim($_POST['Size']);
            // Remove any stock information that might have been included
            $newSize = preg_replace('/\s*\d+ available.*/', '', $newSize);
            $newSize = trim($newSize) === '' ? 'Standard Only' : trim($newSize);

            $query = "UPDATE cart SET Size = :size WHERE CartID = :cartID";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':size', $newSize, PDO::PARAM_STR);
            $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
            $stmt->execute();

            // If size changed, we should reset quantity to 1 to prevent stock issues
            if ($stmt->rowCount() > 0) {
                $query = "UPDATE cart SET Quantity = 1 WHERE CartID = :cartID";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
                $stmt->execute();
                
                $_SESSION['success'] = "Size updated successfully! Quantity reset to 1.";
                $success = true;
            }
        }

        // Check if Quantity is set
        if (isset($_POST['Quantity']) && is_numeric($_POST['Quantity'])) {
            $newQuantity = intval($_POST['Quantity']);

            // Get ProductID and current Size from cart
            $stmt = $conn->prepare("SELECT ProductID, Size FROM cart WHERE CartID = :cartID");
            $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
            $stmt->execute();
            $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cartItem) {
                $productID = $cartItem['ProductID'];
                $size = $cartItem['Size'];
                
                // Clean the size value for comparison
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

                // Validate quantity
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

        $conn->commit();
        
        if (!$success && !isset($_SESSION['error'])) {
            $_SESSION['error'] = "No changes were made";
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }
}

header("Location: shopping_cart.php");
exit;
?>