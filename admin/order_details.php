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
$customer_query = "SELECT CustName, CustEmail FROM customer WHERE CustID = ?";
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
                <p>Name: <?php echo $customer['CustName']; ?></p>
                <p>Email: <?php echo $customer['CustEmail']; ?></p>
            </div>
            <div class="rec-info">
                <h3>Receiver Information</h3>
                <p><b>Name:</b> <?php echo($orderpayment['ReceiverName']); ?></p>
                <p><b>Contact Number:</b> <?php echo $orderpayment['ReceiverContact']; ?></p>
                <p><b>Email:</b> <?php echo $orderpayment['ReceiverEmail']; ?></p>
                <p><b>Address:</b> <?php echo $orderpayment['StreetAddress'] . ', ' . $orderpayment['Postcode'] . ' ' . $orderpayment['City'] . ', ' . $orderpayment['State']; ?></p>
                <p><b>Order Date:</b> <?php echo $orderpayment['OrderDate']; ?></p>
                <p><b>Order Status:</b> 
                    <span class="<?php echo ($orderpayment['OrderStatus'] == 'Out for delivery') ? 'status-completed' : 'status-pending'; ?>">
                        <?php echo $orderpayment['OrderStatus']; ?>
                    </span>
                </p>
            </div>

            <div class="order_item">
                <h3>Order Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th></th>
                            <th colspan="2">Product</th>
                            <th class="center">Price (RM)</th>
                            <th class="center">Qty</th>
                            <th style="text-align: right;">Total (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;

                        while ($orderdetails = $order_details->fetch_assoc()): 
                            $total_item = $orderdetails['ProductPrice'] * $orderdetails['Quantity'];
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td style="display: grid; place-items: center;">
                                <?php
                                $imageName = strtolower(str_replace(' ', '-', $orderdetails['ProductName']));
                                $jpgPath = "../image/{$imageName}.jpg";
                                $pngPath = "../image/{$imageName}.png";

                                if (file_exists($jpgPath)) {
                                    echo "<img src='{$jpgPath}' alt='{$orderdetails['ProductName']}'>";
                                } elseif (file_exists($pngPath)) {
                                    echo "<img src='{$pngPath}' alt='{$orderdetails['ProductName']}'>";
                                } else {
                                    echo "<img src='../image/placeholder.jpg' alt='Image not available' width='150'>";
                                }
                                ?>
                            <td colspan="2" style="line-height: 1.5;"><strong><?php echo $orderdetails['ProductName']; ?></strong><br>Size: <?php echo $orderdetails['Size']; ?></td>
                            <td class="center"><?php echo number_format($orderdetails['ProductPrice'], 2); ?></td>
                            <td class="center"><?php echo $orderdetails['Quantity']; ?></td>
                            <td><?php echo number_format($total_item, 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="highlight-row">
                            <td colspan="6" style="text-align: right;">Delivery Fee:</td>
                            <td>RM <?php echo number_format($orderpayment['DeliveryFee'], 2); ?></td>
                        </tr>
                        <tr class="highlight-row">
                            <td colspan="6" style="text-align: right;">TOTAL</td>
                            <td>RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
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