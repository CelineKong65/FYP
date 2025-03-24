<?php
include 'config.php'; // Ensure this file contains the PDO connection ($conn)

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
    <title>Track Your Order</title>
</head>
<body>
    <h2>Track Your Shipment</h2>
    <form method="GET" action="">
        <label for="order_id">Enter Order ID:</label>
        <input type="text" name="order_id" required>
        <button type="submit">Track</button>
    </form>
    
    <?php if ($shipping) { ?>
        <h3>Shipping Details</h3>
        <p><strong>Shipping Method:</strong> <?= htmlspecialchars($shipping['ShippingMethod']) ?></p>
        <p><strong>Tracking Number:</strong> <?= htmlspecialchars($shipping['TrackingNum']) ?></p>
        <p><strong>Shipping Status:</strong> <?= htmlspecialchars($shipping['ShippingStatus']) ?></p>
        <p><strong>Estimated Delivery Date:</strong> <?= htmlspecialchars($shipping['EstimateDeliveryDate']) ?></p>
        <p><strong>Actual Delivery Date:</strong> <?= htmlspecialchars($shipping['ActualDeliveryDate'] ?? 'Not Delivered Yet') ?></p>
    <?php } elseif (isset($_GET['order_id'])) { ?>
        <p style="color: red;">No shipping record found for this Order ID.</p>
    <?php } ?>
</body>
</html>