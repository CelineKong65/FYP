<?php
ob_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$custID = $_SESSION['user_id'];
$eWalletBalance = 0;
$requiredAmount = $_SESSION['required_amount'] ?? 0;
$topupAmount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

$query = "SELECT EWalletBalance FROM customer WHERE CustID = :custID";
$stmt = $conn->prepare($query);
$stmt->bindParam(':custID', $custID, PDO::PARAM_INT);
$stmt->execute();
$eWalletBalance = $stmt->fetchColumn() ?? 0;

// Initialize variables for form fields
$paymentMethod = '';
$cardName = '';
$cardNum = '';
$cardExpDate = '';
$cardCVV = '';
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) {
    $topupAmount = (float)$_POST['topup_amount'];
    $paymentMethod = $_POST['paymentMethod'] ?? '';
    $cardName = $_POST['cname'] ?? '';
    $cardNum = $_POST['ccnum'] ?? '';
    $cardExpDate = $_POST['expdate'] ?? '';
    $cardCVV = $_POST['cvv'] ?? '';
    
    // Validate all fields
    $errors = [];
    
    // Top-up amount validation
    if (empty($_POST['topup_amount'])) {
        $errors[] = "Top-up amount is required";
        $fieldErrors['topup_amount'] = "Top-up amount is required";
    } elseif ($topupAmount <= 0) {
        $errors[] = "The top-up amount must be greater than 0.";
        $fieldErrors['topup_amount'] = "Amount must be greater than 0";
    } elseif ($topupAmount < 10) {
        $errors[] = "The top-up amount must be at least RM 10.";
        $fieldErrors['topup_amount'] = "Value must be greater than or equal to 10";
    } elseif ($topupAmount > 1000) {
        $errors[] = "The maximum top-up amount is RM 1000.";
        $fieldErrors['topup_amount'] = "Maximum amount is RM 1000";
    }
    
    // Payment Method Validation
    if (empty($paymentMethod)) {
        $errors[] = "Payment method is required";
        $fieldErrors['paymentMethod'] = "Payment method is required";
    }

    // Credit/Debit Card validations
    if ($paymentMethod === 'Credit Card' || $paymentMethod === 'Debit Card') {
        // Card Name validation
        if (empty($cardName)) {
            $errors[] = "Bank is required";
            $fieldErrors['cname'] = "Please select a bank";
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
                $fieldErrors['expdate'] = "Invalid format (YYYY-MM)";
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
            $errors[] = "CVV must be 3 digits";
            $fieldErrors['cvv'] = "Must be 3 digits";
        }
    }
    
    if (empty($errors)) {
        // Update eWallet Balance
        $newBalance = $eWalletBalance + $topupAmount;
        $updateQuery = "UPDATE customer SET EWalletBalance = :newBalance WHERE CustID = :custID";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':newBalance', $newBalance, PDO::PARAM_STR);
        $updateStmt->bindParam(':custID', $custID, PDO::PARAM_INT);

        if ($updateStmt->execute()) {
            // After updating eWallet, insert the payment details into the TopUp table
            $insertTopUpQuery = "INSERT INTO TopUp (CustID, TopUpMethod, CardNum, ExpDate, CardName, CVV) 
                                 VALUES (:custID, :topUpMethod, :cardNum, :expDate, :cardName, :cvv)";
            $insertStmt = $conn->prepare($insertTopUpQuery);
            $insertStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
            $insertStmt->bindParam(':topUpMethod', $paymentMethod, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardNum', $cardNum, PDO::PARAM_STR);
            $insertStmt->bindParam(':expDate', $cardExpDate, PDO::PARAM_STR);
            $insertStmt->bindParam(':cardName', $cardName, PDO::PARAM_STR);
            $insertStmt->bindParam(':cvv', $cardCVV, PDO::PARAM_STR);

            if ($insertStmt->execute()) {
                $_SESSION['topup_success'] = $topupAmount;
                $_SESSION['new_balance'] = $newBalance;
                header('Location: topup.php');
                exit();
            } else {
                $error = "An error occurred while saving payment information. Please try again.";
            }
        } else {
            $error = "An error occurred during the top-up process. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-wallet Top Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="topup.css">
    <style>
        input[type="number"], input[type="text"], input[type="month"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
        </div>
        <ul class="sidebar-menu">
            <li><a href="account.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="order_history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="rate_products.php"><i class="fa fa-star" style="color: white;"></i>Rate</a></li>
            <li class="active"><a href="topup.php"><i class="fa-solid fa-money-check-dollar" style="color: white;"></i>Top Up</a></li>
            <li><a href="my_vouchers.php"><i class="fa-solid fa-ticket" style="color: white;"></i>My Voucher</a></li>
        </ul>
        <div class="sidebar-footer">
            <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> LOG OUT</button>
        </div>
    </div>
    <div class="topup-container">
        <h2>E-wallet Top Up</h2>
        
        <div class="balance-info">
            <p>Current Balance</p>
            <p class="balance-amount">RM <?= number_format($eWalletBalance, 2) ?></p>
            <?php if ($requiredAmount > 0): ?>
                <p>You need <span class="required-amount">RM <?= number_format(max(0, $requiredAmount - $eWalletBalance), 2) ?></span> more to complete your purchase.</p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['topup_success'])): ?>
            <div class="success-popup" id="successPopup">
                <div class="popup-content">
                    <h3>Top Up Successful!</h3>
                    <p>You have successfully topped up RM <?= number_format($_SESSION['topup_success'], 2) ?></p>
                    <div class="new-balance"> 
                        <p><strong>Your new balance is RM <?= number_format($_SESSION['new_balance'], 2) ?></strong></p>
                    </div>
                    <button onclick="closePopup()">OK</button>
                </div>
            </div>
            <?php unset($_SESSION['topup_success']); ?>
            <?php unset($_SESSION['new_balance']); ?>
        <?php endif; ?>
        
        <form method="POST" action="topup.php" id="topupForm" novalidate>
            <div class="form-group">
                <label for="topup_amount" class="required-field">Top Up Amount (RM)</label>
                <input type="number" id="topup_amount" name="topup_amount" min="10" max="1000" step="10" 
                       value="<?= $topupAmount > 0 ? $topupAmount : '' ?>" required
                       class="<?= isset($fieldErrors['topup_amount']) ? 'error-field' : '' ?>">
                <span class="error-text" id="topup_amount_error"><?= isset($fieldErrors['topup_amount']) ? htmlspecialchars($fieldErrors['topup_amount']) : '' ?></span>
            </div>
            
            <div class="amount-buttons">
                <div class="amount-btn" data-amount="50">RM 50</div>
                <div class="amount-btn" data-amount="100">RM 100</div>
                <div class="amount-btn" data-amount="200">RM 200</div>
                <div class="amount-btn" data-amount="500">RM 500</div>
                <div class="amount-btn" data-amount="1000">RM 1000</div>
            </div>

            <div class="col">
                <h2>Payment</h2>
                
                <div class="payment-method">
                    <label class="required-field">
                        <input type="radio" name="paymentMethod" value="Credit Card" 
                               <?= $paymentMethod === 'Credit Card' ? 'checked' : '' ?> required>
                        Credit Card
                    </label>
                    <label>
                        <input type="radio" name="paymentMethod" value="Debit Card" 
                               <?= $paymentMethod === 'Debit Card' ? 'checked' : '' ?>>
                        Debit Card
                    </label>
                    <span class="payment-method-error" id="payment_method_error"><?= isset($fieldErrors['paymentMethod']) ? htmlspecialchars($fieldErrors['paymentMethod']) : '' ?></span>
                </div>
                
                <!-- Credit/Debit Card Section - Always visible -->
                <div id="creditCardSection">
                    <p>Accepted Cards</p>
                    <div class="card-icons">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" alt="Visa" style="width:40px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/30/American_Express_logo.svg" alt="Amex" style="width:40px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" style="width:40px;">
                    </div>

                    <div class="form-group">
                        <label for="cname" class="required-field">Bank</label>
                        <select id="cname" name="cname" class="<?= isset($fieldErrors['cname']) ? 'error-field' : '' ?>">
                            <option value="">-- Select Bank --</option>
                            <option value="Maybank" <?= $cardName === 'Maybank' ? 'selected' : '' ?>>Maybank</option>
                            <option value="CIMB Bank" <?= $cardName === 'CIMB Bank' ? 'selected' : '' ?>>CIMB Bank</option>
                            <option value="Public Bank" <?= $cardName === 'Public Bank' ? 'selected' : '' ?>>Public Bank</option>
                            <option value="OCBC Bank" <?= $cardName === 'OCBC Bank' ? 'selected' : '' ?>>OCBC Bank</option>
                            <option value="RHB Bank" <?= $cardName === 'RHB Bank' ? 'selected' : '' ?>>RHB Bank</option>
                            <option value="Hong Leong Bank" <?= $cardName === 'Hong Leong Bank' ? 'selected' : '' ?>>Hong Leong Bank</option>
                            <option value="Bank Islam" <?= $cardName === 'Bank Islam' ? 'selected' : '' ?>>Bank Islam</option>
                            <option value="BSN" <?= $cardName === 'BSN Bank' ? 'selected' : '' ?>>BSN Bank</option>
                        </select>
                        <span class="error-text" id="cname_error"><?= isset($fieldErrors['cname']) ? htmlspecialchars($fieldErrors['cname']) : '' ?></span>
                    </div>

                    <div class="form-group">
                        <label for="ccnum" class="required-field">Card Number</label>
                        <input type="text" id="ccnum" name="ccnum" placeholder="Enter card number" 
                               value="<?= htmlspecialchars($cardNum) ?>" 
                               class="<?= isset($fieldErrors['ccnum']) ? 'error-field' : '' ?>">
                        <span class="error-text" id="ccnum_error"><?= isset($fieldErrors['ccnum']) ? htmlspecialchars($fieldErrors['ccnum']) : '' ?></span>
                    </div>

                    <div class="form-group">
                        <label for="expdate" class="required-field">Exp Date</label>
                        <input type="month" id="expdate" name="expdate" 
                               value="<?= htmlspecialchars($cardExpDate) ?>" min="<?= date('Y-m') ?>" 
                               class="<?= isset($fieldErrors['expdate']) ? 'error-field' : '' ?>">
                        <span class="error-text" id="expdate_error"><?= isset($fieldErrors['expdate']) ? htmlspecialchars($fieldErrors['expdate']) : '' ?></span>
                    </div>

                    <div class="form-group">
                        <label for="cvv" class="required-field">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="Enter CVV" 
                               value="<?= htmlspecialchars($cardCVV) ?>" 
                               class="<?= isset($fieldErrors['cvv']) ? 'error-field' : '' ?>">
                        <span class="error-text" id="cvv_error"><?= isset($fieldErrors['cvv']) ? htmlspecialchars($fieldErrors['cvv']) : '' ?></span>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn">Top Up Now</button>
        </form>
    </div>

    <script>
        // Amount buttons functionality
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.amount-btn').forEach(b => {
                    b.classList.remove('active');
                });
             
                this.classList.add('active');
                const amount = this.getAttribute('data-amount');
                document.getElementById('topup_amount').value = amount;
                validateTopupAmount(); // Validate after setting the amount
            });
        });
        
        // Preselect amount if coming from URL
        const urlParams = new URLSearchParams(window.location.search);
        const amountParam = urlParams.get('amount');
        if (amountParam) {
            const matchingBtn = document.querySelector(`.amount-btn[data-amount="${amountParam}"]`);
            if (matchingBtn) {
                matchingBtn.classList.add('active');
            }
        }

        // Real-time validation functions
        function validateTopupAmount() {
            const field = document.getElementById('topup_amount');
            const errorElement = document.getElementById('topup_amount_error');
            const value = parseFloat(field.value);
            
            if (field.value.trim() === '') {
                field.classList.add('error-field');
                errorElement.textContent = 'Top-up amount is required';
                return false;
            }
            
            if (isNaN(value) || value <= 0) {
                field.classList.add('error-field');
                errorElement.textContent = 'Amount must be greater than 0';
                return false;
            } else if (value < 10) {
                field.classList.add('error-field');
                errorElement.textContent = 'Value must be greater than or equal to 10';
                return false;
            } else if (value > 1000) {
                field.classList.add('error-field');
                errorElement.textContent = 'Maximum amount is RM 1000';
                return false;
            }
            
            field.classList.remove('error-field');
            errorElement.textContent = '';
            return true;
        }

        function validatePaymentMethod() {
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
            const errorElement = document.getElementById('payment_method_error');
            
            if (!paymentMethod) {
                errorElement.textContent = 'Payment method is required';
                return false;
            }
            
            errorElement.textContent = '';
            return true;
        }

        function validateCardName() {
            const field = document.getElementById('cname');
            const errorElement = document.getElementById('cname_error');
            
            if (!field.value.trim()) {
                field.classList.add('error-field');
                errorElement.textContent = 'Bank is required';
                return false;
            }
            
            field.classList.remove('error-field');
            errorElement.textContent = '';
            return true;
        }

        function validateCardNumber() {
            const field = document.getElementById('ccnum');
            const errorElement = document.getElementById('ccnum_error');
            
            if (!field.value.trim()) {
                field.classList.add('error-field');
                errorElement.textContent = 'Card number is required';
                return false;
            }
            
            // Remove all non-digit characters
            const cleanedValue = field.value.replace(/\D/g, '');
            
            if (!/^\d{13,16}$/.test(cleanedValue)) {
                field.classList.add('error-field');
                errorElement.textContent = 'Must be 13-16 digits';
                return false;
            }
            
            // Update the field with cleaned value (no formatting)
            if (field.value !== cleanedValue) {
                field.value = cleanedValue;
            }
            
            field.classList.remove('error-field');
            errorElement.textContent = '';
            return true;
        }

        function validateExpDate() {
            const field = document.getElementById('expdate');
            const errorElement = document.getElementById('expdate_error');
            
            if (!field.value) {
                field.classList.add('error-field');
                errorElement.textContent = 'Expiry date is required';
                return false;
            }
            
            const selectedDate = new Date(field.value);
            const currentDate = new Date();
            currentDate.setHours(0, 0, 0, 0);
            
            if (selectedDate < currentDate) {
                field.classList.add('error-field');
                errorElement.textContent = 'Card has expired';
                return false;
            }
            
            field.classList.remove('error-field');
            errorElement.textContent = '';
            return true;
        }

        function validateCVV() {
            const field = document.getElementById('cvv');
            const errorElement = document.getElementById('cvv_error');
            
            if (!field.value.trim()) {
                field.classList.add('error-field');
                errorElement.textContent = 'CVV is required';
                return false;
            }
            
            // Remove all non-digit characters
            const cleanedValue = field.value.replace(/\D/g, '');
            
            if (!/^\d{3}$/.test(cleanedValue)) {
                field.classList.add('error-field');
                errorElement.textContent = 'Must be 3 digits';
                return false;
            }
            
            // Update the field with cleaned value
            if (field.value !== cleanedValue) {
                field.value = cleanedValue;
            }
            
            field.classList.remove('error-field');
            errorElement.textContent = '';
            return true;
        }

        // Add event listeners for real-time validation
        document.getElementById('topup_amount').addEventListener('input', validateTopupAmount);
        document.getElementById('topup_amount').addEventListener('change', validateTopupAmount);
        
        document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
            radio.addEventListener('change', function() {
                validatePaymentMethod();
            });
        });
        
        // Validate card fields independently
        document.getElementById('cname').addEventListener('input', validateCardName);
        document.getElementById('ccnum').addEventListener('input', validateCardNumber);
        document.getElementById('expdate').addEventListener('change', validateExpDate);
        document.getElementById('cvv').addEventListener('input', validateCVV);

        // Form submission validation
        document.getElementById('topupForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate top-up amount
            isValid = validateTopupAmount() && isValid;
            
            // Validate payment method
            isValid = validatePaymentMethod() && isValid;
            
            // Validate credit card fields if payment method is credit/debit card
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
            if (paymentMethod && (paymentMethod.value === 'Credit Card' || paymentMethod.value === 'Debit Card')) {
                isValid = validateCardName() && isValid;
                isValid = validateCardNumber() && isValid;
                isValid = validateExpDate() && isValid;
                isValid = validateCVV() && isValid;
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to the first error
                const firstError = document.querySelector('.error-field');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        // Close popup function
        function closePopup() {
            const popup = document.getElementById('successPopup');
            if (popup) {
                popup.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initial validation of all fields
            validateTopupAmount();
            validatePaymentMethod();
            validateCardName();
            validateCardNumber();
            validateExpDate();
            validateCVV();
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>