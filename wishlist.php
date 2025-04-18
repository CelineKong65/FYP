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

// Fetch wishlist items with size information
$query = "SELECT 
            w.WishID,
            w.ProductID,
            w.Size,
            p.ProductName, 
            p.ProductPrice, 
            p.ProductPicture,
            p.ProductDesc
          FROM wishlist w
          JOIN product p ON w.ProductID = p.ProductID
          WHERE w.CustID = :user_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For each wishlist item, fetch available sizes and stock
foreach ($wishlistItems as &$item) {
    $sizeQuery = "SELECT Size, Stock FROM product_size WHERE ProductID = :product_id";
    $sizeStmt = $conn->prepare($sizeQuery);
    $sizeStmt->bindParam(':product_id', $item['ProductID'], PDO::PARAM_INT);
    $sizeStmt->execute();
    $item['sizes'] = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($item); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        .wishlist-container {
            max-width: 1200px;
            margin: 150px auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 150px;
        }

        h2 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        img {
            max-width: 80px;
            height: auto;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 8px 12px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #004d99;
        }

        .stock-info {
            margin-top: 5px;
            font-weight: bold;
        }

        .stock-info.in-stock {
            color: green;
        }

        .stock-info.out-of-stock {
            color: red;
        }
    </style>
</head>
<body>
    <div class="wishlist-container">
        <h2>My Wishlist</h2>

        <?php if (empty($wishlistItems)): ?>
            <p class="empty-message">Your wishlist is empty. Start adding your favorite products!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Price (RM)</th>
                        <th>Size</th>
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
                        <td>
                            <?php echo htmlspecialchars($item['ProductName']); ?>
                            <div class="stock-info <?php 
                                $totalStock = 0;
                                foreach ($item['sizes'] as $size) {
                                    $totalStock += $size['Stock'];
                                }
                                echo $totalStock > 0 ? 'in-stock' : 'out-of-stock';
                            ?>">
                                <?php echo $totalStock > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            </div>
                        </td>
                        <td><?php echo number_format($item['ProductPrice'], 2); ?></td>
                        <td>
                            <form action="update_wishlist_size.php" method="POST" id="sizeForm-<?php echo $item['WishID']; ?>">
                                <input type="hidden" name="wish_id" value="<?php echo $item['WishID']; ?>">
                                <select name="new_size" onchange="document.getElementById('sizeForm-<?php echo $item['WishID']; ?>').submit()" style="padding:5px; margin-top:5px;">
                                    <?php foreach ($item['sizes'] as $sizeOption): 
                                        $selected = ($sizeOption['Size'] === $item['Size']) ? 'selected' : '';
                                        $isOutOfStock = $sizeOption['Stock'] <= 0;
                                        $disabled = $isOutOfStock ? 'disabled' : '';
                                        $sizeText = $sizeOption['Size'] ?? 'Standard Only';
                                    ?>
                                        <option value="<?= htmlspecialchars($sizeOption['Size'] ?? 'Standard Only') ?>" 
                                                <?= $selected ?> <?= $disabled ?>>
                                            <?= htmlspecialchars($sizeText) ?> (<?= $sizeOption['Stock'] ?>)
                                            <?= $isOutOfStock ? ' - Out of Stock' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <!-- Remove from Wishlist Form -->
                                <form action="remove_wishlist.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="wish_id" value="<?php echo $item['WishID']; ?>">
                                    <button type="submit">Remove</button>
                                </form>

                                <!-- Add to Cart Form -->
                                <form action="add_to_cart.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="productID" value="<?php echo $item['ProductID']; ?>">
                                    <input type="hidden" name="size" value="<?php echo htmlspecialchars($item['Size'] ?? 'Standard Only'); ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit" <?php echo ($totalStock <= 0) ? 'disabled' : ''; ?>>Add to Cart</button>
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

<?php include 'footer.php'; ?>
