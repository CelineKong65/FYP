<?php
ob_start();
include 'config.php'; 
include 'header.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

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
    
    // Get stock for the currently selected size
    $currentSizeQuery = "SELECT Stock FROM product_size WHERE ProductID = :product_id AND (Size = :size OR (Size IS NULL AND :size IS NULL))";
    $currentSizeStmt = $conn->prepare($currentSizeQuery);
    $currentSizeStmt->bindParam(':product_id', $item['ProductID'], PDO::PARAM_INT);
    $currentSizeStmt->bindParam(':size', $item['Size']);
    $currentSizeStmt->execute();
    $currentStock = $currentSizeStmt->fetchColumn();
    $item['current_stock'] = $currentStock !== false ? $currentStock : 0;
}
unset($item); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
    <link rel="stylesheet" href="wishlist.css">
    <script>
        function updateStockStatus(wishId, sizeSelect) {
            // Get the selected option
            const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
            
            // Extract the stock value from the option text (assuming format "Size (stock)")
            const stockMatch = selectedOption.text.match(/\((\d+)\)/);
            const stock = stockMatch ? parseInt(stockMatch[1]) : 0;
            
            // Find the stock info element for this row
            const stockInfo = document.querySelector(`#stock-info-${wishId}`);
            
            // Update the stock status
            if (stock > 0) {
                stockInfo.textContent = 'In Stock';
                stockInfo.className = 'stock-info in-stock';
            } else {
                stockInfo.textContent = 'Out of Stock';
                stockInfo.className = 'stock-info out-of-stock';
            }
            
            // Also update the Add to Cart button status
            const addToCartBtn = document.querySelector(`#add-to-cart-${wishId}`);
            if (addToCartBtn) {
                addToCartBtn.disabled = stock <= 0;
            }
        }
    </script>
</head>
<body>
    <div class="wishlist-div">
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
                            <div id="stock-info-<?php echo $item['WishID']; ?>" class="stock-info <?php echo ($item['current_stock'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo ($item['current_stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
                            </div>
                        </td>
                        <td><?php echo number_format($item['ProductPrice'], 2); ?></td>
                        <td>
                            <form action="update_wishlist_size.php" method="POST" id="sizeForm-<?php echo $item['WishID']; ?>">
                                <input type="hidden" name="wish_id" value="<?php echo $item['WishID']; ?>">
                                <select name="new_size" onchange="updateStockStatus(<?php echo $item['WishID']; ?>, this); document.getElementById('sizeForm-<?php echo $item['WishID']; ?>').submit()" style="padding:5px; margin-top:5px;">
                                    <?php foreach ($item['sizes'] as $sizeOption): 
                                        $selected = ($sizeOption['Size'] === $item['Size']) ? 'selected' : '';
                                        $isOutOfStock = $sizeOption['Stock'] <= 0;
                                        $disabled = $isOutOfStock ? 'disabled' : '';
                                        $sizeText = $sizeOption['Size'] ?? 'Standard Only';
                                    ?>
                                        <option value="<?= htmlspecialchars($sizeOption['Size'] ?? 'Standard Only') ?>" 
                                                <?= $selected ?> <?= $disabled ?>
                                                data-stock="<?= $sizeOption['Stock'] ?>">
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
                                    <button type="submit" id="add-to-cart-<?php echo $item['WishID']; ?>" <?php echo ($item['current_stock'] <= 0) ? 'disabled' : ''; ?>>Add to Cart</button>
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