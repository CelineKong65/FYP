<?php
include 'config.php'; 
include 'header.php'; 
// Get the logged-in user's ID from the session
$custID = $_SESSION["user_id"] ?? null;
// Check if the user is logged in, if not redirect to login page
if (!$custID) {
    $_SESSION['error'] = "Please login to view your cart.";
    header("Location: login.php");
    exit;
}

if ($custID) {
    // Get all items in the user's cart along with product details
    $query = "SELECT cart.*, product.ProductName, product.ProductPicture, product.ProductID, product.ProductPrice, product.ProductStatus
              FROM cart 
              JOIN product ON cart.ProductID = product.ProductID
              WHERE cart.CustID = :custID";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':custID', $custID, PDO::PARAM_INT); 
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stock availability for each product and size
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

// Calculate total cart value
$totalPrice = 0;
foreach ($result as $row) {
    $totalPrice += $row['ProductPrice'] * $row['Quantity'];
}
$grandTotal = $totalPrice;

// Store subtotal and cart items in session
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

// Get the total number of unique items in cart
$stmt = $conn->prepare("SELECT COUNT(DISTINCT ProductID) AS total FROM cart WHERE CustID = ?");
$stmt->execute([$custID]);
$row = $stmt->fetch();
$cartCount = $row['total'] ?? 0;

// Check for any inactive or out-of-stock items to disable checkout
$canCheckout = true;
foreach ($result as $row) {
    $currentProductStocks = $productStocks[$row['ProductID']] ?? [];
    $sizeStockMap = [];
    foreach ($currentProductStocks as $stock) {
        $sizeValue = $stock['Size'] === null ? 'Standard Only' : $stock['Size'];
        $sizeStockMap[$sizeValue] = $stock['Stock'];
    }
    $currentSize = preg_replace('/\s*\d+ available.*/', '', $row['Size']);
    $currentSize = trim($currentSize) === '' ? 'Standard Only' : trim($currentSize);
    $stockAvailable = $sizeStockMap[$currentSize] ?? 0;

    if ($stockAvailable <= 0 || strtolower($row['ProductStatus']) !== 'active') {
        $canCheckout = false;
        break;
    }
}
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
    <div class="cart-div">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <!-- If cart is empty -->
        <?php if (empty($result)): ?>
            <p class = "empty-message">No items in cart</p>
        <?php else: ?>
            <!-- Display cart table -->
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
                        // Get all available sizes and stock for current product
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
                                                <?= htmlspecialchars($size) ?> (<?= $disabled ? 'Out of Stock' : "$stock available" ?>)
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
                                $currentStock = $sizeStockMap[$currentSize] ?? 99;
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

            <!-- Cart buttons (continue shopping, clear cart) -->
            <div class="cart-buttons">
                <button class="continue" onclick="window.location.href='product.php'">Continue Shopping</button>
                <button class="update" onclick="window.location.href='clear_cart.php'">Clear Cart</button>
            </div>

            <!-- Summary and checkout button -->
            <div class="cart-summary">
                <div class="summary-details">
                    <p><strong>TOTAL</strong> <span class="grand-total">RM <?= number_format($grandTotal, 2) ?></span></p>
                </div>

                <?php if ($canCheckout): ?>
                    <button class="checkout" onclick="window.location.href='payment.php'">PROCEED TO CHECK OUT</button>
                <?php else: ?>
                    <button class="checkout" disabled style="background-color: grey; cursor: not-allowed;">CHECKOUT UNAVAILABLE</button>
                    <p class="error-message" style="color: red; margin-top: 10px;">One or more items are out of stock or inactive. Please update your cart before proceeding.</p>
                <?php endif; ?>
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