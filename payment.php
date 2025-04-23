<?php
ob_start();
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
$custPhoneNum = '';
$custEmail = '';
$custStreetAddress = '';
$custCity = '';
$custPostcode = '';
$custState = '';

if ($custID) {
    $query = "SELECT CustName, CustPhoneNum, CustEmail, StreetAddress, City, Postcode, State FROM customer WHERE CustID = :custID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':custID', $custID, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $custName = $customer['CustName'];
        $custPhoneNum = $customer['CustPhoneNum'];
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
    $formFullName = $_POST['fullname'] ?? '';
    $formContact = $_POST['contact'] ?? '';
    $formEmail = $_POST['email'] ?? '';
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

    // Validate all fields
    $errors = [];
    
    // Full Name Validation
    if (empty($formFullName)) {
        $errors[] = "Full name is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $formFullName)) {
        $errors[] = "Full name should contain only letters and spaces";
    }

    // Contact Validation
    if (empty($formContact)) {
        $errors[] = "Contact number is required";
    } elseif (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $formContact)) {
        $errors[] = "Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format";
    }

    // Email Validation
    if (empty($formEmail)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $formEmail)) {
        $errors[] = "Invalid email format (must end with .com)";
    }

    // Postcode validation (exactly 5 digits)
    if ($formPostcode && !preg_match('/^\d{5}$/', $formPostcode)) {
        $errors[] = "Postcode must be exactly 5 digits";
    }
    
    // Card Name validation (only letters and spaces)
    if (!preg_match('/^[a-zA-Z\s]+$/', $cardName)) {
        $errors[] = "Card name should contain only letters and spaces";
    }
    
    // Card Number validation (13-16 digits)
    if (!preg_match('/^\d{13,16}$/', $cardNum)) {
        $errors[] = "Card number must be 13-16 digits";
    }
    
    // Expiry Date validation (for month input YYYY-MM format)
    if (empty($cardExpDate)) {
        $errors[] = "Expiry date is required";
    } else {
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $currentMonth = $currentDate->format('m');
        
        $expDateParts = explode('-', $cardExpDate);
        if (count($expDateParts) !== 2) {
            $errors[] = "Invalid expiry date format";
        } else {
            $expYear = $expDateParts[0];
            $expMonth = $expDateParts[1];
            
            if ($expYear < $currentYear || ($expYear == $currentYear && $expMonth < $currentMonth)) {
                $errors[] = "Card has expired";
            }
        }
    }
    
    // CVV validation (exactly 3 digits)
    if (!preg_match('/^\d{3}$/', $cardCVV)) {
        $errors[] = "CVV must be exactly 3 digits";
    }

    // Check stock availability before processing payment
    foreach ($cartItems as $item) {
        // Use IS NULL comparison when size is "Standard Only"
        if ($item['Size'] === 'Standard Only') {
            $updateStockQuery = "UPDATE product_size ps
                                JOIN product p ON ps.ProductID = p.ProductID
                                SET ps.Stock = ps.Stock - :quantity
                                WHERE p.ProductName = :productName AND ps.Size IS NULL";
        } else {
            $updateStockQuery = "UPDATE product_size ps
                                JOIN product p ON ps.ProductID = p.ProductID
                                SET ps.Stock = ps.Stock - :quantity
                                WHERE p.ProductName = :productName AND ps.Size = :size";
        }
        
        $updateStockStmt = $conn->prepare($updateStockQuery);
        $updateStockStmt->bindParam(':quantity', $item['Quantity'], PDO::PARAM_INT);
        $updateStockStmt->bindParam(':productName', $item['ProductName'], PDO::PARAM_STR);
        
        // Only bind size parameter if not "Standard Only"
        if ($item['Size'] !== 'Standard Only') {
            $updateStockStmt->bindParam(':size', $item['Size'], PDO::PARAM_STR);
        }
        
        $updateStockStmt->execute();
        
        if ($updateStockStmt->rowCount() === 0) {
            throw new Exception("Failed to update stock for product: " . $item['ProductName']);
        }
    }

    // If no validation errors, proceed with order processing
    if (empty($errors)) {
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Insert payment information
            $insertQuery = "INSERT INTO orderpayment 
                (CustID, ReceiverName, ReceiverContact, ReceiverEmail, StreetAddress, City, Postcode, State, OrderDate, TotalPrice, DeliveryFee, CardName, CardNum, ExpDate, CardCVV) 
                VALUES 
                (:custID, :receiverName, :receiverContact, :receiverEmail, :streetAddress, :city, :postcode, :state, NOW(), :totalPrice, :deliveryFee, :cardName, :cardNum, :expDate, :cardCVV)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
            $insertStmt->bindParam(':receiverName', $formFullName, PDO::PARAM_STR);
            $insertStmt->bindParam(':receiverContact', $formContact, PDO::PARAM_STR);
            $insertStmt->bindParam(':receiverEmail', $formEmail, PDO::PARAM_STR);
            $insertStmt->bindParam(':streetAddress', $finalStreetAddress, PDO::PARAM_STR);
            $insertStmt->bindParam(':city', $finalCity, PDO::PARAM_STR);
            $insertStmt->bindParam(':postcode', $finalPostcode, PDO::PARAM_STR);
            $insertStmt->bindParam(':state', $finalState, PDO::PARAM_STR);
            $insertStmt->bindParam(':totalPrice', $grandTotalWithDelivery, PDO::PARAM_STR);
            $insertStmt->bindParam(':deliveryFee', $deliveryCharge, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardName', $cardName, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardNum', $cardNum, PDO::PARAM_STR);
            $insertStmt->bindParam(':expDate', $cardExpDate, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardCVV', $cardCVV, PDO::PARAM_STR);
            
            if ($insertStmt->execute()) {
                $orderID = $conn->lastInsertId();

                // Process each cart item
                foreach ($cartItems as $item) {
                    $productName = $item['ProductName'];
                    $size = $item['Size'];
                    $quantity = $item['Quantity'];
                    
                    // Get the product price from the database
                    $priceQuery = "SELECT ProductPrice FROM product WHERE ProductName = :productName";
                    $priceStmt = $conn->prepare($priceQuery);
                    $priceStmt->bindParam(':productName', $productName, PDO::PARAM_STR);
                    $priceStmt->execute();
                    $productPrice = $priceStmt->fetchColumn();

                    if ($productPrice === false) {
                        throw new Exception("Could not find price for product: " . $productName);
                    }

                    // Insert order details
                    $detailQuery = "INSERT INTO orderdetails (OrderID, ProductName, Size, Quantity, ProductPrice)
                                    VALUES (:orderID, :productName, :size, :quantity, :productPrice)";
                    $detailStmt = $conn->prepare($detailQuery);
                    $detailStmt->bindParam(':orderID', $orderID, PDO::PARAM_INT);
                    $detailStmt->bindParam(':productName', $productName, PDO::PARAM_STR);
                    $detailStmt->bindParam(':size', $size, PDO::PARAM_STR);
                    $detailStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                    $detailStmt->bindParam(':productPrice', $productPrice, PDO::PARAM_STR);
                    $detailStmt->execute();

                }

                // Clear the cart from database
                $deleteCartQuery = "DELETE FROM cart WHERE CustID = :custID";
                $deleteCartStmt = $conn->prepare($deleteCartQuery);
                $deleteCartStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
                $deleteCartStmt->execute();

                // Clear the session cart
                unset($_SESSION['cart_items']);
                unset($_SESSION['subtotal']);
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to success page
                header('Location: payment.php?payment=success');
                exit();
            } else {
                throw new Exception("Error saving order information");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $errors[] = "Error processing order: " . $e->getMessage();
        }
    }
    
    // Display validation errors if any
    if (!empty($errors)) {
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
                        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($custName) ?>" required>

                        <label for="contact"><b>Contact Number</b></label>
                        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($custPhoneNum) ?>" pattern="\d{3}-\d{3,4} \d{4}" title="Format: XXX-XXX XXXX or XXX-XXXX XXXX" placeholder="XXX-XXX XXXX" required>

                        <label for="email"><b>Email</b></label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($custEmail) ?>" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.com" title="Email must end with .com" required>

                        <label for="address"><b>Street Address</b></label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($custStreetAddress) ?>" required>

                        <label for="city"><b>City</b></label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($custCity) ?>" required>

                        <label for="postcode"><b>Postcode</b></label>
                        <input type="text" id="postcode" name="postcode" value="<?= htmlspecialchars($custPostcode) ?>" pattern="\d{5}" title="Postcode must be exactly 5 digits" required>

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
                        <input type="month" id="expdate" name="expdate" min="<?php echo date('Y-m'); ?>" required>

                        <label for="cvv"><b>CVV</b></label>
                        <input type="text" id="cvv" name="cvv" placeholder="Enter CVV" pattern="\d{3}" title="3-digit CVV" required>
                    </div>
                </div>

                <button type="submit" class="checkout-btn">Continue to checkout</button>
            </form>
        </div>
    </div>
    <script>
        // State change handler
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

        // Client-side validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            // Full name validation
            const fullName = document.getElementById('fullname').value;
            if (!/^[a-zA-Z\s]+$/.test(fullName)) {
                alert('Full name should contain only letters and spaces');
                e.preventDefault();
                return;
            }

            // Contact validation
            const contact = document.getElementById('contact').value;
            if (!/^\d{3}-\d{3,4} \d{4}$/.test(contact)) {
                alert('Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format');
                e.preventDefault();
                return;
            }

            // Email validation
            const email = document.getElementById('email').value;
            if (!/^[^@]+@[^@]+\.com$/.test(email)) {
                alert('Please enter a valid email address ending with .com');
                e.preventDefault();
                return;
            }

            // Postcode validation
            const postcode = document.getElementById('postcode').value;
            if (!/^\d{5}$/.test(postcode)) {
                alert('Postcode must be exactly 5 digits');
                e.preventDefault();
                return;
            }
            
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
            const expDateInput = document.getElementById('expdate');
            if (!expDateInput.value) {
                alert('Please select an expiry date');
                e.preventDefault();
                return;
            }

            const selectedDate = new Date(expDateInput.value);
            const currentDate = new Date();
                
            if (selectedDate.getFullYear() < currentDate.getFullYear() || 
                (selectedDate.getFullYear() === currentDate.getFullYear() && 
                selectedDate.getMonth() < currentDate.getMonth())) {
                alert('Card has expired');
                e.preventDefault();
                return;
            }
            
            // CVV validation
            const cvv = document.getElementById('cvv').value;
            if (!/^\d{3}$/.test(cvv)) {
                alert('CVV must be exactly 3 digits');
                e.preventDefault();
                return;
            }
        });

        // Success Popup Functions
        function showSuccessPopup() {
            // Create the popup HTML
            const popupHTML = `
                <div class="simple-popup-overlay">
                    <div class="simple-popup">
                        <div class="popup-icon">✓</div>
                        <h2>Payment Successful!</h2>
                        <p>Thank you for your purchase. Your order has been placed successfully.</p>
                        <button class="simple-popup-btn" onclick="closePopup()">OK</button>
                    </div>
                </div>
            `;
            
            // Add it to the body
            document.body.insertAdjacentHTML('beforeend', popupHTML);
        }

        function closePopup() {
            // Remove the popup
            const popup = document.querySelector('.simple-popup-overlay');
            if (popup) {
                popup.remove();
            }
            // Redirect to index.php
            window.location.href = 'index.php';
        }

        // Check for success parameter in URL and show popup
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('payment') === 'success') {
                showSuccessPopup();
            }
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>