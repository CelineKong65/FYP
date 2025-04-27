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
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .container {
        flex: 1;
        width: 80%;
        margin: 0 auto;
        margin-top: 160px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow-x: auto；
    }

    .main-content {
        flex-grow: 1;
        padding: 20px;
        margin: 50px;
        background-color: #ffffff;
        border-radius: 10px;
        height: fit-content;
    }

    .order-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    button {
        padding: 10px;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        margin-bottom: 10px;
        font-size: 15px;
        width: 80px;
    }

    .order-header h2 {
        margin: 0;
        flex-grow: 1;
        text-align: center;
        color: #1e3a8a;
        font-size: 35px;
    }

    .order-header button a {
        text-decoration: none;
        color: white;
    }

    button[name="print"]{
        background-color: #ffc107;
        color: white;
    }

    button[name="print"]:hover{
        background-color: #e0a800d1;
        transition: 0.3s ease;
    }

    button[name="back"]:hover{
        background-color: #dc3545;
        transition: 0.3s ease;
    }

    button[name="back"]{
        background-color: #c82333;
    }

    h3{
        font-size: 20px;
    }

    .cust-info, .rec-info{
        font-size: 15px;
    }

    .rec-info, .order_item{
        border-top: 1px solid #ddd; 
    }

    .order_item {
        margin-top: 20px;
    }

    .order_item h3 {
        font-size: 24px;
        color: #1e3a8a;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 16px;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    table th, table td {
        padding: 16px;
        border-bottom: 1px solid #dee2e6;
    }

    table th {
        background-color: #1e3a8a;
        color: white;
        text-align: center;
    }

    table td {
        text-align: center;
    }

    table td img {
        width: 70px;
        height: auto;
        border-radius: 8px;
    }

    table tr:last-child td {
        font-size: 18px;
        font-weight: bold;
        background-color: #f0f8ff;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        transform: translateX(-5px) translateY(2px);
    }

    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }

    p{
        line-height: 1.3;
    }

    .grand-total-row td {
        background-color: #dbeafe;
        font-size: 18px;
        font-weight: bold;
        color: #1e40af;
        padding: 20px;
        text-align: right;
    }

    .grand-total-label {
        text-align: right;
    }

    .grand-total-value {
        text-align: right;
    }

</style>

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
                <p>ID: <?php echo $customer['CustID']; ?></p>
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
                <h3>Order Summary</h3>
                <table>
                <tbody>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: left;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <strong>Order Date:</strong><br>
                                    <span style="color: #334155;"><?php echo $orderpayment['OrderDate']; ?></span>
                                </div>
                                <div style="flex: 1;">
                                    <strong>Status:</strong><br>
                                    <span class="status-badge <?php echo ($orderpayment['OrderStatus'] == 'Out for delivery' || $orderpayment['OrderStatus'] == 'Delivered') ? 'status-completed' : 'status-pending'; ?>">
                                        <?php echo $orderpayment['OrderStatus']; ?>
                                    </span>
                                </div>
                                <div style="flex: 1;">
                                    <strong>Payment Method:</strong><br>
                                    <span style="color: #334155;"><?php echo $orderpayment['PaymentMethod']; ?></span>
                                </div>
                            </div>
                        </td>
                    </tr>

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
                                <div>
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
                    <tr class="grand-total-row">
                        <td colspan="5" class="grand-total-label">Grand Total:</td>
                        <td class="grand-total-value">RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
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