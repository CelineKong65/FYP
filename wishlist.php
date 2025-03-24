<?php
session_start();
include 'config.php'; 
include 'header.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID

// Fetch wishlist items
$query = "SELECT 
            p.ProductID,
            p.ProductName, 
            p.ProductPrice, 
            (SELECT pc.Picture FROM product_color pc WHERE pc.ProductID = p.ProductID LIMIT 1) AS Picture
          FROM wishlist w
          JOIN product p ON w.ProductID = p.ProductID
          WHERE w.CustID = :user_id";


$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
    <link rel="stylesheet" href="wishlist.css"> 
</head>
<body>

<h2>My Wishlist</h2>

<?php if (empty($wishlistItems)): ?>
    <p class="empty-message">Your wishlist is empty. Start adding your favorite products!</p>
<?php else: ?>
    <table border="1">
        <tr>
            <th>Image</th>
            <th>Product Name</th>
            <th>Price (RM)</th>
            <th>Action</th>
        </tr>
        <?php foreach ($wishlistItems as $item): ?>
        <tr>
            <td><img src="image/<?php echo htmlspecialchars($item['Picture']); ?>" width="100" alt="Product Image"></td>
            <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
            <td><?php echo number_format($item['ProductPrice'], 2); ?></td>
            <td>
                <form action="remove_wishlist.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                    <button type="submit">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>

<?php
include 'footer.php';
?>