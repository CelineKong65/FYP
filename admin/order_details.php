<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

if (!isset($_GET['order_id'])) {
    header("Location: order_view.php");
    exit();
}

$order_id = $_GET['order_id'];

$order_query_sql = "SELECT * FROM orderpayment WHERE OrderID = ?";
$stmt = $conn->prepare($order_query_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$orderpayment = $stmt->get_result()->fetch_assoc();

// Get order details
$order_details_query = "SELECT * FROM orderdetails WHERE OrderID = ?";
$stmt = $conn->prepare($order_details_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_details = $stmt->get_result();

$cust_id = $orderpayment['CustID'];
$customer_query = "SELECT CustID, CustName, CustEmail FROM customer WHERE CustID = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

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
    <div class="header">
        <?php include 'header.php'; ?>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="order-header">
                <button name="back" class="view-details-btn" onclick="window.location.href='order_view.php'">Back</button>
                <h2>Order Details - #<?php echo $order_id; ?></h2>
                <button name="print" onclick="printOrder()">Print</button>
            </div>
            
            <div class="cust-info">
                <p><b>User Information</b></p>
                <p>Name: <?php echo $customer['CustName']; ?> (ID: <?php echo $customer['CustID']; ?>)</p>
                <p>Email: <?php echo $customer['CustEmail']; ?></p>
            </div>
            <div style="display: flex; gap: 50px; flex-wrap: wrap; margin-top: 20px;">
    
            <div class="rec-info">
                <h3>Receiver Information</h3>
                <p><b>Name:</b> <?php echo($orderpayment['ReceiverName']); ?></p>
                <p><b>Contact Number:</b> <?php echo $orderpayment['ReceiverContact']; ?></p>
                <p><b>Email:</b> <?php echo $orderpayment['ReceiverEmail']; ?></p>
                <p><b>Address:</b> <?php echo $orderpayment['StreetAddress'] . ', ' . $orderpayment['Postcode'] . ' ' . $orderpayment['City'] . ', ' . $orderpayment['State']; ?></p>
            </div>

            <div class="order-info">
                <h3>Order Info</h3>
                <p><b>Order Date: </b><?php echo $orderpayment['OrderDate']; ?></p>
                <p><b>Status: </b>
                    <span class="status-badge <?php echo ($orderpayment['OrderStatus'] == 'Out for delivery' || $orderpayment['OrderStatus'] == 'Delivered') ? 'status-completed' : 'status-pending'; ?>">
                        <?php echo $orderpayment['OrderStatus']; ?>
                    </span>
                </p>
                <p><b>Payment Method: </b><?php echo $orderpayment['PaymentMethod']; ?></p>
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
                    while ($orderdetails = $order_details->fetch_assoc()): 
                        $total_item = $orderdetails['ProductPrice'] * $orderdetails['Quantity'];
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td colspan="2">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php
                            $imageName = strtolower(str_replace(' ', '-', $orderdetails['ProductName']));
                            $jpgPath = "../image/{$imageName}.jpg";
                            $pngPath = "../image/{$imageName}.png";

                            if (file_exists($jpgPath)) {
                                echo "<img src='{$jpgPath}' alt='{$orderdetails['ProductName']}' style='width: 70px;'>";
                            } elseif (file_exists($pngPath)) {
                                echo "<img src='{$pngPath}' alt='{$orderdetails['ProductName']}' style='width: 70px;'>";
                            } else {
                                echo "<img src='../image/placeholder.jpg' alt='Image not available' style='width: 70px;'>";
                            }
                            ?>
                            <div style="margin-left: 20px; text-align: left;">
                                <strong><?php echo $orderdetails['ProductName']; ?></strong><br>
                                Size: <?php echo $orderdetails['Size']; ?>
                            </div>
                        </div>
                        </td>
                        <td><?php echo number_format($orderdetails['ProductPrice'], 2); ?></td>
                        <td>&times <?php echo $orderdetails['Quantity']; ?></td>
                        <td style="text-align: right;"><?php echo number_format($total_item, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>

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