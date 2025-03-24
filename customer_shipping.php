<?php
include 'config.php'; 
include 'header.php';

$shipping = null;
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['order_id'])) {
    $orderID = $_GET['order_id'];
    
    $stmt = $conn->prepare("SELECT ShippingMethod, TrackingNum, ShippingStatus, EstimateDeliveryDate, ActualDeliveryDate 
                            FROM shipping WHERE OrderID = ?");
    $stmt->execute([$orderID]);
    $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-top: 150px;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            margin-top: 50px;
        }
        
        .tracking-form {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 25px auto;
            margin-bottom: 50px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #555;
        }
        
        .order-id-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .submit-track-btn {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .submit-track-btn:hover {
            background-color: #2980b9;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            width: 80%;
            max-width: 600px;
            position: relative;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .shipping-details {
            font-size: 0; 
        }
        
        .shipping-details p {
            margin: 16px 0;
            padding: 12px;
            background-color: #f9f9f9;
            border-left: 4px solid #3498db;
            border-radius: 4px;
            white-space: nowrap;
            font-size: 16px; 
        }
        
        .shipping-details strong {
            color: #2c3e50;
            width: 190px; 
            display: inline-block;
            position: relative;
            margin-right: 10px;
        }
        
        .shipping-details strong::after {
            content: ":";
            position: absolute;
            right: 0px;
        }
        
        .error-message {
            text-align: center;
            padding: 15px;
            background: #ffebee;
            border-left: 4px solid #e74c3c;
            max-width: 600px;
            margin: 20px auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Track Your Shipment</h1>
    <form method="GET" action="" class="tracking-form">
        <label for="order_id" class="form-label">Enter Order ID:</label>
        <input type="text" id="order_id" name="order_id" class="order-id-input" required>
        <button type="submit" class="submit-track-btn">Track</button>
    </form>
    
    <?php if ($shipping) { ?>
        <div id="shippingModal" class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('shippingModal').style.display='none'">&times;</span>
                <h2>Shipping Details</h2>
                <div class="shipping-details">
                    <p><strong>Shipping Method</strong> <?= htmlspecialchars($shipping['ShippingMethod']) ?></p>
                    <p><strong>Tracking Number</strong> <?= htmlspecialchars($shipping['TrackingNum']) ?></p>
                    <p><strong>Shipping Status</strong> <?= htmlspecialchars($shipping['ShippingStatus']) ?></p>
                    <p><strong>Estimated Delivery Date</strong> <?= htmlspecialchars($shipping['EstimateDeliveryDate']) ?></p>
                    <p><strong>Actual Delivery Date</strong> <?= htmlspecialchars($shipping['ActualDeliveryDate'] ?? 'Not Delivered Yet') ?></p>
                </div>
            </div>
        </div>
        
        <script>
            window.onclick = function(event) {
                var modal = document.getElementById('shippingModal');
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
    <?php } elseif (isset($_GET['order_id'])) { ?>
        <div class="error-message">
            No shipping record found for this Order ID.
        </div>
    <?php } ?>
</body>
</html>

<?php
include 'footer.php'; 
?>