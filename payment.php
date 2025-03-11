<?php
session_start(); // Start session to access session variables

include 'config.php'; // Include the database configuration file

// Retrieve grand total from session
$deliveryCharge = 5.00; 
$subtotal = $_SESSION['subtotal'] ?? 0;
$grandTotalWithDelivery = $subtotal + $deliveryCharge;

// Retrieve cart items from session
$cartItems = $_SESSION['cart_items'] ?? [];

// Retrieve customer details from the database if logged in
$custID = $_SESSION['user_id'] ?? null;
$custName = '';
$custEmail = '';
$custAddress = '';

if ($custID) {
    $query = "SELECT CustName, CustEmail, CustAddress FROM customer WHERE CustID = :custID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':custID', $custID, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $custName = $customer['CustName'];
        $custEmail = $customer['CustEmail'];
        $custAddress = $customer['CustAddress'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cardName = $_POST['cname'] ?? '';
    $cardNum = $_POST['ccnum'] ?? '';
    $cardExpDate = $_POST['expdate'] ?? '';
    $cardCVV = $_POST['cvv'] ?? '';

    // Insert data into the orderpayment table
    $insertQuery = "INSERT INTO orderpayment (CustID, CustName, CustEmail, CustAddress, OrderDate, TotalPrice, CardName, CardNum, CardCVV)
                    VALUES (:custID, :custName, :custEmail, :custAddress, NOW(), :totalPrice, :cardName, :cardNum, :cardCVV)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
    $insertStmt->bindParam(':custName', $custName, PDO::PARAM_STR);
    $insertStmt->bindParam(':custEmail', $custEmail, PDO::PARAM_STR);
    $insertStmt->bindParam(':custAddress', $custAddress, PDO::PARAM_STR);
    $insertStmt->bindParam(':totalPrice', $grandTotalWithDelivery, PDO::PARAM_STR);
    $insertStmt->bindParam(':cardName', $cardName, PDO::PARAM_STR);
    $insertStmt->bindParam(':cardNum', $cardNum, PDO::PARAM_STR);
    $insertStmt->bindParam(':cardCVV', $cardCVV, PDO::PARAM_STR);

    // After inserting into orderpayment table
    if ($insertStmt->execute()) {
        // Get the last inserted OrderID
        $orderID = $conn->lastInsertId();

        // Insert each cart item into the orderdetails table
        foreach ($cartItems as $item) {
            $productName = $item['ProductName'];
            $color = $item['Color'];
            $size = $item['Size'];
            $quantity = $item['Quantity'];

            $detailQuery = "INSERT INTO orderdetails (OrderID, ProductName, Color, Size, Quantity)
                            VALUES (:orderID, :productName, :color, :size, :quantity)";
            $detailStmt = $conn->prepare($detailQuery);
            $detailStmt->bindParam(':orderID', $orderID, PDO::PARAM_INT);
            $detailStmt->bindParam(':productName', $productName, PDO::PARAM_STR);
            $detailStmt->bindParam(':color', $color, PDO::PARAM_STR);
            $detailStmt->bindParam(':size', $size, PDO::PARAM_STR);
            $detailStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

            if (!$detailStmt->execute()) {
                // Handle the error (e.g., display an error message)
                echo "<p>Error saving order details. Please try again.</p>";
                exit();
            }
        }

        // Clear the cart for the logged-in user
        $deleteCartQuery = "DELETE FROM cart WHERE CustID = :custID";
        $deleteCartStmt = $conn->prepare($deleteCartQuery);
        $deleteCartStmt->bindParam(':custID', $custID, PDO::PARAM_INT);

        if ($deleteCartStmt->execute()) {
            // Redirect to a success page or display a success message
            header('Location: index.php');
            exit();
        } else {
            // Handle the error (e.g., display an error message)
            echo "<p>Error clearing cart. Please try again.</p>";
        }
    } else {
        // Handle the error (e.g., display an error message)
        echo "<p>Error saving order. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Form</title>
    <link rel="stylesheet" href="payment.css">
</head>
<body>
    <div class="checkout-wrapper">
        <!-- Display Cart Items -->
        <div class="order-container">
            <div class="order-box">
                <h2>Order Summary</h2>
                <div class="cart-items">
                    <?php if (!empty($cartItems)): ?>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <p>
                                    <?= htmlspecialchars($item['ProductName']) ?> 
                                    (<?= htmlspecialchars($item['Color']) ?>, <?= htmlspecialchars($item['Size']) ?>) 
                                    x <?= htmlspecialchars($item['Quantity']) ?>
                                </p>
                                <span class="cart-price">RM <?= number_format($item['Total'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No items in cart.</p>
                    <?php endif; ?>
                </div>

                <!-- Subtotal & Total -->
                <div class="subtotal">
                    <span>Subtotal:</span>
                    <span>RM <?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="total">
                    <span>Total (Incl. Delivery):</span>
                    <span class="total-price">RM <?= number_format($grandTotalWithDelivery, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="container-bill">
            <form method="POST" action="payment.php">
                <div class="row">
                    <div class="col">
                        <h2>Billing Address</h2>
                        <label for="price"><b>Total Price</b></label>
                        <input type="text" id="price" value="RM <?= number_format($subtotal, 2) ?>" readonly>

                        <label for="fullname"><b>Full Name</b></label>
                        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($custName) ?>" placeholder="Enter full name" required>

                        <label for="email"><b>Email</b></label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($custEmail) ?>" placeholder="Enter email" required>

                        <label for="address"><b>Address</b></label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($custAddress) ?>" placeholder="Enter address" required>

                        <label>
                            <input type="checkbox"> Shipping address same as billing
                        </label>
                    </div>

                    <div class="col">
                        <h2>Payment</h2>
                        <p>Accepted Cards</p>
                        <div class="card-icons">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" alt="Visa">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/3/30/American_Express_logo.svg" alt="Amex">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard">
                        </div>

                        <label for="cname"><b>Name on Card</b></label>
                        <input type="text" id="cname" name="cname" placeholder="Enter name on card" required>

                        <label for="ccnum"><b>Card Number</b></label>
                        <input type="text" id="ccnum" name="ccnum" placeholder="Enter card number" required>

                        <label for="expdate"><b>Exp Date</b></label>
                        <input type="text" id="expdate" name="expdate" placeholder="MM/YY" required>

                        <label for="cvv"><b>CVV</b></label>
                        <input type="text" id="cvv" name="cvv" placeholder="Enter CVV" required>
                    </div>
                </div>

                <button type="submit" class="checkout-btn">Continue to checkout</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
include 'footer.php'; 
?>