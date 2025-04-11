<?php
session_start(); 
include 'config.php'; 
include 'header.php'; 

$custID = $_SESSION["user_id"] ?? null;

if ($custID) {
    $query = "SELECT cart.*, product.ProductName, product.ProductPicture 
              FROM cart 
              JOIN product ON cart.ProductID = product.ProductID
              WHERE cart.CustID = :custID";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':custID', $custID, PDO::PARAM_INT); 
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $result = [];
}

$totalPrice = 0;
foreach ($result as $row) {
    $totalPrice += $row['ProductPrice'] * $row['Quantity'];
}
$grandTotal = $totalPrice + 5.00;

$_SESSION['subtotal'] = $totalPrice; 
$_SESSION['delivery_fee'] = 5.00; 

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
    <style>
        .summary-details p {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 16px;
            letter-spacing: 0.5px;
            word-spacing: 2px; 
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <?php if (empty($result)): ?>
            <p style="text-align: center; font-size: 18px; font-weight: bold;">No items in Cart</p>
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
                    <?php foreach ($result as $row): ?>
                    <tr>
                        <td><img src="image/<?= htmlspecialchars($row['ProductPicture']) ?>" alt="<?= htmlspecialchars($row['ProductName']) ?>"></td>
                        <td><?= htmlspecialchars($row['ProductName']) ?></td>
                        <td>
                            <?php if ($row['Size'] == 'Standard Only'): ?>
                                Standard Only
                            <?php else: ?>
                                <form action="update_cart.php" method="POST">
                                    <input type="hidden" name="CartID" value="<?= $row['CartID'] ?>">
                                    <select name="Size" onchange="this.form.submit()">
                                        <option value="S" <?= $row['Size'] == 'S' ? 'selected' : '' ?>>S</option>
                                        <option value="M" <?= $row['Size'] == 'M' ? 'selected' : '' ?>>M</option>
                                        <option value="L" <?= $row['Size'] == 'L' ? 'selected' : '' ?>>L</option>
                                        <option value="XL" <?= $row['Size'] == 'XL' ? 'selected' : '' ?>>XL</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="price">RM <?= number_format($row['ProductPrice'], 2) ?></td>
                        <td>
                            <form action="update_cart.php" method="POST">
                                <input type="hidden" name="CartID" value="<?= $row['CartID'] ?>">
                                <input type="number" name="Quantity" value="<?= $row['Quantity'] ?>" min="1" onchange="this.form.submit()">
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
                    <p>SUBTOTAL<span class="total-price">RM <?= number_format($totalPrice, 2) ?></span></p>
                    <p>DELIVERY FEES<span class="delivery-fee">RM <?= number_format(5.00, 2) ?></span></p> 
                    <p><strong>TOTAL</strong> <span class="grand-total">RM <?= number_format($grandTotal, 2) ?></span></p>
                </div>
                <button class="checkout" onclick="window.location.href='payment.php'">PROCEED TO CHECK OUT</button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>
