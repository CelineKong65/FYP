<?php
ob_start();
include 'header.php';
include 'config.php';

// Check if this is a top-up request
if (isset($_GET['topup'])) {
    if ($_GET['topup'] == 'success') {
        $_SESSION['topup_success'] = true;
        header('Location: payment.php');
        exit();
    }
}

// Retrieve cart items from session
$cartItems = $_SESSION['cart_items'] ?? [];
$subtotal = $_SESSION['subtotal'] ?? 0;

// Initialize grand total
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

// Get current E-wallet balance (if any)
$eWalletBalance = 0;
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
    
    // Get E-wallet balance
    $balanceQuery = "SELECT EWalletBalance FROM customer WHERE CustID = :custID";
    $balanceStmt = $conn->prepare($balanceQuery);
    $balanceStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
    $balanceStmt->execute();
    $eWalletBalance = $balanceStmt->fetchColumn() ?? 0;
}

// Initialize form fields with empty values
$formFullName = $custName;
$formContact = $custPhoneNum;
$formEmail = $custEmail;
$formStreetAddress = $custStreetAddress;
$formCity = $custCity;
$formPostcode = $custPostcode;
$formState = $custState;
$paymentMethod = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : '';
$cardName = '';
$cardNum = '';
$cardExpDate = '';
$cardCVV = '';

// Initialize error array for all fields
$fieldErrors = [
    'fullname' => '',
    'contact' => '',
    'email' => '',
    'address' => '',
    'city' => '',
    'postcode' => '',
    'state' => '',
    'cname' => '',
    'ccnum' => '',
    'expdate' => '',
    'cvv' => '',
    'paymentMethod' => ''
];

// Check if customer has any available vouchers
$voucherQuery = "SELECT v.VoucherID, v.VoucherCode, v.DiscountValue, v.MinPurchase, v.ExpireDate 
                FROM voucher v
                JOIN voucher_usage vu ON v.VoucherID = vu.VoucherID
                WHERE vu.CustID = :custID AND vu.UsedAt IS NULL 
                AND v.VoucherStatus = 'Active'
                AND (v.ExpireDate IS NULL OR v.ExpireDate >= CURDATE())";
$voucherStmt = $conn->prepare($voucherQuery);
$voucherStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
$voucherStmt->execute();
$availableVouchers = $voucherStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle voucher application/removal
$appliedVoucher = null;
$voucherError = '';

if (isset($_POST['applyVoucher'])) {
    $voucherCode = trim($_POST['voucherCode'] ?? '');
    
    if (!empty($voucherCode)) {
        // Check voucher validity (with additional checks)
        $checkVoucherQuery = "SELECT v.VoucherID, v.VoucherCode, v.DiscountValue, v.MinPurchase, v.ExpireDate 
                            FROM voucher v
                            JOIN voucher_usage vu ON v.VoucherID = vu.VoucherID
                            WHERE v.VoucherCode = :voucherCode 
                            AND vu.CustID = :custID 
                            AND vu.UsedAt IS NULL
                            AND v.VoucherStatus = 'Active'
                            AND (v.ExpireDate IS NULL OR v.ExpireDate >= CURDATE())
                            FOR UPDATE"; // Lock the row to prevent changes during validation
        
        $checkStmt = $conn->prepare($checkVoucherQuery);
        $checkStmt->bindParam(':voucherCode', $voucherCode);
        $checkStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
        $checkStmt->execute();
        $appliedVoucher = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($appliedVoucher) {
            // Additional check for minimum purchase
            if ($grandTotalWithDelivery < $appliedVoucher['MinPurchase']) {
                $voucherError = "Minimum purchase of RM " . number_format($appliedVoucher['MinPurchase'], 2) . " required for this voucher";
                $appliedVoucher = null;
            } else {
                // Store with timestamp to detect changes
                $appliedVoucher['validated_at'] = time();
                $_SESSION['applied_voucher'] = $appliedVoucher;
            }
        } else {
            $voucherError = "Invalid voucher code, already used, or expired";
        }
    }
    
    // Preserve all form data when applying voucher
    $formFullName = $_POST['fullname'] ?? $formFullName;
    $formContact = $_POST['contact'] ?? $formContact;
    $formEmail = $_POST['email'] ?? $formEmail;
    $formStreetAddress = $_POST['address'] ?? $formStreetAddress;
    $formCity = $_POST['city'] ?? $formCity;
    $formPostcode = $_POST['postcode'] ?? $formPostcode;
    $formState = $_POST['state'] ?? $formState;
    $paymentMethod = $_POST['paymentMethod'] ?? $paymentMethod;
    $cardName = $_POST['cname'] ?? $cardName;
    $cardNum = $_POST['ccnum'] ?? $cardNum;
    $cardExpDate = $_POST['expdate'] ?? $cardExpDate;
    $cardCVV = $_POST['cvv'] ?? $cardCVV;
}

