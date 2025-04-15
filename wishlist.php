<?php
ob_start();
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
            p.ProductPicture
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
    <div class="wishlist-container">
        <h2>My Wishlist</h2>
        <link rel="stylesheet" href="wishlist.css">
        <?php if (empty($wishlistItems)): ?>
            <p class="empty-message">Your wishlist is empty. Start adding your favorite products!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Price (RM)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wishlistItems as $item): ?>
                    <tr>
                        <td>
                            <img src="image/<?php echo htmlspecialchars($item['ProductPicture'] ?? 'default-image.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                        <td><?php echo number_format($item['ProductPrice'], 2); ?></td>
                        <td>
                            <div class="action-buttons">
                                <!-- Remove from Wishlist Form -->
                                <form action="remove_wishlist.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                                    <button type="submit">Remove</button>
                                </form>
                                
                                <!-- Add to Cart Form -->
                                <form action="add_to_cart.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="productID" value="<?php echo $item['ProductID']; ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit">Add to Cart</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div> 
</body>
</html>

<?php
include 'footer.php';
?>