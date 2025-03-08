<?php 
    include 'config.php'; 
    include 'header.php';
    session_start();
    
    $custID = 1; 

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
?>


<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }
        .cart-container {
            width: 80%;
            margin: 150px auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        img {
            width: 80px;
            height: auto;
        }
        .price, .total {
            color: red;
            font-weight: bold;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
        }
        .quantity-selector button {
            background: #ddd;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .quantity-selector input {
            width: 40px;
            text-align: center;
            border: 1px solid #ddd;
            margin: 0 5px;
        }
        .remove {
            cursor: pointer;
            font-size: 18px;
            color: red;
        }
        .cart-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .cart-buttons button {
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .continue {
            background: #eee;
        }
        .update {
            background: #ddd;
        }
        .cart-summary {
            background: #f0f0f0;
            padding: 20px;
            margin-top: 20px;
            text-align: right;
        }
        .summary-details p {
            display: flex;
            justify-content: space-between;
        }
        .checkout {
            background: black;
            color: white;
            padding: 15px;
            width: 100%;
            border: none;
            cursor: pointer;
            font-weight: bold;
            margin-top:20px;
        }
        select {
            padding: 5px;
            border: 1px solid #ddd;
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
                <p><strong>TOTAL</strong> <span class="total-price">RM <?= number_format($totalPrice, 2) ?></span></p>
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