// Revalidate applied voucher on every page load
if (isset($_SESSION['applied_voucher'])) {
    $appliedVoucher = $_SESSION['applied_voucher'];
    
    $revalidateQuery = "SELECT v.VoucherID 
                       FROM voucher v
                       WHERE v.VoucherID = :voucherID
                       AND v.VoucherStatus = 'Active'
                       AND (v.ExpireDate IS NULL OR v.ExpireDate >= CURDATE())";
    
    $stmt = $conn->prepare($revalidateQuery);
    $stmt->bindParam(':voucherID', $appliedVoucher['VoucherID'], PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        // Voucher is no longer valid
        unset($_SESSION['applied_voucher']);
        $appliedVoucher = null;
        
        // Refresh the page to update the UI
        header("Refresh:0");
        exit();
    }
}

// Check if voucher should be removed
if (isset($_POST['removeVoucher'])) {
    $appliedVoucher = null;
    unset($_SESSION['applied_voucher']);
    // Recalculate total without voucher
    $grandTotalWithDelivery = $subtotal;
    
    // Preserve all form data when removing voucher
    $formFullName = $_POST['fullname'] ?? $formFullName;
    $formContact = $_POST['contact'] ?? $formContact;
    $formEmail = $_POST['email'] ?? $formEmail;
    $formStreetAddress = $_POST['address'] ?? $formStreetAddress;
    $formCity = $_POST['city'] ?? $formCity;
    $formPostcode = $_POST['postcode'] ?? $formPostcode;
    $formState = $_POST['state'] ?? $formState;
    $paymentMethod = $_POST['paymentMethod'] ?? $paymentMethod;
    $cardName = $_POST['cname'] ?? $cardName;
    $cardNum = $_POST['ccnum'] ?? $cardNum;
    $cardExpDate = $_POST['expdate'] ?? $cardExpDate;
    $cardCVV = $_POST['cvv'] ?? $cardCVV;
}

// If voucher is applied, calculate discounted total
if ($appliedVoucher || isset($_SESSION['applied_voucher'])) {
    $appliedVoucher = $appliedVoucher ?? $_SESSION['applied_voucher'];
    $discountAmount = $appliedVoucher['DiscountValue'];
    $grandTotalWithDelivery -= $discountAmount;
}

