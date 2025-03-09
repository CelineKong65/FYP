<?php
include 'config.php';
session_start();

if (isset($_GET['CartID'])) {
    $cartID = $_GET['CartID'];

    // Prepare and execute the DELETE query
    $query = "DELETE FROM cart WHERE CartID = :cartID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Redirect back to the shopping cart page after deletion
        header("Location: shopping_cart.php");
        exit();
    } else {
        echo "Error: Unable to remove item.";
    }
} else {
    echo "Invalid request.";
}
?>
