<?php
session_start(); 
include 'config.php'; 
include 'header.php'; 

$custID = $_SESSION["user_id"] ?? null;

if ($custID) {
    $query = "SELECT cart.*, product.ProductName, product.ProductPicture, product.ProductID, product.ProductPrice
              FROM cart 
              JOIN product ON cart.ProductID = product.ProductID
              WHERE cart.CustID = :custID";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':custID', $custID, PDO::PARAM_INT); 
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pre-fetch stock information for all products in cart
    $productStocks = [];
    foreach ($result as $row) {
        if (!isset($productStocks[$row['ProductID']])) {
            $stmt = $conn->prepare("SELECT Size, Stock FROM product_size WHERE ProductID = :productID");
            $stmt->bindParam(':productID', $row['ProductID'], PDO::PARAM_INT);
            $stmt->execute();
            $productStocks[$row['ProductID']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} else {
    $result = [];
    $productStocks = [];
}

$totalPrice = 0;
foreach ($result as $row) {
    $currentProductStocks = $productStocks[$row['ProductID']] ?? [];
    $totalPrice += $row['ProductPrice'] * $row['Quantity'];
}
$grandTotal = $totalPrice;

$_SESSION['subtotal'] = $totalPrice; 

$_SESSION['cart_items'] = [];
foreach ($result as $row) {
    $_SESSION['cart_items'][] = [
        'ProductName' => $row['ProductName'],
        'Size' => $row['Size'],
        'Quantity' => $row['Quantity'],
        'Total' => $row['ProductPrice'] * $row['Quantity']
    ];
}

// Update cart count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT ProductID) AS total FROM cart WHERE CustID = ?");
$stmt->execute([$custID]);
$row = $stmt->fetch();
$cartCount = $row['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="shopping_cart.css">
</head>
<body>
    <div class="cart-container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (empty($result)): ?>
            <p class = "empty-message">No items in cart</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>IMAGE</th>
                        <th>PRODUCT NAME</th>
                        <th>SIZE</th>
                        <th>PRICE</th>
                        <th>QUANTITY</th>
                        <th>TOTAL</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): 
                        $currentProductStocks = $productStocks[$row['ProductID']] ?? [];
                        $sizeStockMap = [];
                        foreach ($currentProductStocks as $stock) {
                            $sizeValue = $stock['Size'] === null ? 'Standard Only' : $stock['Size'];
                            $sizeStockMap[$sizeValue] = $stock['Stock'];
                        }
                        
                        // Clean up the stored size value (remove stock info if present)
                        $currentSize = preg_replace('/\s*\d+ available.*/', '', $row['Size']);
                        $currentSize = trim($currentSize) === '' ? 'Standard Only' : trim($currentSize);
                    ?>
                    <tr>
                        <td><img src="image/<?= htmlspecialchars($row['ProductPicture']) ?>" alt="<?= htmlspecialchars($row['ProductName']) ?>"></td>
                        <td><?= htmlspecialchars($row['ProductName']) ?></td>
                        <td>
                            <?php if (empty($sizeStockMap)): ?>
                                <span>Standard Only</span>
                            <?php else: ?>
                                <form action="update_cart.php" method="POST" class="size-form">
                                    <input type="hidden" name="CartID" value="<?= $row['CartID'] ?>">
                                    <select name="Size" onchange="this.form.submit()">
                                        <?php foreach ($sizeStockMap as $size => $stock): 
                                            $disabled = $stock <= 0;
                                            $selected = $currentSize === $size;
                                        ?>
                                            <option value="<?= htmlspecialchars($size) ?>" <?= $selected ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                                <?= htmlspecialchars($size) ?> 
                                                (<?= $disabled ? 'Out of Stock' : "$stock available" ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="price">RM <?= number_format($row['ProductPrice'], 2) ?></td>
                        <td>
                            <form action="update_cart.php" method="POST" class="quantity-form">
                                <input type="hidden" name="CartID" value="<?= $row['CartID'] ?>">
                                <?php 
                                // Get current size's stock
                                $currentStock = $sizeStockMap[$currentSize] ?? 99; // Default to 99 if size not found
                                $maxQuantity = min($currentStock, 99);
                                ?>
                                <input type="number" name="Quantity" value="<?= htmlspecialchars($row['Quantity']) ?>" 
                                       min="1" max="<?= $maxQuantity ?>" 
                                       onchange="if(this.value >= 1 && this.value <= <?= $maxQuantity ?>) { this.form.submit(); } else { this.value = <?= $row['Quantity'] ?>; alert('Quantity must be between 1 and <?= $maxQuantity ?>'); }">
                            </form>
                        </td>
                        <td class="total">RM <?= number_format($row['ProductPrice'] * $row['Quantity'], 2) ?></td>
                        <td><a href="remove_item.php?CartID=<?= $row['CartID'] ?>" class="remove">&#10006;</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
            <div class="cart-buttons">
                <button class="continue" onclick="window.location.href='product.php'">Continue Shopping</button>
                <button class="update" onclick="window.location.href='clear_cart.php'">Clear Cart</button>
            </div>

            <div class="cart-summary">
                <div class="summary-details">
                    <p><strong>TOTAL</strong> <span class="grand-total">RM <?= number_format($grandTotal, 2) ?></span></p>
                </div>
                <button class="checkout" onclick="window.location.href='payment.php'">PROCEED TO CHECK OUT</button>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Add confirmation for remove item
        document.querySelectorAll('.remove').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to remove this item from your cart?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php include 'footer.php'; ?>