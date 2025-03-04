<?php
    include 'header.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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
                <tr>
                    <td><img src="image/Goggles.png" alt="Goggles"></td>
                    <td>Goggles</td>
                    <td>
                        <select>
                            <option>Light Green</option>
                            <option>Pink</option>
                            <option>Dark Blue</option>
                        </select>
                    </td>
                    <td>
                        Standard Only
                    </td>
                    <td class="price">RM 19.00</td>
                    <td>
                        <div class="quantity-selector">
                            <button>-</button>
                            <input type="text" value="5">
                            <button>+</button>
                        </div>
                    </td>
                    <td class="total">RM 95.00</td>
                    <td class="remove">&#10006;</td>
                </tr>
                <tr>
                    <td><img src="image/boy-swimsuit-jammer.png" alt="Boy Swimsuit Jammer"></td>
                    <td>Boy Swimsuit Jammer</td>
                    <td>
                        <select>
                            <option>Blue</option>
                            <option>Black</option>
                        </select>
                    </td>
                    <td>
                        <select>
                            <option>S</option>
                            <option>M</option>
                            <option>L</option>
                            <option>XL</option>
                        </select>
                    </td>
                    <td class="price">RM 50.00</td>
                    <td>
                        <div class="quantity-selector">
                            <button>-</button>
                            <input type="text" value="1">
                            <button>+</button>
                        </div>
                    </td>
                    <td class="total">RM 50.00</td>
                    <td class="remove">&#10006;</td>
                </tr>
            </tbody>
        </table>
        
        <div class="cart-buttons">
            <button class="continue" onclick="window.location.href='product.php'">CONTINUE SHOPPING</button>
            <button class="update">UPDATE CART</button>
        </div>

        <div class="cart-summary">
            <div class="summary-details">
                <p><strong>TOTAL</strong> <span class="total-price">RM 145.00</span></p>
            </div>
            <button class="checkout">PROCEED TO CHECK OUT</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Select all quantity selectors
            document.querySelectorAll(".quantity-selector").forEach(selector => {
                let minusButton = selector.querySelector("button:first-child");
                let plusButton = selector.querySelector("button:last-child");
                let quantityInput = selector.querySelector("input");

                // Increase quantity
                plusButton.addEventListener("click", function () {
                    let currentValue = parseInt(quantityInput.value, 10);
                    quantityInput.value = currentValue + 1;
                    updateTotal(selector);
                });

                // Decrease quantity
                minusButton.addEventListener("click", function () {
                    let currentValue = parseInt(quantityInput.value, 10);
                    if (currentValue > 1) {
                        quantityInput.value = currentValue - 1;
                        updateTotal(selector);
                    }
                });
            });

            function updateTotal(selector) {
                let row = selector.closest("tr");
                let price = parseFloat(row.querySelector(".price").textContent.replace("RM ", ""));
                let quantity = parseInt(selector.querySelector("input").value, 10);
                let totalCell = row.querySelector(".total");

                totalCell.textContent = "RM " + (price * quantity).toFixed(2);
                updateCartTotal();
            }

            function updateCartTotal() {
                let total = 0;
                document.querySelectorAll(".total").forEach(totalCell => {
                    total += parseFloat(totalCell.textContent.replace("RM ", ""));
                });

                document.querySelector(".total-price").textContent = "RM " + total.toFixed(2);
            }
        });
    </script>

</body>
</html>

<?php
    include 'footer.php'; 
?>
