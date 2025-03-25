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

button[name="view_details"]{
    color: black;
    background-color: #ffc107;
}

button[name="view_details"]:hover{
    background-color: #e0a800d1;
}

button[name="view_details"] a{
    text-decoration: none;
    color: black;
}

button[name="delete_order"]{
    background-color: red;
}

button[name="delete_order"]:hover{
    background-color: #c82333;
}

.add_div,.upd_div{
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.add{
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
}

.add_btn{
    background-color: #28a745;
}

.add_btn:hover {
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
    background-color: #f0f4ff;
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

#editModal, #addModal {
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

.edit-content,.add-content {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    margin: auto;
    margin-top: 150px;
}

#editModal h3, #addModal h3 {
    margin-top: 0;
    color: #1e3a8a;
    font-size: 25px;
    text-align: center;
}

#editModal label, #addModal label {
    display: block;
    margin-bottom: 5px;
    color: #1e3a8a;
    font-weight: bold;
    font-size: 15px;
}

#editModal input, #editModal select, #addModal input, #addModal select {
    width: 95%;
    padding: 8px;
    margin-bottom: 13px;
    border: 1px solid #93c5fd;
    border-radius: 4px;
    font-size: 13px;
}

#editModal select, #addModal select{
    width: 100%;
}

#editModal button[type="submit"], #addModal button[type="submit"] {
    background-color: #1e3a8a;
}

#editModal button[type="submit"]:hover, #addModal button[type="submit"]:hover {
    background-color: #1d4ed8;
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
                                    <button name="view_details">
                                        <a href="order_details.php?order_id=<?php echo $order['OrderID']; ?>" class="view-details-btn">View Details</a>
                                    </button>
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