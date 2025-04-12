<?php
ob_start();
session_start();
include 'config.php';
include 'header.php';

// Retrieve cart items from session
$cartItems = $_SESSION['cart_items'] ?? [];
$subtotal = $_SESSION['subtotal'] ?? 0;

// Initialize delivery fee and grand total
$deliveryCharge = 0.00;
$grandTotalWithDelivery = $subtotal;

// Retrieve customer details
$custID = $_SESSION['user_id'] ?? null;
$custName = '';
$custEmail = '';
$custStreetAddress = '';
$custCity = '';
$custPostcode = '';
$custState = '';

if ($custID) {
    $query = "SELECT CustName, CustEmail, StreetAddress, City, Postcode, State FROM customer WHERE CustID = :custID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':custID', $custID, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $custName = $customer['CustName'];
        $custEmail = $customer['CustEmail'];
        $custStreetAddress = $customer['StreetAddress'];
        $custCity = $customer['City'];
        $custPostcode = $customer['Postcode'];
        $custState = $customer['State'];
    }
}

// Set delivery fee based on state
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stateToUse = $_POST['state'] ?? $custState;
} else {
    $stateToUse = $custState;
}

// Set delivery fee based on state
if ($stateToUse === 'Melaka') {
    $deliveryCharge = 10.00;
} elseif ($stateToUse === 'Sabah' || $stateToUse === 'Sarawak') {
    $deliveryCharge = 50.00;
} else {
    $deliveryCharge = 15.00;
}

