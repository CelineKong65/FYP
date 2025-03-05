<?php
    include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Form</title>
    <style>
        le>
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
        <form>
            <div class="row">
                <div class="col">
                    <h2>Billing Address</h2>
                    <label for="fullname"><b>Full Name</b></label>
                    <input type="text" id="fullname" placeholder="Enter full name">

                    <label for="email"><b>Email</b></label>
                    <input type="email" id="email" placeholder="Enter email">

                    <label for="address"><b>Address</b></label>
                    <input type="text" id="address" placeholder="Enter address">

                    <label for="city"><b>City</b></label>
                    <input type="text" id="city" placeholder="Enter city">

                    <label for="state"><b>State</b></label>
                    <input type="text" id="state" placeholder="Enter state">

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
                    <input type="text" id="cname" placeholder="Enter name on card">

                    <label for="ccnum"><b>Card Number</b></label>
                    <input type="text" id="ccnum" placeholder="Enter card number">

                    <label for="expdate"><b>Exp Date</b></label>
                    <input type="text" id="expdate" placeholder="MM/YY">

                    <label for="cvv"><b>CVV</b></label>
                    <input type="text" id="cvv" placeholder="Enter CVV">
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
