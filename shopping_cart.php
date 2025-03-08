<?php 
    session_start(); // Start session to access session variables
    include 'config.php'; 
    include 'header.php';
    
    // Retrieve CustID from session
    $custID = $_SESSION["user_id"] ?? null;
    
    if ($custID) {
        $query = "SELECT cart.*, product_color.Picture 
                  FROM cart 
                  JOIN product_color 
                  ON cart.ProductID = product_color.ProductID 
                  AND cart.Color = product_color.Color
                  WHERE cart.CustID = :custID";
    
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':custID', $custID, PDO::PARAM_INT); 
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = []; // Empty array if user is not logged in
    }    
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
            <?php 
                $totalPrice = 0;
                foreach ($result as $row) {
                    $totalPrice += $row['ProductPrice'] * $row['Quantity'];
                }
            ?>
            <table>
                <thead>
                    <tr>
                        <th>IMAGE</th>
                        <th>PRODUCT NAME</th>
                        <th>COLOR</th>
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
                        <td><img src="image/<?= htmlspecialchars($row['Picture']) ?>" alt="<?= htmlspecialchars($row['ProductName']) ?>"></td>
                        <td><?= htmlspecialchars($row['ProductName']) ?></td>
                        <td>
                            <form action="update_cart.php" method="POST" id="colorForm_<?= $row['CartID'] ?>">
                                <input type="hidden" name="CartID" value="<?= $row['CartID'] ?>">
                                <select name="Color" onchange="document.getElementById('colorForm_<?= $row['CartID'] ?>').submit()">
                                    <?php
                                    $colorQuery = "SELECT Color FROM product_color WHERE ProductID = :productID";
                                    $colorStmt = $conn->prepare($colorQuery);
                                    $colorStmt->bindParam(':productID', $row['ProductID'], PDO::PARAM_INT);
                                    $colorStmt->execute();
                                    $availableColors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);

                                    echo "<option value='{$row['Color']}' selected>{$row['Color']}</option>";

                                    foreach ($availableColors as $color) {
                                        if ($color != $row['Color']) {
                                            echo "<option value='$color'>$color</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </form>
                        </td>


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
                <p><strong>TOTAL</strong> <span class="grand-total">RM <?= number_format($totalPrice + 5.00, 2) ?></span></p>
            </div>
            <button class="checkout">PROCEED TO CHECK OUT</button>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>

<?php
include 'footer.php';
?>