<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$orders_query = "SELECT o.OrderID, o.CustName 
                 FROM orderpayment o 
                 LEFT JOIN shipping s ON o.OrderID = s.OrderID 
                 WHERE s.OrderID IS NULL";
$orders_result = $conn->query($orders_query);

$shipping_query = "SELECT s.*, o.CustName, o.CustEmail FROM shipping s JOIN orderpayment o ON s.OrderID = o.OrderID";
$shipping_result = $conn->query($shipping_query);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $shipping_query = "SELECT s.*, o.CustName, o.CustEmail FROM shipping s JOIN orderpayment o ON s.OrderID = o.OrderID WHERE s.TrackingNum LIKE ? OR s.ShippingAddress LIKE ? OR o.CustName LIKE ?";
    $stmt = $conn->prepare($shipping_query);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $shipping_result = $stmt->get_result();
}

if (isset($_POST['update_shipping'])) {
    $shipping_id = intval($_POST['shipping_id']);
    $status = trim($_POST['status']);
    $actual_date = ($status == "Delivered") ? trim($_POST['actual_date']) : null;
    
    if ($status == "Pending" || $status == "Processing") {
        $method = null;
        $tracking = null;
    } else {
        $method = trim($_POST['method']);
        $tracking = trim($_POST['tracking']);
        
        if (empty($method)) {
            echo "<script>alert('Shipping method is required!'); window.location.href='shipping.php';</script>";
            exit();
        }
        if (empty($tracking)) {
            echo "<script>alert('Tracking number is required!'); window.location.href='shipping.php';</script>";
            exit();
        }
        if ($status == "Delivered" && empty($actual_date)) {
            echo "<script>alert('Actual delivery date is required for Delivered status!'); window.location.href='shipping.php';</script>";
            exit();
        }
    }
    
    $update_query = "UPDATE shipping SET TrackingNum = ?, ShippingMethod = ?, ShippingStatus = ?, ActualDeliveryDate = ? WHERE ShippingID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $tracking, $method, $status, $actual_date, $shipping_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Shipping details updated successfully!'); window.location.href='shipping.php';</script>";
    } else {
        echo "<script>alert('Failed to update shipping details.'); window.location.href='shipping.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['delete_shipping'])) {
    $shipping_id = intval($_POST['shipping_id']);
    
    $delete_query = "DELETE FROM shipping WHERE ShippingID = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $shipping_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Shipping details deleted successfully!'); window.location.href='shipping.php';</script>";
    } else {
        echo "<script>alert('Failed to delete shipping details.'); window.location.href='shipping.php';</script>";
    }
    exit();
}

