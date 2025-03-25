<?php
session_start();
include 'header.php';
include 'config.php';

// Check if the customer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$custID = $_SESSION['user_id'];

// Fetch order history for the logged-in customer
$sql = "SELECT op.OrderID, op.OrderDate, op.TotalPrice, od.ProductName, od.Color, od.Size, od.Quantity 
        FROM orderpayment op
        JOIN orderdetails od ON op.OrderID = od.OrderID
        WHERE op.CustID = :custID
        ORDER BY op.OrderDate DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':custID', $custID, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize orders into an array grouped by OrderID
$groupedOrders = [];
foreach ($orders as $row) {
    $groupedOrders[$row['OrderID']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }
        .order-history {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 100px;
            margin-bottom: 100px;
        }
        .order {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .order h3 {
            margin-top: 0;
        }
        .order-details {
            margin-left: 20px;
        }
        .order-details p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="order-history">
        <h1>Order History</h1>
        <?php if (empty($groupedOrders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <?php foreach ($groupedOrders as $orderID => $orderItems): ?>
                <div class="order">
                    <h3>Order ID: <?php echo htmlspecialchars($orderID); ?></h3>
                    <p>Order Date: <?php echo htmlspecialchars($orderItems[0]['OrderDate']); ?></p>
                    <p>Total Price: RM <?php echo number_format($orderItems[0]['TotalPrice'], 2); ?></p>
                    <div class="order-details">
                        <h4>Items:</h4>
                        <?php foreach ($orderItems as $item): ?>
                            <p><?php echo htmlspecialchars($item['ProductName']); ?> - <?php echo htmlspecialchars($item['Color']); ?> - <?php echo htmlspecialchars($item['Size']); ?> - Quantity: <?php echo htmlspecialchars($item['Quantity']); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
include 'footer.php'; 
?>
