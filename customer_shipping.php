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
    <link rel="stylesheet" href="customer_shipping.css"> 
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