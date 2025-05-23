<?php
include 'config.php';
session_start();

$custID = $_SESSION['user_id'];

// Delete all items from the cart for the specific customer
$query = "DELETE FROM cart WHERE CustID = :custID";
$stmt = $conn->prepare($query);
$stmt->bindParam(':custID', $custID, PDO::PARAM_INT);

if ($stmt->execute()) {
    // Redirect back to the cart page after clearing the cart
    header("Location: shopping_cart.php");
    exit();
} else {
    echo "Error: Unable to clear cart.";
}
?>