if (isset($_POST['add_shipping'])) {
    $order_id = intval($_POST['order_id']);
    $address = trim($_POST['address']);
    $status = trim($_POST['status']);
    $est_date = trim($_POST['est_date']);
    $admin_id = intval($_POST['admin_id']);

    // Check if order exists and doesn't already have shipping
    $check_order = $conn->prepare("SELECT o.OrderID 
                                  FROM orderpayment o 
                                  LEFT JOIN shipping s ON o.OrderID = s.OrderID 
                                  WHERE o.OrderID = ? AND s.OrderID IS NULL");
    $check_order->bind_param("i", $order_id);
    $check_order->execute();
    $check_order->store_result();
    
    if ($check_order->num_rows == 0) {
        echo "<script>alert('Error: Invalid order selected or shipping already exists for this order!'); window.location.href='shipping.php';</script>";
        exit();
    }

    $today = date('Y-m-d');
    if ($est_date < $today) {
        echo "<script>alert('Error: Estimated delivery date must be today or later!'); window.location.href='shipping.php';</script>";
        exit();
    }

    if ($status == "Pending" || $status == "Processing") {
        $method = null;
        $tracking = null;
    } else {
        $method = trim($_POST['method']);
        $tracking = trim($_POST['tracking']);
    }

    $insert_query = "INSERT INTO shipping (OrderID, ShippingAddress, ShippingMethod, TrackingNum, ShippingStatus, EstimateDeliveryDate, AdminID) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isssssi", $order_id, $address, $method, $tracking, $status, $est_date, $admin_id);

    if ($stmt->execute()) {
        echo "<script>alert('Shipping details added successfully!'); window.location.href='shipping.php';</script>";
    } else {
        echo "<script>alert('Failed to add shipping details.'); window.location.href='shipping.php';</script>";
    }
    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Management</title>
    <link rel='stylesheet' href='shipping.css'>
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
            <h2>Shipping Record</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by tracking, address or customer" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>

            <div class="add">
                <button onclick="openAddModal()" class="add_btn">Add Record</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Shipping ID</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Method</th>
                        <th>Tracking</th>
                        <th>Est. Delivery</th>
                        <th>Actual Delivery</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($shipping_result && $shipping_result->num_rows > 0): ?>
                        <?php while ($shipping = $shipping_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $shipping['ShippingID']; ?></td>
                                <td><?php echo $shipping['OrderID']; ?></td>
                                <td><?php echo $shipping['CustName']; ?></td>
                                <td><?php echo $shipping['ShippingAddress']; ?></td>
                                <td><?php echo $shipping['ShippingMethod'] ?: "N/A"; ?></td>
                                <td><?php echo $shipping['TrackingNum'] ?: "N/A"; ?></td>
                                <td><?php echo $shipping['EstimateDeliveryDate']; ?></td>
                                <td><?php echo $shipping['ActualDeliveryDate'] ?: "N/A"; ?></td>
                                <td><?php echo $shipping['ShippingStatus']; ?></td>
                                <td>
                                    <button name="edit_shipping" onclick='editShipping(<?php echo json_encode($shipping["ShippingID"]); ?>, <?php echo json_encode($shipping["ShippingStatus"]); ?>, <?php echo json_encode($shipping["ShippingMethod"]); ?>, <?php echo json_encode($shipping["TrackingNum"]); ?>, <?php echo json_encode($shipping["ActualDeliveryDate"]); ?>)'>Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="shipping_id" value="<?php echo $shipping['ShippingID']; ?>">
                                        <button type="submit" name="delete_shipping" onclick="return confirm('Are you sure you want to delete this shipping record?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: red;"><b>No shipping records found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Shipping</h3>
            <form method="POST">
                <input type="hidden" name="admin_id" value="<?php echo $_SESSION['AdminID']; ?>">
                
                <label>Order ID:</label>
                <?php if ($orders_result->num_rows > 0): ?>
                    <select name="order_id" required>
                        <option value="">Select Order</option>
                        <?php 
                        // Reset pointer to beginning of result set
                        $orders_result->data_seek(0);
                        while ($order = $orders_result->fetch_assoc()): ?>
                            <option value="<?php echo $order['OrderID']; ?>"><?php echo $order['OrderID'] . ' - ' . $order['CustName']; ?></option>
                        <?php endwhile; ?>
                    </select>
                <?php else: ?>
                    <p style="color: red;">No orders available for shipping (all orders may already have shipping records)</p>
                <?php endif; ?>
                
                <label>Shipping Address:</label>
                <input type="text" name="address" required>
                
                <label>Status:</label>
                <select name="status" required onchange="toggleFields(this, 'add')">
                    <option value="">Select Shipping Status</option>  
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Failed Delivery">Failed Delivery</option>
                    <option value="Returned">Returned</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <label>Shipping Method:</label>
                <select name="method" id="add-method">
                    <option value="">Select Shipping Method</option>
                    <option value="PosLaju">PosLaju</option>
                    <option value="J&T Express">J&T Express</option>
                    <option value="GDex">GDex</option>
                    <option value="DHL Express">DHL Express</option>
                    <option value="Ninja Van">Ninja Van</option>
                    <option value="Lalamove / GrabExpress">Lalamove / GrabExpress</option>
                </select>

                <label>Tracking Number:</label>
                <input type="text" name="tracking" id="add-tracking">
                
                <label>Estimate Delivery Date:</label>
                <input type="date" name="est_date" required min="<?php echo date('Y-m-d'); ?>">

                <div class="add_div">
                    <button type="submit" name="add_shipping" <?php echo ($orders_result->num_rows == 0) ? 'disabled' : ''; ?>>Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rest of your code remains the same -->
    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Update Shipping</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="shipping_id" id="editShippingId">
                
                <label>Status:</label>
                <select name="status" id="editStatus" required onchange="toggleFields(this, 'update')">
                    <option value="">Select Shipping Status</option>    
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Failed Delivery">Failed Delivery</option>
                    <option value="Returned">Returned</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <label>Shipping Method:</label>
                <select name="method" id="editMethod" required>
                    <option value="">Select Shipping Method</option>
                    <option value="PosLaju">PosLaju</option>
                    <option value="J&T Express">J&T Express</option>
                    <option value="GDex">GDex</option>
                    <option value="DHL Express">DHL Express</option>
                    <option value="Ninja Van">Ninja Van</option>
                    <option value="Lalamove / GrabExpress">Lalamove / GrabExpress</option>
                </select>

                <label>Tracking Number:</label>
                <input type="text" name="tracking" id="editTracking" placeholder="Tracking Number" required>
                
                <label>Actual Delivery Date:</label>
                <input type="date" name="actual_date" id="editActualDate" disabled>
                
                <div class="upd_div">
                    <button type="submit" name="update_shipping">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFields(selectElement, formType) {
            const methodField = document.getElementById(formType === 'add' ? 'add-method' : 'editMethod');
            const trackingField = document.getElementById(formType === 'add' ? 'add-tracking' : 'editTracking');
            const actualDateField = document.getElementById('editActualDate');

            if (selectElement.value === "Pending" || selectElement.value === "Processing") {
                methodField.disabled = true;
                methodField.required = false;
                trackingField.disabled = true;
                trackingField.required = false;
                methodField.value = "";
                trackingField.value = "";
                if (actualDateField) {
                    actualDateField.disabled = true;
                    actualDateField.required = false;
                    actualDateField.value = "";
                }
            } else {
                methodField.disabled = false;
                methodField.required = true;
                trackingField.disabled = false;
                trackingField.required = true;
                if (actualDateField && selectElement.value === "Delivered") {
                    actualDateField.disabled = false;
                    actualDateField.required = true;
                } else if (actualDateField) {
                    actualDateField.disabled = true;
                    actualDateField.required = false;
                    actualDateField.value = "";
                }
            }
        }

        function editShipping(shippingId, status, method, tracking, actualDate) {
            document.getElementById('editShippingId').value = shippingId;
            document.getElementById('editStatus').value = status;
            document.getElementById('editMethod').value = method || '';
            document.getElementById('editTracking').value = tracking || '';
            document.getElementById('editActualDate').value = actualDate || '';
            
            toggleFields(document.getElementById('editStatus'), 'update');
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
    </script>
</body>
</html>