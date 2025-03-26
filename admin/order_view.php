<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$order_query = "SELECT o.OrderID, o.CustName, o.CustEmail, o.CustAddress, o.OrderDate, o.TotalPrice 
                FROM orderpayment o";

if (!empty($search_query)) {
    $order_query .= " WHERE o.CustName LIKE ? OR o.CustEmail LIKE ?";
    $search_param = "%$search_query%";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $order_result = $stmt->get_result();
} else {
    $order_result = $conn->query($order_query);
}

if (isset($_POST['delete_order'])) {
    $order_id = intval($_POST['order_id']);
    
    $delete_shipping = "DELETE FROM shipping WHERE OrderID = ?";
    $stmt = $conn->prepare($delete_shipping);
    $stmt->bind_param("i", $order_id);
    $shipping_deleted = $stmt->execute();
    
    $delete_details = "DELETE FROM orderdetails WHERE OrderID = ?";
    $stmt = $conn->prepare($delete_details);
    $stmt->bind_param("i", $order_id);
    $details_deleted = $stmt->execute();
    
    $delete_payment = "DELETE FROM orderpayment WHERE OrderID = ?";
    $stmt = $conn->prepare($delete_payment);
    $stmt->bind_param("i", $order_id);
    $payment_deleted = $stmt->execute();
    
    if ($payment_deleted) {
        echo "<script>alert('Order deleted successfully!'); window.location.href='order_view.php';</script>";
    } else {
        echo "<script>alert('Failed to delete order.'); window.location.href='order_view.php';</script>";
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List</title>
    <link rel='stylesheet' href='order_list.css'>
</head>
<body>
    <div class="header">
        <?php include 'header.php'; ?>
    </div>

    <div class="container">
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="main-content">
            <h2>Order History</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Order Date</th>
                        <th>Total Price (RM)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($order_result && $order_result->num_rows > 0): ?>
                        <?php while ($order = $order_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $order['OrderID']; ?></td>
                                <td><?php echo $order['CustName']; ?></td>
                                <td><?php echo $order['CustEmail']; ?></td>
                                <td><?php echo $order['CustAddress']; ?></td>
                                <td><?php echo $order['OrderDate']; ?></td>
                                <td>RM <?php echo number_format($order['TotalPrice'], 2); ?></td>
                                <td>
                                <button name="view_details" onclick="window.location.href='order_details.php?order_id=<?php echo $order['OrderID']; ?>'">View Details</button>
                                    <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                            <button type="submit" name="delete_order" class="delete-btn" onclick="return confirm('Are you sure you want to delete this order record?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: red;"><b>No orders found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>