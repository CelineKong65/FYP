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
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.container {
    margin: 0 auto;
    flex: 1;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    width: 90%;
    margin-top: 80px;
    min-height: 700px;
    max-height: fit-content;
    border-radius: 20px;
    transform: translateY(50px);
}

.main-content {
    flex-grow: 1;
    padding: 20px;
    margin: 50px;
    background-color: #ffffff;
    border-radius: 10px;
    height: fit-content;
}

button {
    padding: 10px;
    background-color: #1e3a8a;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 10px;
    font-size: 13px;
    width: 80px;
}

button[name="print"]{
    background-color: #ffc107;
    color: white;
}

button[name="print"]:hover{
    background-color: #e0a800d1;
}

button[name="back"]:hover{
    background-color:rgb(245, 34, 55);
    transition: 0.3s ease;
}

button[name="back"]{
    background-color: #c82333;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
    margin-top: 10px;
}

table, th, td {
    font-size: 15px;
    border: 1px solid #1e3a8a;
}


th, td {
    padding: 12px;
    text-align: left;
}

th {
    background-color: #1e3a8a;
    color: white;
}

tr:nth-child(even) {
    background-color: #e3f2fd;
}

table tr:hover {
    background-color:rgb(237, 236, 236);
}

.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
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

h3{
    font-size: 20px;
}

.cust-info, .rec-info{
    font-size: 15px;
}

.rec-info, .order_item{
    border-top: 1px solid #ddd; 
}

p{
    line-height: 1.5;
}

.status-completed {
    color: #05ac2c;
}

.status-pending {
    color: red;
}

.total_price {
    margin-top: 30px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 20px 30px;
    background-color: #f1f5fb;
    border: 1px solid #dbe2ef;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(69, 188, 248, 0.35);
    gap: 15px;
    font-size: 18px;
    font-weight: bold;
    color: #1e3a8a;
}

.total_price p {
    margin: 0;
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
                <p>Name: <?php echo $customer['CustName']; ?></p>
                <p>Email: <?php echo $customer['CustEmail']; ?></p>
            </div>
            <div class="rec-info">
                <h3>Receiver Information</h3>
                <p><b>Name:</b> <?php echo $orderpayment['ReceiverName']; ?></p>
                <p><b>Contact Number:</b> <?php echo $orderpayment['ReceiverContact']; ?></p>
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
                            <th colspan="2">Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;

                        while ($orderdetails = $order_details->fetch_assoc()): 
                            $total_item = $orderdetails['Price'] * $orderdetails['Quantity'];
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td colspan="2"><?php echo $orderdetails['ProductName']; ?><br>Size: <?php echo $orderdetails['Size']; ?></td>
                            <td>RM <?php echo number_format($orderdetails['Price'], 2); ?></td>
                            <td><?php echo $orderdetails['Quantity']; ?></td>
                            <td>RM <?php echo number_format($total_item, 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table> 
            </div>

            <div class="total_price">
                <p>TOTAL</p>
                <p><strong>RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></strong></p>
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