<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['wish_id']) || !isset($_POST['new_size'])) {
    header("Location: wishlist.php");
    exit();
}

$wishID = $_POST['wish_id'];
$newSize = $_POST['new_size'] === 'Standard Only' ? null : $_POST['new_size'];

// Update the size in the wishlist
$updateQuery = "UPDATE wishlist SET Size = :size WHERE WishID = :wish_id";
$stmt = $conn->prepare($updateQuery);
$stmt->bindParam(':size', $newSize);
$stmt->bindParam(':wish_id', $wishID, PDO::PARAM_INT);
$stmt->execute();

header("Location: wishlist.php");
exit();
?>
