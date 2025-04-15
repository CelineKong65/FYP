<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$order_query = "SELECT o.OrderID, o.ReceiverName, o.ReceiverContact, o.StreetAddress, o.City, o.Postcode, o.State, 
                o.OrderDate, o.OrderStatus, o.TotalPrice 
                FROM orderpayment o 
                ORDER BY o.OrderDate DESC";

if (!empty($search_query)) {
    $order_query .= " WHERE o.ReceiverName LIKE ? OR o.ReceiverContact LIKE ? ORDER BY o.OrderDate DESC";
    $search_param = "%$search_query%";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $order_result = $stmt->get_result();
} else {
    $order_result = $conn->query($order_query);
}

if (isset($_POST['edit_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);

    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Out for delivery', 'Delivered'];
    if (!in_array($new_status, $allowed_statuses)) {
        die("Invalid order status");
    }

    $update_stmt = $conn->prepare("UPDATE orderpayment SET OrderStatus = ? WHERE OrderID = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    $update_stmt->execute();

    if ($update_stmt->execute()) {
        $_SESSION['message'] = 'Order status updated successfully!';
        header("Location: order_view.php");
        exit();
    } else {
        $_SESSION['message'] = 'Failed to update order status';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List</title>
    <link rel='stylesheet' href='order_view.css'>
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
            <h2>Order List</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
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
                    <?php if ($order_result && $order_result->num_rows > 0): ?>
                        <?php while ($orderpayment = $order_result->fetch_assoc()): ?>
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
                                <td class="<?php echo ($orderpayment['OrderStatus'] == 'Out for delivery') ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $orderpayment['OrderStatus']; ?>
                                </td>                               
                                <td>RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
                                <td>
                                    <button type="button" name="change_status" onclick="editStatus('<?php echo $orderpayment['OrderID']; ?>', '<?php echo $orderpayment['OrderStatus']; ?>')">Edit Status</button>
                                    <button name="view_details" onclick="window.location.href='order_details.php?order_id=<?php echo $orderpayment['OrderID']; ?>'">View Details</button>
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

    <div id="editModal" class="edit">
        <div class="edit-content">
            <span class="close" onclick="closeEdit()">&times;</span>
            <h3>Edit Order Status</h3>
            <form method="POST" action="" onsubmit="return validateEditForm()">
                <input type="hidden" name="order_id" id="editOrderID">
                <label for="new_status">New status:</label>
                <select name="new_status" id="editOrderStatus" required>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Out for delivery">Out for delivery</option>
                    <option value="Delivered">Delivered</option>
                </select>
                <div class="submit_btn">
                    <button type="submit" name="edit_status">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editStatus(id, currentStatus) {
            document.getElementById("editOrderID").value = id;
            document.getElementById("editOrderStatus").value = currentStatus;
            document.getElementById("editModal").style.display = "block";
        }

        function closeEdit() {
            document.getElementById("editModal").style.display = "none";
        }
    </script>

</body>
</html>