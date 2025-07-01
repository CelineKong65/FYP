<?php
include 'config.php'; 
include 'header.php';

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// If order_id is not passed via GET, redirect to order history
if (!isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit();
}
// Get the order ID from the GET parameter
$order_id = $_GET['order_id'];

try {
    // Retrieve order payment info and voucher code (if applied)
    $order_query_sql = "SELECT op.*, v.VoucherCode
                        FROM orderpayment op
                        LEFT JOIN voucher v ON op.VoucherID = v.VoucherID
                        WHERE op.OrderID = ?";
    $stmt = $conn->prepare($order_query_sql);
    $stmt->execute([$order_id]);
    $orderpayment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Retrieve order item details and product pictures
    $order_details_query = "SELECT od.*, p.ProductPicture 
                            FROM orderdetails od
                            LEFT JOIN product p ON od.ProductID = p.ProductID
                            WHERE od.OrderID = ?";
    $stmt = $conn->prepare($order_details_query);
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get customer information based on CustID
    $cust_id = $orderpayment['CustID'];
    $customer_query = "SELECT CustID, CustName, CustEmail FROM customer WHERE CustID = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->execute([$cust_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Display error if database access fails
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel='stylesheet' href='order_details.css'>
</head>
<body>
    <div class="order-details-container">
        <div class="main-content">
            <div class="order-header">
                <button name="back" class="view-details-btn" onclick="window.location.href='order_history.php'">Back</button>
                <h2>Order Details - #<?php echo $order_id; ?></h2>
                <button name="print" onclick="printOrder()">Print</button>
            </div>
            
            <div class="cust-info">
                <p><b>User Information</b></p>
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
                    $total_items_price = 0;
                    foreach ($order_details as $orderdetails): 
                        $item_total = $orderdetails['ProductPrice'] * $orderdetails['Quantity'];
                        $total_items_price += $item_total;
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td colspan="2">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php
                                // Set product image path
                                $imageSrc = $orderdetails['ProductPicture'] ? 'image/' . htmlspecialchars($orderdetails['ProductPicture']) : null;
                            ?>
                             <img src="<?= $imageSrc ?>" alt="<?= $orderdetails['ProductName'] ?>" style="width: 90px;">
                            <div style="margin-left: 20px; text-align: left;">
                                <strong><?php echo htmlspecialchars($orderdetails['ProductName']); ?></strong><br>
                                Size: <?php echo htmlspecialchars($orderdetails['Size']); ?>
                            </div>
                        </div>
                        </td>
                        <td><?php echo number_format($orderdetails['ProductPrice'], 2); ?></td>
                        <td>&times <?php echo htmlspecialchars($orderdetails['Quantity']); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item_total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Show voucher discount if voucher was applied -->
                    <?php if ($orderpayment['VoucherID'] !== null):
                        $discount_value = $total_items_price - $orderpayment['TotalPrice'];
                    ?>
                    <tr class="voucher-row">
                        <td colspan="5" class="total-label">Voucher Discount (<?php echo htmlspecialchars($orderpayment['VoucherCode']); ?>):</td>
                        <td class="total-value">-RM <?php echo number_format($discount_value, 2); ?></td>
                    </tr>
                    <?php endif; ?>

                    <!-- Show final total including delivery fee -->
                    <tr class="total-row">
                        <td colspan="5" class="total-label">Total (Incl. Delivery):</td>
                        <td class="total-value">RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
                    </tr>
                </tbody>
                </table>

            </div>
        </div>
    </div>

    <!-- JavaScript function for print button -->
    <script>
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>

<?php 
include 'footer.php';
?>
