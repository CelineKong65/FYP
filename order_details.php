<?php
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit();
}

$order_id = $_GET['order_id'];

try {
    // Get order payment information
    $order_query_sql = "SELECT * FROM orderpayment WHERE OrderID = ?";
    $stmt = $conn->prepare($order_query_sql);
    $stmt->execute([$order_id]);
    $orderpayment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orderpayment) {
        header("Location: order_view.php");
        exit();
    }

    // Get order details
    $order_details_query = "SELECT * FROM orderdetails WHERE OrderID = ?";
    $stmt = $conn->prepare($order_details_query);
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get customer information
    $cust_id = $orderpayment['CustID'];
    $customer_query = "SELECT CustID, CustName, CustEmail FROM customer WHERE CustID = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->execute([$cust_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="order_details.css">
</head>
<body>
    <div class="order-details-container">
        <div class="main-content">
            <div class="order-header">
                <button name="back" class="view-details-btn" onclick="window.location.href='order_history.php'">Back</button>
                <h2>Order Details - #<?php echo htmlspecialchars($order_id); ?></h2>
                <button name="print" onclick="printOrder()">Print</button>
            </div>
            
            <div class="cust-info">
                <p><b>User Information</b></p>
                <p>ID: <?php echo htmlspecialchars($customer['CustID']); ?></p>
                <p>Name: <?php echo htmlspecialchars($customer['CustName']); ?></p>
                <p>Email: <?php echo htmlspecialchars($customer['CustEmail']); ?></p>
            </div>
            <div style="display: flex; gap: 50px; flex-wrap: wrap; margin-top: 20px;">
    
            <div class="rec-info">
                <h3>Receiver Information</h3>
                <p><b>Name:</b> <?php echo htmlspecialchars($orderpayment['ReceiverName']); ?></p>
                <p><b>Contact Number:</b> <?php echo htmlspecialchars($orderpayment['ReceiverContact']); ?></p>
                <p><b>Email:</b> <?php echo htmlspecialchars($orderpayment['ReceiverEmail']); ?></p>
                <p><b>Address:</b> <?php echo htmlspecialchars($orderpayment['StreetAddress'] . ', ' . $orderpayment['Postcode'] . ' ' . $orderpayment['City'] . ', ' . $orderpayment['State']); ?></p>
            </div>

            <div class="order-info">
                <h3>Order Info</h3>
                <p><b>Order Date: </b><?php echo htmlspecialchars($orderpayment['OrderDate']); ?></p>
                <p><b>Status: </b>
                    <span class="status-badge <?php echo ($orderpayment['OrderStatus'] == 'Out for delivery' || $orderpayment['OrderStatus'] == 'Delivered') ? 'status-completed' : 'status-pending'; ?>">
                        <?php echo htmlspecialchars($orderpayment['OrderStatus']); ?>
                    </span>
                </p>
                <p><b>Payment Method: </b><?php echo htmlspecialchars($orderpayment['PaymentMethod']); ?></p>
            </div>

        </div>

            <div class="order_item">
                <h3>Order Summary</h3>
                <table>
                <tbody>
                    <tr>
                        <th>No.</th>
                        <th colspan="2">Product</th>
                        <th>Price (RM)</th>
                        <th>Qty</th>
                        <th style="text-align: right;">Total (RM)</th>
                    </tr>

                    <!-- Order Items -->
                    <?php 
                    $counter = 1;
                    foreach ($order_details as $orderdetail): 
                        $total_item = $orderdetail['ProductPrice'] * $orderdetail['Quantity'];
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td colspan="2">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php
                            $imageName = strtolower(str_replace(' ', '-', $orderdetail['ProductName']));
                            $jpgPath = "image/{$imageName}.jpg";
                            $pngPath = "image/{$imageName}.png";

                            if (file_exists($jpgPath)) {
                                echo "<img src='{$jpgPath}' alt='".htmlspecialchars($orderdetail['ProductName'])."' style='width: 70px;'>";
                            } elseif (file_exists($pngPath)) {
                                echo "<img src='{$pngPath}' alt='".htmlspecialchars($orderdetail['ProductName'])."' style='width: 70px;'>";
                            } else {
                                echo "<img src='image/placeholder.jpg' alt='Image not available' style='width: 70px;'>";
                            }
                            ?>
                            <div style="margin-left: 20px; text-align: left;">
                                <strong><?php echo htmlspecialchars($orderdetail['ProductName']); ?></strong><br>
                                Size: <?php echo htmlspecialchars($orderdetail['Size']); ?>
                            </div>
                        </div>
                        </td>
                        <td><?php echo number_format($orderdetail['ProductPrice'], 2); ?></td>
                        <td>&times <?php echo htmlspecialchars($orderdetail['Quantity']); ?></td>
                        <td style="text-align: right;"><?php echo number_format($total_item, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Total Row -->
                    <tr class="total-row">
                        <td colspan="5" class="total-label">Total (Incl. Delivery):</td>
                        <td class="total-value">RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
                    </tr>
                </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>

<?php include 'footer.php'; ?>