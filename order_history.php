<?php
include 'config.php';
include 'header.php';

// Check if the customer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$custID = $_SESSION['user_id'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT o.OrderID, o.ReceiverName, o.ReceiverContact, o.StreetAddress, o.City, o.Postcode, o.State, 
        o.OrderDate, o.OrderStatus, o.TotalPrice 
        FROM orderpayment o 
        WHERE o.CustID = :custid";

if (!empty($search_query)) {
    $sql .= " AND (o.ReceiverName LIKE :search OR o.ReceiverContact LIKE :search)";
}

$sql .= " ORDER BY o.OrderDate DESC";
$stmt = $conn->prepare($sql);

// Bind parameters
$stmt->bindParam(':custid', $custID, PDO::PARAM_INT);

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$order_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="order_history.css">
</head>
<body>
    <div class="history-container">
        <div class="main-content">
            <h2>Order History</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or contact number" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th colspan="3">Receiver Info</th>
                        <th>Order Time</th>
                        <th>Order Status</th>
                        <th>Total Price (RM)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($order_result && count($order_result) > 0): ?>
                        <?php foreach ($order_result as $orderpayment): ?>
                            <tr>
                                <td><?php echo $orderpayment['OrderID']; ?></td>
                                <td colspan="3" style="line-height: 1.75;">
                                    <strong><?php echo $orderpayment['ReceiverName']; ?></strong><br>
                                    <?php echo $orderpayment['ReceiverContact']; ?><br>
                                    <?php
                                    $full_address = trim($orderpayment['StreetAddress'] . ', ' . $orderpayment['Postcode'] . ' ' . $orderpayment['City'] . ', ' . $orderpayment['State']);
                                    echo htmlspecialchars($full_address);
                                    ?>
                                </td>
                                <td><?php echo $orderpayment['OrderDate']; ?></td>
                                <td class="<?php echo ($orderpayment['OrderStatus'] == 'Out for delivery' || $orderpayment['OrderStatus'] == 'Delivered') ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $orderpayment['OrderStatus']; ?>
                                </td>                               
                                <td>RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
                                <td>
                                    <button name="view_details" class="view_details" onclick="window.location.href='order_details.php?order_id=<?php echo $orderpayment['OrderID']; ?>'">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: red;"><b>No orders found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php 
include 'footer.php';
?>