// Handle form submission (for actual payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['applyVoucher']) && !isset($_POST['removeVoucher'])) {
    $appliedVoucher = $_SESSION['applied_voucher'] ?? null;
    $formFullName = $_POST['fullname'] ?? '';
    $formContact = $_POST['contact'] ?? '';
    $formEmail = $_POST['email'] ?? '';
    $formStreetAddress = $_POST['address'] ?? '';
    $formCity = $_POST['city'] ?? '';
    $formPostcode = $_POST['postcode'] ?? '';
    $formState = $_POST['state'] ?? '';
    $paymentMethod = $_POST['paymentMethod'] ?? '';

    $finalStreetAddress = $formStreetAddress ?: $custStreetAddress;
    $finalCity = $formCity ?: $custCity;
    $finalPostcode = $formPostcode ?: $custPostcode;
    $finalState = $formState ?: $custState;
    
    // Only get card details if payment method is credit card or debit card
    if ($paymentMethod === 'Credit Card' || $paymentMethod === 'Debit Card') {
        $cardName = $_POST['cname'] ?? '';
        $cardNum = $_POST['ccnum'] ?? '';
        $cardExpDate = $_POST['expdate'] ?? '';
        $cardCVV = $_POST['cvv'] ?? '';
    }

    // Validate all fields
    $errors = [];
    
    // Payment Method Validation
    if (empty($paymentMethod)) {
        $errors[] = "Payment method is required";
        $fieldErrors['paymentMethod'] = "Payment method is required";
    } elseif ($paymentMethod === 'E-wallet' && $eWalletBalance < $grandTotalWithDelivery) {
        // Redirect to top-up page if balance is insufficient
        $_SESSION['required_amount'] = $grandTotalWithDelivery;
        header('Location: topup.php');
        exit();
    }
    
    // Full Name Validation
    if (empty($formFullName)) {
        $errors[] = "Full name is required";
        $fieldErrors['fullname'] = "Full name is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $formFullName)) {
        $errors[] = "Full name should contain only letters and spaces";
        $fieldErrors['fullname'] = "Should contain only letters and spaces";
    }

    // Contact Validation
    if (empty($formContact)) {
        $errors[] = "Contact number is required";
        $fieldErrors['contact'] = "Contact number is required";
    } elseif (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $formContact)) {
        $errors[] = "Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format";
        $fieldErrors['contact'] = "Format: XXX-XXX XXXX or XXX-XXXX XXXX";
    }

    // Email Validation
    if (empty($formEmail)) {
        $errors[] = "Email is required";
        $fieldErrors['email'] = "Email is required";
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $formEmail)) {
        $errors[] = "Invalid email format (must end with .com)";
        $fieldErrors['email'] = "Must end with .com";
    }

    // Address Validation
    if (empty($formStreetAddress)) {
        $errors[] = "Street address is required";
        $fieldErrors['address'] = "Street address is required";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-,.]+$/', $formStreetAddress)) {
        $errors[] = "Street address should contain only letters, numbers, spaces, hyphens, commas and periods";
        $fieldErrors['address'] = "Only letters, numbers, spaces, hyphens, commas and periods allowed";
    }

    // City Validation
    if (empty($formCity)) {
        $errors[] = "City is required";
        $fieldErrors['city'] = "City is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $formCity)) {
        $errors[] = "City should contain only letters and spaces";
        $fieldErrors['city'] = "Should contain only letters and spaces";
    }

    // Postcode validation
    if (empty($formPostcode)) {
        $errors[] = "Postcode is required";
        $fieldErrors['postcode'] = "Postcode is required";
    } elseif (!preg_match('/^\d{5}$/', $formPostcode)) {
        $errors[] = "Postcode must be exactly 5 digits";
        $fieldErrors['postcode'] = "Must be exactly 5 digits";
    }

    // State Validation
    if (empty($formState)) {
        $errors[] = "State is required";
        $fieldErrors['state'] = "State is required";
    }
    
    // Credit Card validations (only if payment method is credit card)
    if ($paymentMethod === 'Credit Card') {
        // Card Name validation
        if (empty($cardName)) {
            $errors[] = "Card name is required";
            $fieldErrors['cname'] = "Card name is required";
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $cardName)) {
            $errors[] = "Card name should contain only letters and spaces";
            $fieldErrors['cname'] = "Should contain only letters and spaces";
        }
        
        // Card Number validation
        if (empty($cardNum)) {
            $errors[] = "Card number is required";
            $fieldErrors['ccnum'] = "Card number is required";
        } elseif (!preg_match('/^\d{13,16}$/', $cardNum)) {
            $errors[] = "Card number must be 13-16 digits";
            $fieldErrors['ccnum'] = "Must be 13-16 digits";
        }
        
        // Expiry Date validation
        if (empty($cardExpDate)) {
            $errors[] = "Expiry date is required";
            $fieldErrors['expdate'] = "Expiry date is required";
        } else {
            $currentDate = new DateTime();
            $currentYear = $currentDate->format('Y');
            $currentMonth = $currentDate->format('m');
            
            $expDateParts = explode('-', $cardExpDate);
            if (count($expDateParts) !== 2) {
                $errors[] = "Invalid expiry date format";
                $fieldErrors['expdate'] = "Invalid format";
            } else {
                $expYear = $expDateParts[0];
                $expMonth = $expDateParts[1];
                
                if ($expYear < $currentYear || ($expYear == $currentYear && $expMonth < $currentMonth)) {
                    $errors[] = "Card has expired";
                    $fieldErrors['expdate'] = "Card has expired";
                }
            }
        }
        
        // CVV validation
        if (empty($cardCVV)) {
            $errors[] = "CVV is required";
            $fieldErrors['cvv'] = "CVV is required";
        } elseif (!preg_match('/^\d{3}$/', $cardCVV)) {
            $errors[] = "CVV must be exactly 3 digits";
            $fieldErrors['cvv'] = "Must be exactly 3 digits";
        }
    }

    // Check stock availability before processing payment
    if (empty($errors)) {
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
    }

    // If no validation errors, proceed with order processing
    if (empty($errors)) {
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Insert payment information
            $insertQuery = "INSERT INTO orderpayment 
                (CustID, ReceiverName, ReceiverContact, ReceiverEmail, StreetAddress, City, Postcode, State, 
                OrderDate, TotalPrice, PaymentMethod, CardName, CardNum, ExpDate, CardCVV, VoucherID) 
                VALUES 
                (:custID, :receiverName, :receiverContact, :receiverEmail, :streetAddress, :city, :postcode, :state, 
                NOW(), :totalPrice, :paymentMethod, :cardName, :cardNum, :expDate, :cardCVV, :voucherID)";

                
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
            $insertStmt->bindParam(':paymentMethod', $paymentMethod, PDO::PARAM_STR);
            
            $voucherIDToBind = $appliedVoucher['VoucherID'] ?? null;
            $insertStmt->bindParam(':voucherID', $voucherIDToBind, PDO::PARAM_INT);

            // For E-wallet, store the remaining balance after payment
            $remainingBalance = ($paymentMethod === 'E-wallet') ? ($eWalletBalance - $grandTotalWithDelivery) : NULL;
           
            // Only bind card details if payment method is credit card or debit card
            $cardNameToBind = ($paymentMethod === 'Credit Card' || $paymentMethod === 'Debit Card') ? $cardName : NULL;
            $cardNumToBind = ($paymentMethod === 'Credit Card' || $paymentMethod === 'Debit Card') ? $cardNum : NULL;
            $expDateToBind = ($paymentMethod === 'Credit Card' || $paymentMethod === 'Debit Card') ? $cardExpDate : NULL;
            $cardCVVToBind = ($paymentMethod === 'Credit Card' || $paymentMethod === 'Debit Card') ? $cardCVV : NULL;
            
            $insertStmt->bindParam(':cardName', $cardNameToBind, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardNum', $cardNumToBind, PDO::PARAM_STR);
            $insertStmt->bindParam(':expDate', $expDateToBind, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardCVV', $cardCVVToBind, PDO::PARAM_STR);


            // If voucher was applied, mark it as used
            if ($appliedVoucher) {
                $markVoucherUsedQuery = "UPDATE voucher_usage 
                                        SET UsedAt = NOW() 
                                        WHERE VoucherID = :voucherID 
                                        AND CustID = :custID 
                                        AND UsedAt IS NULL";
                $markVoucherStmt = $conn->prepare($markVoucherUsedQuery);
                $markVoucherStmt->bindParam(':voucherID', $appliedVoucher['VoucherID'], PDO::PARAM_INT);
                $markVoucherStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
                $markVoucherStmt->execute();
            }

            
            if ($insertStmt->execute()) {
                $orderID = $conn->lastInsertId();

                // Process each cart item
                foreach ($cartItems as $item) {
                    $productName = $item['ProductName'];
                    $size = $item['Size'];
                    $quantity = $item['Quantity'];
                    
                    // Get the product ID and price from the database
                    $productQuery = "SELECT ProductID, ProductPrice FROM product WHERE ProductName = :productName";
                    $productStmt = $conn->prepare($productQuery);
                    $productStmt->bindParam(':productName', $productName, PDO::PARAM_STR);
                    $productStmt->execute();
                    $productData = $productStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$productData) {
                        throw new Exception("Could not find product: " . $productName);
                    }

                    $productID = $productData['ProductID'];
                    $productPrice = $productData['ProductPrice'];

                    // Insert order details with ProductID
                    $detailQuery = "INSERT INTO orderdetails (OrderID, ProductID, ProductName, Size, Quantity, ProductPrice)
                                    VALUES (:orderID, :productID, :productName, :size, :quantity, :productPrice)";
                    $detailStmt = $conn->prepare($detailQuery);
                    $detailStmt->bindParam(':orderID', $orderID, PDO::PARAM_INT);
                    $detailStmt->bindParam(':productID', $productID, PDO::PARAM_INT);
                    $detailStmt->bindParam(':productName', $productName, PDO::PARAM_STR);
                    $detailStmt->bindParam(':size', $size, PDO::PARAM_STR);
                    $detailStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                    $detailStmt->bindParam(':productPrice', $productPrice, PDO::PARAM_STR);
                    $detailStmt->execute();
                }

                // Update E-wallet balance if payment method is E-wallet
                if ($paymentMethod === 'E-wallet') {
                    $remainingBalance = $eWalletBalance - $grandTotalWithDelivery;
                    $updateBalanceQuery = "UPDATE customer SET EWalletBalance = :newBalance WHERE CustID = :custID";
                    $updateBalanceStmt = $conn->prepare($updateBalanceQuery);
                    $updateBalanceStmt->bindParam(':newBalance', $remainingBalance, PDO::PARAM_STR);
                    $updateBalanceStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
                    $updateBalanceStmt->execute();
                }

                // Clear the cart from database
                $deleteCartQuery = "DELETE FROM cart WHERE CustID = :custID";
                $deleteCartStmt = $conn->prepare($deleteCartQuery);
                $deleteCartStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
                $deleteCartStmt->execute();

                // Clear the session cart
                unset($_SESSION['cart_items']);
                unset($_SESSION['subtotal']);
                unset($_SESSION['applied_voucher']);
                
                // Store payment method and balance in session
                $_SESSION['payment_method'] = $paymentMethod;
                if ($paymentMethod === 'E-wallet') {
                    $_SESSION['remaining_balance'] = $remainingBalance;
                }
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to success page with appropriate parameters
                if ($paymentMethod === 'E-wallet') {
                    header('Location: payment.php?payment=success&ewallet=true&balance=' . $remainingBalance);
                } else {
                    header('Location: payment.php?payment=success');
                }
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

                <div class="voucher-section">
                    <h3>Apply Voucher</h3>                    
                    <?php if (!empty($availableVouchers)): ?>
                        <div class="available-vouchers">
                            <p>Your available vouchers:</p>
                            <form method="POST" action="payment.php" class="voucher-form">
                                <select name="voucherCode" class="voucher-select">
                                    <option value="">-- Select a voucher --</option>
                                    <?php foreach ($availableVouchers as $voucher): ?>
                                        <option value="<?= htmlspecialchars($voucher['VoucherCode']) ?>"
                                            <?= (isset($_POST['voucherCode']) && $_POST['voucherCode'] == $voucher['VoucherCode']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($voucher['VoucherCode']) ?> - 
                                            RM <?= number_format($voucher['DiscountValue'], 2) ?> off
                                            <?php if ($voucher['MinPurchase'] > 0): ?>
                                                (Min. purchase RM <?= number_format($voucher['MinPurchase'], 2) ?>)
                                            <?php endif; ?>
                                            <?php if ($voucher['ExpireDate']): ?>
                                                - Expires on <?= date('d M Y', strtotime($voucher['ExpireDate'])) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="applyVoucher" class="apply-voucher-btn">Apply</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>You don't have any available vouchers.</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($voucherError)): ?>
                        <div class="voucher-error"><?= htmlspecialchars($voucherError) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($appliedVoucher): ?>
                        <div class="voucher-success">
                            Voucher applied! Discount: RM <?= number_format($appliedVoucher['DiscountValue'], 2) ?>
                            <form method="POST" action="payment.php" style="display: inline;">
                                <input type="hidden" name="removeVoucher" value="1">
                                <button type="submit" class="remove-voucher-btn">Remove</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="total">
                    <span>Subtotal:</span>
                    <span class="total-price">RM <?= number_format($subtotal, 2) ?></span>
                </div>

                <?php if ($appliedVoucher): ?>
                    <div class="total">
                        <span>Voucher Discount (<?= htmlspecialchars($appliedVoucher['VoucherCode']) ?>):</span>
                        <span class="total-price">- RM <?= number_format($appliedVoucher['DiscountValue'], 2) ?></span>
                    </div>
                <?php endif; ?>

                <div class="total">
                    <span>Total (Incl. Delivery):</span>
                    <span id="total" class="total-price">RM <?= number_format($grandTotalWithDelivery, 2) ?></span>
                </div>
                
                <?php if ($paymentMethod === 'E-wallet' || (isset($_SESSION['topup_success']) && $_SESSION['topup_success'])): ?>
                    <div class="e-wallet-info">
                        <p>Your E-wallet balance: RM <?= number_format($eWalletBalance, 2) ?></p>
                        <?php if ($eWalletBalance < $grandTotalWithDelivery): ?>
                            <p class="text-danger">Insufficient balance. Please top up your E-wallet.</p>
                        <?php else: ?>
                            <p>Remaining balance after payment: RM <?= number_format($eWalletBalance - $grandTotalWithDelivery, 2) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php unset($_SESSION['topup_success']); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="container-bill">
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="payment.php" id="paymentForm" novalidate>
                <div class="row">
                    <div class="col">
                        <h2>Billing Address</h2>
                        <label for="price"><b>Total Price</b></label>
                        <input type="text" id="price" value="RM <?= number_format($grandTotalWithDelivery, 2) ?>" readonly>

                        <label for="fullname"><b>Full Name</b></label>
                        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($formFullName) ?>" required>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['fullname']) ?></span>

                        <label for="contact"><b>Contact Number</b></label>
                        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($formContact) ?>" required>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['contact']) ?></span>

                        <label for="email"><b>Email</b></label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($formEmail) ?>" required>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['email']) ?></span>

                        <label for="address"><b>Street Address</b></label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($formStreetAddress) ?>" required>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['address']) ?></span>

                        <label for="city"><b>City</b></label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($formCity) ?>" required>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['city']) ?></span>

                        <label for="postcode"><b>Postcode</b></label>
                        <input type="text" id="postcode" name="postcode" value="<?= htmlspecialchars($formPostcode) ?>" required>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['postcode']) ?></span>

                        <label for="state"><b>State</b></label>
                        <select id="state" name="state" required>
                            <?php
                            $states = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Pulau Pinang','Perak','Perlis','Selangor','Terengganu','Sabah','Sarawak'];
                            foreach ($states as $stateOption):
                                $selected = ($formState == $stateOption) ? 'selected' : '';
                                echo "<option value=\"$stateOption\" $selected>$stateOption</option>";
                            endforeach;
                            ?>
                        </select>
                        <span class="error-text"><?= htmlspecialchars($fieldErrors['state']) ?></span>
                    </div>

                    <div class="col">
                        <h2>Payment</h2>
                        
                        <div class="payment-method">
                            <label>
                                <input type="radio" name="paymentMethod" value="Credit Card" <?= $paymentMethod === 'Credit Card' ? 'checked' : '' ?> required>
                                Credit Card
                            </label>
                            <label>
                                <input type="radio" name="paymentMethod" value="Debit Card" <?= $paymentMethod === 'Debit Card' ? 'checked' : '' ?>>
                                Debit Card
                            </label>
                            <label>
                                <input type="radio" name="paymentMethod" value="E-wallet" <?= $paymentMethod === 'E-wallet' ? 'checked' : '' ?>>
                                E-wallet
                            </label>
                            <span class="error-text"><?= htmlspecialchars($fieldErrors['paymentMethod']) ?></span>
                        </div>
                        
                        <!-- Credit Card Section -->
                        <div id="creditCardSection" class="payment-section <?= $paymentMethod === 'Credit Card' ? 'active' : '' ?>">
                            <p>Accepted Cards</p>
                            <div class="card-icons">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" alt="Visa">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/3/30/American_Express_logo.svg" alt="Amex">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard">
                            </div>

                            <label for="cname"><b>Name on Card</b></label>
                            <input type="text" id="cname" name="cname" placeholder="Enter name on card" value="<?= htmlspecialchars($cardName) ?>" <?= $paymentMethod === 'Credit Card' ? 'required' : '' ?>>
                            <span class="error-text"><?= htmlspecialchars($fieldErrors['cname']) ?></span>

                            <label for="ccnum"><b>Card Number</b></label>
                            <input type="text" id="ccnum" name="ccnum" placeholder="Enter card number" value="<?= htmlspecialchars($cardNum) ?>" <?= $paymentMethod === 'Credit Card' ? 'required' : '' ?>>
                            <span class="error-text"><?= htmlspecialchars($fieldErrors['ccnum']) ?></span>

                            <label for="expdate"><b>Exp Date</b></label>
                            <input type="month" id="expdate" name="expdate" value="<?= htmlspecialchars($cardExpDate) ?>" min="<?php echo date('Y-m'); ?>" <?= $paymentMethod === 'Credit Card' ? 'required' : '' ?>>
                            <span class="error-text"><?= htmlspecialchars($fieldErrors['expdate']) ?></span>

                            <label for="cvv"><b>CVV</b></label>
                            <input type="text" id="cvv" name="cvv" placeholder="Enter CVV" value="<?= htmlspecialchars($cardCVV) ?>" <?= $paymentMethod === 'Credit Card' ? 'required' : '' ?>>
                            <span class="error-text"><?= htmlspecialchars($fieldErrors['cvv']) ?></span>
                        </div>
                        
                        <!-- E-wallet Section -->
                        <div id="eWalletSection" class="payment-section <?= $paymentMethod === 'E-wallet' ? 'active' : '' ?>">
                            <div class="e-wallet-info">
                                <p>Current E-wallet balance: RM <?= number_format($eWalletBalance, 2) ?></p>
                                <?php if ($paymentMethod === 'E-wallet'): ?>
                                    <?php if ($eWalletBalance < $grandTotalWithDelivery): ?>
                                        <p class="text-danger">Insufficient balance. You need RM <?= number_format($grandTotalWithDelivery - $eWalletBalance, 2) ?> more.</p>
                                    <?php else: ?>
                                        <p>Remaining balance after payment: RM <?= number_format($eWalletBalance - $grandTotalWithDelivery, 2) ?></p>
                                    <?php endif; ?>
                                    <a href="topup.php?amount=<?= ceil(($grandTotalWithDelivery - $eWalletBalance)/50)*50 ?>" class="topup-btn">Top Up Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="checkout-btn">Place Order</button>
            </form>
        </div>
    </div>
    <script>
        // Check for voucher validity periodically
function checkVoucherValidity() {
    const voucherApplied = <?= isset($_SESSION['applied_voucher']) ? 'true' : 'false' ?>;
    
    if (voucherApplied) {
        // Check every 30 seconds
        setTimeout(() => {
            fetch(window.location.href, {
                headers: {
                    'X-Voucher-Check': 'true'
                }
            })
            .then(response => response.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const voucherStillApplied = doc.querySelector('.voucher-success') !== null;
                
                if (!voucherStillApplied) {
                    window.location.reload();
                }
            });
        }, 30000);
    }
}

// Run on page load
window.addEventListener('load', function() {
    checkVoucherValidity();
    
    // Also check when coming back to the page
    window.addEventListener('focus', checkVoucherValidity);
});
    // Function to validate required fields
function validateRequiredField(fieldId, fieldName) {
    const field = document.getElementById(fieldId);
    const errorElement = document.querySelector(`#${fieldId} + .error-text`);
    
    if (!field.value.trim()) {
        errorElement.textContent = `${fieldName} is required`;
        return false;
    }
    
    errorElement.textContent = '';
    return true;
}

// Field-specific validation functions
function validateFullName() {
    const isValid = validateRequiredField('fullname', 'Full name');
    if (!isValid) return false;
    
    return validateField('fullname', /^[a-zA-Z\s]+$/, 'Should contain only letters and spaces');
}

function validateContact() {
    const isValid = validateRequiredField('contact', 'Contact number');
    if (!isValid) return false;
    
    return validateField('contact', /^\d{3}-\d{3,4} \d{4}$/, 'Format: XXX-XXX XXXX or XXX-XXXX XXXX');
}

function validateEmail() {
    const isValid = validateRequiredField('email', 'Email');
    if (!isValid) return false;
    
    return validateField('email', /^[^@]+@[^@]+\.com$/, 'Must end with .com');
}

function validateAddress() {
    const isValid = validateRequiredField('address', 'Street address');
    if (!isValid) return false;
    
    return validateField('address', /^[a-zA-Z0-9\s\-,.]+$/, 'Only letters, numbers, spaces, hyphens, commas and periods allowed');
}

function validateCity() {
    const isValid = validateRequiredField('city', 'City');
    if (!isValid) return false;
    
    return validateField('city', /^[a-zA-Z\s]+$/, 'Should contain only letters and spaces');
}

function validatePostcode() {
    const isValid = validateRequiredField('postcode', 'Postcode');
    if (!isValid) return false;
    
    return validateField('postcode', /^\d{5}$/, 'Must be exactly 5 digits');
}

function validateCardName() {
    // Only validate if credit card or debit card is selected
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    if (!paymentMethod || (paymentMethod.value !== 'Credit Card' && paymentMethod.value !== 'Debit Card')) {
        document.querySelector('#cname + .error-text').textContent = '';
        return true;
    }
    
    const isValid = validateRequiredField('cname', 'Card name');
    if (!isValid) return false;
    
    return validateField('cname', /^[a-zA-Z\s]+$/, 'Should contain only letters and spaces');
}

function validateCardNumber() {
    // Only validate if credit card or debit card is selected
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    if (!paymentMethod || (paymentMethod.value !== 'Credit Card' && paymentMethod.value !== 'Debit Card')) {
        document.querySelector('#ccnum + .error-text').textContent = '';
        return true;
    }
    
    const isValid = validateRequiredField('ccnum', 'Card number');
    if (!isValid) return false;
    
    return validateField('ccnum', /^\d{13,16}$/, 'Must be 13-16 digits');
}

function validateCVV() {
    // Only validate if credit card or debit card is selected
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    if (!paymentMethod || (paymentMethod.value !== 'Credit Card' && paymentMethod.value !== 'Debit Card')) {
        document.querySelector('#cvv + .error-text').textContent = '';
        return true;
    }
    
    const isValid = validateRequiredField('cvv', 'CVV');
    if (!isValid) return false;
    
    return validateField('cvv', /^\d{3}$/, 'Must be exactly 3 digits');
}

function validateExpDate() {
    // Only validate if credit card or debit card is selected
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    if (!paymentMethod || (paymentMethod.value !== 'Credit Card' && paymentMethod.value !== 'Debit Card')) {
        document.querySelector('#expdate + .error-text').textContent = '';
        return true;
    }
    
    const expDateInput = document.getElementById('expdate');
    const errorElement = document.querySelector('#expdate + .error-text');
    
    if (!expDateInput.value) {
        errorElement.textContent = 'Expiry date is required';
        return false;
    }
    
    const selectedDate = new Date(expDateInput.value);
    const currentDate = new Date();
    
    if (selectedDate.getFullYear() < currentDate.getFullYear() || 
        (selectedDate.getFullYear() === currentDate.getFullYear() && 
        selectedDate.getMonth() < currentDate.getMonth())) {
        errorElement.textContent = 'Card has expired';
        return false;
    }
    
    errorElement.textContent = '';
    return true;
}

function validatePaymentMethod() {
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    const errorElement = document.querySelector('.payment-method .error-text');
    
    if (!paymentMethod) {
        errorElement.textContent = 'Payment method is required';
        return false;
    }
    
    // Remove the E-wallet balance check here since we want to show the top-up button
    errorElement.textContent = '';
    return true;
}

// Generic field validation function
function validateField(fieldId, regex, errorMessage) {
    const field = document.getElementById(fieldId);
    const errorElement = document.querySelector(`#${fieldId} + .error-text`);
    
    if (!regex.test(field.value)) {
        errorElement.textContent = errorMessage;
        return false;
    }
    
    errorElement.textContent = '';
    return true;
}

function togglePaymentSections() {
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    
    if (paymentMethod) {
        document.getElementById('creditCardSection').classList.remove('active');
        document.getElementById('eWalletSection').classList.remove('active');
        
        if (paymentMethod.value === 'Credit Card' || paymentMethod.value === 'Debit Card') {
            document.getElementById('creditCardSection').classList.add('active');
            // Make credit card fields required
            document.getElementById('cname').required = true;
            document.getElementById('ccnum').required = true;
            document.getElementById('expdate').required = true;
            document.getElementById('cvv').required = true;
        } else if (paymentMethod.value === 'E-wallet') {
            document.getElementById('eWalletSection').classList.add('active');
            // Make credit card fields not required
            document.getElementById('cname').required = false;
            document.getElementById('ccnum').required = false;
            document.getElementById('expdate').required = false;
            document.getElementById('cvv').required = false;
        }
    }
}

// Set up event listeners for payment method radio buttons
document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
    radio.addEventListener('change', function() {
        togglePaymentSections();
    });
});

