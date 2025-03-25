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

// Get order header information
$order_header_query = "SELECT * FROM orderpayment WHERE OrderID = ?";
$stmt = $conn->prepare($order_header_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_header = $stmt->get_result()->fetch_assoc();

// Get order details
$order_details_query = "SELECT * FROM orderdetails WHERE OrderID = ?";
$stmt = $conn->prepare($order_details_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_details = $stmt->get_result();
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
                <button name="back"><a href="order_view.php" class="view-details-btn">Back to Order List</a></button>
                <h2>Order Details - #<?php echo $order_id; ?></h2>
                <button name="print" onclick="printOrder()">Print</button>
            </div>
            
            <div class="cust-info">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?php echo $order_header['CustName']; ?></p>
                <p><strong>Email:</strong> <?php echo $order_header['CustEmail']; ?></p>
                <p><strong>Address:</strong> <?php echo $order_header['CustAddress']; ?></p>
                <p><strong>Order Date:</strong> <?php echo $order_header['OrderDate']; ?></p>
                <p><strong>Total Price:</strong> RM <?php echo number_format($order_header['TotalPrice'], 2); ?></p>
            </div>

            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($order_details && $order_details->num_rows > 0): ?>
                        <?php while ($item = $order_details->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $item['ProductName']; ?></td>
                                <td><?php echo $item['Color']; ?></td>
                                <td><?php echo $item['Size']; ?></td>
                                <td><?php echo $item['Quantity']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: red;"><b>No items found for this order.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>