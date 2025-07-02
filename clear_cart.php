<?php
include 'config.php';
session_start();

// Retrieve the logged-in customer's ID from the session
$custID = $_SESSION['user_id'];

// Delete all items from the cart for the specific customer
$query = "DELETE FROM cart WHERE CustID = :custID";
$stmt = $conn->prepare($query);
// Bind the customer ID parameter securely to the SQL query
$stmt->bindParam(':custID', $custID, PDO::PARAM_INT);

if ($stmt->execute()) {
    // If successful, redirect the user back to the shopping cart page
    header("Location: shopping_cart.php");
    exit();
} else {
    // If there is an error, show an error message
    echo "Error: Unable to clear cart.";
}
?>
