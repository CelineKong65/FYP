<?php
include 'config.php';
session_start();

// Check if CartID is passed via GET request
if (isset($_GET['CartID'])) {
    $cartID = $_GET['CartID'];   // Store the CartID from the query string

    // Prepare a DELETE query to remove the item from the cart table
    $query = "DELETE FROM cart WHERE CartID = :cartID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cartID', $cartID, PDO::PARAM_INT);
    // Execute the DELETE query
    if ($stmt->execute()) {
        // Redirect back to the shopping cart page after deletion
        header("Location: shopping_cart.php");
        exit();
    } else {
        // If execution fails, display an error message
        echo "Error: Unable to remove item.";
    }
} else {
    // If CartID is not provided in the URL, display an error message
    echo "Invalid request.";
}
?>
