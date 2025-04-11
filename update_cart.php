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
    
    // Check if Quantity is set
    if (isset($_POST['Quantity']) && is_numeric($_POST['Quantity']) && $_POST['Quantity'] > 0) {
        $newQuantity = $_POST['Quantity'];
        $query = "UPDATE cart SET Quantity = :quantity WHERE CartID = :cartID";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
        $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
        $stmt->execute();
    }
}

// Refresh the shopping cart page
header("Location: shopping_cart.php");
exit;
?>