// Set up event listeners for real-time validation
document.getElementById('fullname').addEventListener('input', validateFullName);
document.getElementById('contact').addEventListener('input', validateContact);
document.getElementById('email').addEventListener('input', validateEmail);
document.getElementById('address').addEventListener('input', validateAddress);
document.getElementById('city').addEventListener('input', validateCity);
document.getElementById('postcode').addEventListener('input', validatePostcode);
document.getElementById('cname').addEventListener('input', validateCardName);
document.getElementById('ccnum').addEventListener('input', validateCardNumber);
document.getElementById('cvv').addEventListener('input', validateCVV);
document.getElementById('expdate').addEventListener('change', validateExpDate);

// Form submission validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Validate all required fields
    isValid = validateFullName() && isValid;
    isValid = validateContact() && isValid;
    isValid = validateEmail() && isValid;
    isValid = validateAddress() && isValid;
    isValid = validateCity() && isValid;
    isValid = validatePostcode() && isValid;
    isValid = validatePaymentMethod() && isValid;
    
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    if (paymentMethod && (paymentMethod.value === 'Credit Card' || paymentMethod.value === 'Debit Card')) {
        isValid = validateCardName() && isValid;
        isValid = validateCardNumber() && isValid;
        isValid = validateExpDate() && isValid;
        isValid = validateCVV() && isValid;
    }
    
    // State is a select element, just check if it has a value
    const stateSelect = document.getElementById('state');
    const stateError = document.querySelector('#state + .error-text');
    if (!stateSelect.value) {
        stateError.textContent = 'State is required';
        isValid = false;
    } else {
        stateError.textContent = '';
    }

    if (!isValid) {
        e.preventDefault();
        // Scroll to the first error
        const firstError = document.querySelector('.error-text');
        if (firstError && firstError.textContent) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// Success Popup Functions
function showSuccessPopup() {
        // Check if this was an E-wallet payment
        const urlParams = new URLSearchParams(window.location.search);
        const isEWalletPayment = urlParams.get('ewallet') === 'true';
        const remainingBalance = urlParams.get('balance');
        
        let balanceHTML = '';
        if (isEWalletPayment && remainingBalance) {
            balanceHTML = `
                <div class="remaining-balance">
                    Your current E-wallet balance: RM ${parseFloat(remainingBalance).toFixed(2)}
                </div>
            `;
        }
        
        const popupHTML = `
            <div class="simple-popup-overlay">
                <div class="simple-popup">
                    <div class="popup-icon">✓</div>
                    <h2>Payment Successful!</h2>
                    <p>Thank you for your purchase. Your order has been placed successfully.</p>
                    ${balanceHTML}
                    <button class="simple-popup-btn" onclick="closePopup()">OK</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', popupHTML);
}   


function closePopup() {
    const popup = document.querySelector('.simple-popup-overlay');
    if (popup) popup.remove();
    window.location.href = 'index.php';
}

// Check for success parameter in URL and show popup
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('payment') === 'success') {
        showSuccessPopup();
    }
    togglePaymentSections();
});
</script>
</body>
</html>

<?php include 'footer.php'; ?>