

<?php
session_start(); // Start session to access session variables
include 'header.php'; 
include 'config.php'; // Include the database configuration file

// Retrieve grand total from session
$grandTotal = $_SESSION['grand_total'] ?? 0;

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
    $insertStmt->bindParam(':totalPrice', $grandTotal, PDO::PARAM_STR);
    $insertStmt->bindParam(':cardName', $cardName, PDO::PARAM_STR);
    $insertStmt->bindParam(':cardNum', $cardNum, PDO::PARAM_STR);
    $insertStmt->bindParam(':cardCVV', $cardCVV, PDO::PARAM_STR);

    if ($insertStmt->execute()) {
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
    <style>
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }

        .container-bill {
            max-width: 800px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin: auto;
            margin-top: 150px;
            margin-bottom: 100px;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .col {
            flex: 1;
            padding: 10px;
        }

        h2 {
            margin-bottom: 10px;
        }

        input[type="text"], input[type="email"] {
            width: 80%;
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .card-icons img {
            width: 40px;
            margin-right: 5px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        button.checkout-btn {
            width: 100%;
            background: #28a745;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        button.checkout-btn:hover {
            background: #218838;
        }

    </style>
</head>
<body>
    <div class="container-bill">
        <form method="POST" action="payment.php">
            <div class="row">
                <div class="col">
                    <h2>Billing Address</h2>
                    <label for="price"><b>Total Price</b></label>
                    <input type="text" id="price" value="RM <?= number_format($grandTotal, 2) ?>" readonly>

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
</body>
</html>

<?php
include 'footer.php'; 
?>