<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$ordersPerPage = 10; // Number of orders per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $ordersPerPage;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$count_query = "SELECT COUNT(*) FROM orderpayment o";

$order_query = "
    SELECT o.OrderID, o.ReceiverName, o.ReceiverContact, o.StreetAddress, o.City, o.Postcode, o.State, 
           o.OrderDate, o.OrderStatus, o.TotalPrice 
    FROM orderpayment o 
";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_query)) {
    $where_clauses[] = "(o.ReceiverName LIKE ? OR o.ReceiverContact LIKE ? OR o.OrderID LIKE ?)";
    $searchTerm = '%' . $search_query . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if (!empty($where_clauses)) {
    $order_query .= " WHERE " . implode(" AND ", $where_clauses);
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$order_query .= " ORDER BY o.OrderDate DESC";

$order_query .= " LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $ordersPerPage;
$params[] = $offset;

$stmt_count = $conn->prepare($count_query);
if (!empty($where_clauses)) {
    $count_types = substr($types, 0, -2);
    $count_params = array_slice($params, 0, -2);
    
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$totalOrders = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$totalPages = ceil($totalOrders / $ordersPerPage);

$stmt = $conn->prepare($order_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result === false) {
    die("Error getting results: " . $conn->error);
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
        echo "<script>alert('Order status updated successfully!'); window.location.href='order_view.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to update order status.'); window.location.href='order_view.php';</script>";
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
                <input type="text" name="search" class="search" placeholder="Search by receiver name" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th colspan="3">Receiver Info</th>
                        <th>Order Time</th>
                        <th style="text-align: center;">Order Status</th>
                        <th style="text-align: center;">Total Price</th>
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
                                <td class="<?php echo ($orderpayment['OrderStatus'] == 'Out for delivery' || $orderpayment['OrderStatus'] == 'Delivered') ? 'status-completed' : 'status-pending'; ?>" style="text-align: center;">
                                    <?php echo $orderpayment['OrderStatus']; ?>
                                </td>                               
                                <td style="text-align: center;">RM <?php echo number_format($orderpayment['TotalPrice'], 2); ?></td>
                                <td>
                                    <?php if ($orderpayment['OrderStatus'] === 'Delivered'): ?>
                                        <button type="button" name="change_status" class="btn-disabled" onclick="editStatus('<?php echo $orderpayment['OrderID']; ?>', '<?php echo $orderpayment['OrderStatus']; ?>')">Edit Status</button>
                                    <?php else: ?>
                                        <button type="button" name="change_status" onclick="editStatus('<?php echo $orderpayment['OrderID']; ?>', '<?php echo $orderpayment['OrderStatus']; ?>')">Edit Status</button>
                                    <?php endif; ?>
                                    <button name="view_details" onclick="window.location.href='order_details.php?order_id=<?php echo $orderpayment['OrderID']; ?>'">View Details</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: red;"><b>No orders found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($selected_category) ? '&category=' . $selected_category : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($selected_category) ? '&category=' . $selected_category : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($selected_category) ? '&category=' . $selected_category : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page">Next</a>
                <?php endif; ?>
            </div>
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
            if (currentStatus === 'Delivered') return;
            document.getElementById("editOrderID").value = id;
            document.getElementById("editOrderStatus").value = currentStatus;
            document.getElementById("editModal").style.display = "block";
        }

        function closeEdit() {
            document.getElementById("editModal").style.display = "none";
        }
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const searchForm = document.querySelector('.search');
            
            if (searchInput && searchForm) {
                searchInput.addEventListener('input', function() {
                    if (this.value.trim() === '') {
                        searchForm.submit();
                    }
                });
            }
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById("editModal")) {
                closeEdit();
            }
        }    </script>

</body>
</html>