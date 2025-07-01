<?php
session_start();
include 'config.php';
// Check if user is logged in and both 'wish_id' and 'new_size' are provided via POST
if (!isset($_SESSION['user_id']) || !isset($_POST['wish_id']) || !isset($_POST['new_size'])) {
    header("Location: wishlist.php"); // If any required data is missing, redirect to the wishlist page
    exit();
}
// Get the submitted wishlist ID
$wishID = $_POST['wish_id'];

// Determine the new size value
// If it's "Standard Only" or 'NULL' (string), store NULL in the database
$newSize = ($_POST['new_size'] === 'Standard Only' || $_POST['new_size'] === 'NULL') ? null : $_POST['new_size'];

// Update the size in the wishlist
$updateQuery = "UPDATE wishlist SET Size = :size WHERE WishID = :wish_id";
$stmt = $conn->prepare($updateQuery);
$stmt->bindParam(':size', $newSize);
$stmt->bindParam(':wish_id', $wishID, PDO::PARAM_INT);
$stmt->execute(); // Execute the update

// Redirect the user back to the wishlist page after updating
header("Location: wishlist.php");
exit();
?>