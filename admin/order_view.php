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
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.header {
    margin-bottom: 50px;
}

.container {
    margin-top: 50px;
    display: flex;
    flex: 1;
    margin-left: 250px;
}

.sidebar {
    width: 220px;
    background-color: #0077b6;
    padding-top: 30px;
    text-align: center;
    border-radius: 20px;
    margin: 30px;
    height: 650px;
    margin-top: 150px;
    position: fixed;
    left: 0;
    top: 0; 
}

.main-content {
    flex-grow: 1;
    padding: 20px;
    margin: 10px;
    background-color: #ffffff;
    border-radius: 10px;
    position: relative;
}

h2 {
    color: #1e3a8a;
    font-size: 40px;
    text-align: center;
    margin-bottom: 50px;
}

.message {
    padding: 10px;
    color: #1e3a8a;
    border-radius: 4px;
    margin-bottom: 20px;
}

form.search {
    display: flex;
    justify-content: flex-end;
    gap:10px
}

form.editForm,.addForm{
    margin-bottom: 20px;
}

.editForm input,.addForm input{
    margin-right: 50px;
}

.search input {
    width: 200px;
    padding: 8px;
    font-size: 12px;
    margin-bottom: 10px;
    border: 1px solid #0A2F4F;
    border-radius: 4px;
}

button {
    padding: 10px 16px;
    background-color: #1e3a8a;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 10px;
    font-size: 13px;
}

button.search{
    width: 95px;
}

button:hover {
    background-color: #1d4ed8;
}

.status-completed {
    color: #05ac2c;
}

.status-pending {
    color: red;
}

button[name="change_status"]{
    color: black;
    background-color: #ffc107;
    width: 104px;
}

button[name="change_status"]:hover{
    background-color: #e0a800d1;
}

button[name="view_details"]{
    background-color: #05ac2c;
}

button[name="view_details"]:hover{
    background-color: #218838;
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

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}

.close:hover{
    color: red;
}

#editModal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}

.edit-content{
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    margin: auto;
    margin-top: 250px;
}

#editModal h3{
    margin-top: 0;
    color: #1e3a8a;
    font-size: 25px;
    text-align: center;
}

#editModal label{
    display: block;
    margin-bottom: 5px;
    color: #1e3a8a;
    font-weight: bold;
    font-size: 15px;
}

#editModal select{
    width: 100%;
    padding: 8px;
    margin-bottom: 13px;
    border: 1px solid #93c5fd;
    border-radius: 4px;
    font-size: 13px;
}

#editModal button[type="submit"]{
    background-color: #1e3a8a;
}

#editModal button[type="submit"]:hover{
    background-color: #1d4ed8;
}

.submit_btn {
    display: flex;
    justify-content: flex-end;
    padding-top: 15px;
}
    </style>
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