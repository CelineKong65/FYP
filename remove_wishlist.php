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

// Check if the wish_id is sent from the form
if (!isset($_POST['wish_id']) || empty($_POST['wish_id'])) {
    echo "<script>alert('Wishlist ID is missing.'); window.location.href='wishlist.php';</script>";
    exit();
}
// Get the wish_id and make sure it's an integer
$wishID = (int) $_POST['wish_id'];

try {
    // Check if the wishlist item exists and belongs to the user
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE WishID = :wishID AND CustID = :CustID");
    $stmt->execute(['wishID' => $wishID, 'CustID' => $CustID]);
    // If the item doesn't exist or doesn't belong to the user
    if ($stmt->rowCount() == 0) {
        echo "<script>alert('Wishlist item not found or does not belong to you.'); window.location.href='wishlist.php';</script>";
        exit();
    }

    // Delete the wishlist item
    $deleteStmt = $conn->prepare("DELETE FROM wishlist WHERE WishID = :wishID AND CustID = :CustID");
    // If deletion is successful, show success message
    if ($deleteStmt->execute(['wishID' => $wishID, 'CustID' => $CustID])) {
        echo "<script>alert('Item removed from wishlist successfully!'); window.location.href='wishlist.php';</script>";
    } else {
        // If deletion failed, show error message
        echo "<script>alert('Failed to remove the item from wishlist.'); window.location.href='wishlist.php';</script>";
    }
} catch (PDOException $e) {
    // If there is a database error, show it
    echo "<script>alert('Database error: " . $e->getMessage() . "'); window.location.href='wishlist.php';</script>";
}
?>
