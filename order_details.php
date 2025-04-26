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
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.order-details-container {
    margin: 0 auto;
    flex: 1;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    width: 90%;
    margin-top: 10px;
    border-radius: 20px;
    transform: translateY(50px);
    margin-bottom: 100px;
    margin-top: 130px;
}

.main-content {
    flex-grow: 1;
    padding: 20px;
    margin: 50px;
    background-color: #ffffff;
    border-radius: 10px;
    height: fit-content;
}

button[name="print"], button[name="back"]{
    padding: 10px;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 10px;
    font-size: 15px;
    width: 80px;
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

table {
    width: 100%;
    font-size: 15px;
    margin-top: 10px;
    border-collapse: separate;
}

table, th, td {
    font-size: 15px;
    border: 1px rgba(30, 59, 138, 0.44) solid;

}

th, td {
    padding: 12px;
    text-align: left;
}

th {
    background-color:#1e3a8a;
    color: white;
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
    margin-bottom: 10px;
    margin-top: 10px;
}

.cust-info, .rec-info{
    font-size: 15px;
    margin-bottom: 10px;
}

.cust-info p, .rec-info p {
    margin-bottom: 10px;
}

.rec-info, .order_item{
    border-top: 1px solid #ddd; 
    margin-bottom: 10px;
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

table td:last-child {
    text-align: right;
}

table tr:last-child td {
    font-size: 17px;
    background-color: #f0f8ff
}

img{
    width: 100px;
    border: none;
}

.highlight-row {
    background-color: #f0f8ff;
    font-weight: bold;
}

.center
{
    text-align: center;
}   

@media print {
    body * {
        visibility: hidden;
    }

    .order-details-container, .order-details-container * {
        visibility: visible;
    }

    .order-details-container {
        position: absolute;
        left: 0;
        top: -150px;
        width: 100%;
    }

    button {
        display: none !important;
    }
}
    </style>
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
