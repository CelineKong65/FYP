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

$order_query_sql = "SELECT * FROM orderpayment WHERE OrderID = :order_id";
$stmt = $conn->prepare($order_query_sql);
$stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
$stmt->execute();
$orderpayment = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if orderpayment exists
if (!$orderpayment) {
    die("Order not found.");
}

// Get order details
$order_details_query = "SELECT * FROM orderdetails WHERE OrderID = :order_id";
$stmt = $conn->prepare($order_details_query);
$stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
$stmt->execute();
$order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customer info query
$cust_id = $orderpayment['CustID'];
$customer_query = "SELECT CustName, CustEmail FROM customer WHERE CustID = :cust_id";
$stmt = $conn->prepare($customer_query);
$stmt->bindParam(':cust_id', $cust_id, PDO::PARAM_INT);
$stmt->execute();
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if customer exists
if (!$customer) {
    die("Customer not found.");
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
                <p>Name: <?php echo $customer['CustName']; ?></p>
                <p>Email: <?php echo $customer['CustEmail']; ?></p>
            </div>
            <div class="rec-info">
                <h3>Receiver Information</h3>
                <p><b>Name:</b> <?php echo htmlspecialchars($orderpayment['ReceiverName']); ?></p>
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

                        foreach ($order_details as $orderdetails): 
                            $total_item = $orderdetails['ProductPrice'] * $orderdetails['Quantity'];
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td style="display: grid; place-items: center;">
                                <?php
                                $imageName = strtolower(str_replace(' ', '-', $orderdetails['ProductName']));
                                $jpgPath = "image/{$imageName}.jpg";
                                $pngPath = "image/{$imageName}.png";

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
                        <?php endforeach; ?>
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

<?php include 'footer.php' ?>