$grandTotalWithDelivery = $subtotal + $deliveryCharge;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formStreetAddress = $_POST['address'] ?? '';
    $formCity = $_POST['city'] ?? '';
    $formPostcode = $_POST['postcode'] ?? '';
    $formState = $_POST['state'] ?? '';

    $finalStreetAddress = $formStreetAddress ?: $custStreetAddress;
    $finalCity = $formCity ?: $custCity;
    $finalPostcode = $formPostcode ?: $custPostcode;
    $finalState = $formState ?: $custState;

    $cardName = $_POST['cname'] ?? '';
    $cardNum = $_POST['ccnum'] ?? '';
    $cardExpDate = $_POST['expdate'] ?? '';
    $cardCVV = $_POST['cvv'] ?? '';

    // Validate card details
    $errors = [];
    
    // Card Name validation (only letters and spaces)
    if (!preg_match('/^[a-zA-Z\s]+$/', $cardName)) {
        $errors[] = "Card name should contain only letters and spaces";
    }
    
    // Card Number validation (13-16 digits)
    if (!preg_match('/^\d{13,16}$/', $cardNum)) {
        $errors[] = "Card number must be 13-16 digits";
    }
    
    // Expiry Date validation (MM/YY format and not expired)
    if (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $cardExpDate)) {
        $errors[] = "Invalid expiry date format (MM/YY)";
    } else {
        $currentYear = date('y');
        $currentMonth = date('m');
        list($expMonth, $expYear) = explode('/', $cardExpDate);
        
        if ($expYear < $currentYear || ($expYear == $currentYear && $expMonth < $currentMonth)) {
            $errors[] = "Card has expired";
        }
    }
    
    // CVV validation (exactly 3 digits)
    if (!preg_match('/^\d{3}$/', $cardCVV)) {
        $errors[] = "CVV must be exactly 3 digits";
    }

    // If no validation errors, proceed with order processing
    if (empty($errors)) {
        $insertQuery = "INSERT INTO orderpayment 
            (CustID, CustName, CustEmail, StreetAddress, City, Postcode, State, OrderDate, TotalPrice, CardName, CardNum, CardCVV) 
            VALUES 
            (:custID, :custName, :custEmail, :streetAddress, :city, :postcode, :state, NOW(), :totalPrice, :cardName, :cardNum, :cardCVV)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
        $insertStmt->bindParam(':custName', $custName, PDO::PARAM_STR);
        $insertStmt->bindParam(':custEmail', $custEmail, PDO::PARAM_STR);
        $insertStmt->bindParam(':streetAddress', $finalStreetAddress, PDO::PARAM_STR);
        $insertStmt->bindParam(':city', $finalCity, PDO::PARAM_STR);
        $insertStmt->bindParam(':postcode', $finalPostcode, PDO::PARAM_STR);
        $insertStmt->bindParam(':state', $finalState, PDO::PARAM_STR);
        $insertStmt->bindParam(':totalPrice', $grandTotalWithDelivery, PDO::PARAM_STR);
        $insertStmt->bindParam(':cardName', $cardName, PDO::PARAM_STR);
        $insertStmt->bindParam(':cardNum', $cardNum, PDO::PARAM_STR);
        $insertStmt->bindParam(':cardCVV', $cardCVV, PDO::PARAM_STR);

        if ($insertStmt->execute()) {
            $orderID = $conn->lastInsertId();

            foreach ($cartItems as $item) {
                $productName = $item['ProductName'];
                $size = $item['Size'];
                $quantity = $item['Quantity'];

                $detailQuery = "INSERT INTO orderdetails (OrderID, ProductName, Size, Quantity)
                                VALUES (:orderID, :productName, :size, :quantity)";
                $detailStmt = $conn->prepare($detailQuery);
                $detailStmt->bindParam(':orderID', $orderID, PDO::PARAM_INT);
                $detailStmt->bindParam(':productName', $productName, PDO::PARAM_STR);
                $detailStmt->bindParam(':size', $size, PDO::PARAM_STR);
                $detailStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

                if (!$detailStmt->execute()) {
                    echo "<p>Error saving order details. Please try again.</p>";
                    exit();
                }
            }

            $deleteCartQuery = "DELETE FROM cart WHERE CustID = :custID";
            $deleteCartStmt = $conn->prepare($deleteCartQuery);
            $deleteCartStmt->bindParam(':custID', $custID, PDO::PARAM_INT);

            if ($deleteCartStmt->execute()) {
                header('Location: index.php');
                exit();
            } else {
                echo "<p>Error clearing cart. Please try again.</p>";
            }
        } else {
            echo "<p>Error saving order. Please try again.</p>";
        }
    } else {
        // Display validation errors
        echo '<div class="error-messages">';
        foreach ($errors as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
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
    <!-- Add jQuery and jQuery UI for datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <style>
        .error-messages {
            color: red;
            margin-bottom: 15px;
            border: 1px solid red;
            padding: 10px;
            border-radius: 5px;
        }
        .error-messages p {
            margin: 5px 0;
        }
        .ui-datepicker {
            background: white;
            border: 1px solid #ddd;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="checkout-wrapper">
        <div class="order-container">
            <div class="order-box">
                <h2>Order Summary</h2>
                <div class="cart-items">
                    <?php if (!empty($cartItems)): ?>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <p>
                                    <?= htmlspecialchars($item['ProductName']) ?> 
                                    (<?= htmlspecialchars($item['Size']) ?>) 
                                    x <?= htmlspecialchars($item['Quantity']) ?>
                                </p>
                                <span class="cart-price">RM <?= number_format($item['Total'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No items in cart.</p>
                    <?php endif; ?>
                </div>

                <div class="subtotal">
                    <span>Subtotal:</span>
                    <span id="subtotal">RM <?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="delivery">
                    <span>Delivery:</span>
                    <span id="delivery">RM <?= number_format($deliveryCharge, 2) ?></span>
                </div>
                <div class="total">
                    <span>Total (Incl. Delivery):</span>
                    <span id="total" class="total-price">RM <?= number_format($grandTotalWithDelivery, 2) ?></span>
                </div>

            </div>
        </div>

        <div class="container-bill">
            <form method="POST" action="payment.php" id="paymentForm">
                <div class="row">
                    <div class="col">
                        <h2>Billing Address</h2>
                        <label for="price"><b>Total Price</b></label>
                        <input type="text" id="price" value="RM <?= number_format($grandTotalWithDelivery, 2) ?>" readonly>

                        <label for="fullname"><b>Full Name</b></label>
                        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($custName) ?>" readonly>

                        <label for="email"><b>Email</b></label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($custEmail) ?>" readonly>

                        <label for="address"><b>Street Address</b></label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($custStreetAddress) ?>" required>

                        <label for="city"><b>City</b></label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($custCity) ?>" required>

                        <label for="postcode"><b>Postcode</b></label>
                        <input type="text" id="postcode" name="postcode" value="<?= htmlspecialchars($custPostcode) ?>" required>

                        <label for="state"><b>State</b></label>
                        <select id="state" name="state" required>
                            <?php
                            $states = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Pulau Pinang','Perak','Perlis','Selangor','Terengganu','Sabah','Sarawak'];
                            foreach ($states as $stateOption):
                                $selected = ($custState == $stateOption) ? 'selected' : '';
                                echo "<option value=\"$stateOption\" $selected>$stateOption</option>";
                            endforeach;
                            ?>
                        </select>
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
                        <input type="text" id="cname" name="cname" placeholder="Enter name on card" pattern="[a-zA-Z\s]+" title="Only letters and spaces allowed" required>

                        <label for="ccnum"><b>Card Number</b></label>
                        <input type="text" id="ccnum" name="ccnum" placeholder="Enter card number" pattern="\d{13,16}" title="13-16 digit card number" required>

                        <label for="expdate"><b>Exp Date</b></label>
                        <input type="text" id="expdate" name="expdate" placeholder="MM/YY" required>

                        <label for="cvv"><b>CVV</b></label>
                        <input type="text" id="cvv" name="cvv" placeholder="Enter CVV" pattern="\d{3}" title="3-digit CVV" required>
                    </div>
                </div>

                <button type="submit" class="checkout-btn">Continue to checkout</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('state').addEventListener('change', function () {
            const selectedState = this.value;
            let deliveryCharge = 15.00;

            if (selectedState === 'Melaka') {
                deliveryCharge = 10.00;
            } else if (selectedState === 'Sabah' || selectedState === 'Sarawak') {
                deliveryCharge = 50.00;
            }

            const subtotalText = document.getElementById('subtotal').textContent.replace('RM', '').trim();
            const subtotal = parseFloat(subtotalText);

            const total = subtotal + deliveryCharge;

            document.getElementById('delivery').textContent = 'RM ' + deliveryCharge.toFixed(2);
            document.getElementById('total').textContent = 'RM ' + total.toFixed(2);
            document.getElementById('price').value = 'RM ' + total.toFixed(2);
        });

        // Initialize datepicker for expiry date
        $(function() {
            $('#expdate').datepicker({
                dateFormat: 'mm/y',
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                minDate: 0,
                onClose: function(dateText, inst) {
                    var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
                    var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                    $(this).val($.datepicker.formatDate('mm/y', new Date(year, month, 1)));
                }
            }).focus(function() {
                $(".ui-datepicker-calendar").hide();
                $(".ui-datepicker-current").hide();
            });
        });

        // Client-side validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            // Card Name validation
            const cardName = document.getElementById('cname').value;
            if (!/^[a-zA-Z\s]+$/.test(cardName)) {
                alert('Card name should contain only letters and spaces');
                e.preventDefault();
                return;
            }
            
            // Card Number validation
            const cardNum = document.getElementById('ccnum').value;
            if (!/^\d{13,16}$/.test(cardNum)) {
                alert('Card number must be 13-16 digits');
                e.preventDefault();
                return;
            }
            
            // Expiry Date validation
            const expDate = document.getElementById('expdate').value;
            if (!/^(0[1-9]|1[0-2])\/?([0-9]{2})$/.test(expDate)) {
                alert('Invalid expiry date format (MM/YY)');
                e.preventDefault();
                return;
            } else {
                const currentYear = new Date().getFullYear() % 100;
                const currentMonth = new Date().getMonth() + 1;
                const [expMonth, expYear] = expDate.split('/').map(Number);
                
                if (expYear < currentYear || (expYear == currentYear && expMonth < currentMonth)) {
                    alert('Card has expired');
                    e.preventDefault();
                    return;
                }
            }
            
            // CVV validation
            const cvv = document.getElementById('cvv').value;
            if (!/^\d{3}$/.test(cvv)) {
                alert('CVV must be exactly 3 digits');
                e.preventDefault();
                return;
            }
        });
    </script>

</body>
</html>

<?php include 'footer.php'; ?>