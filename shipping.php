<?php
include 'config.php'; 

// Fetch all shipping records
$stmt = $conn->prepare("SELECT * FROM shipping");
$stmt->execute();
$shippings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $order_id = $_POST['order_id'];
        $cust_id = $_POST['cust_id'];
        $address = $_POST['address'];
        $status = $_POST['status'];
        $est_date = $_POST['est_date'];
        $admin_id = $_POST['admin_id'];

        // If status is "Pending" or "Processing", make shipping method and tracking number NULL
        if ($status == "Pending" || $status == "Processing") {
            $method = null;
            $tracking = null;
        } else {
            $method = $_POST['method'];
            $tracking = $_POST['tracking'];
        }

        $stmt = $conn->prepare("INSERT INTO shipping (OrderID, CustID, ShippingAddress, ShippingMethod, TrackingNum, ShippingStatus, EstimateDeliveryDate, AdminID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $cust_id, $address, $method, $tracking, $status, $est_date, $admin_id]);

        header("Location: shipping.php");
        exit;
    } elseif (isset($_POST['update'])) {
        $shipping_id = $_POST['shipping_id'];
        $status = $_POST['status'];
        $actual_date = ($_POST['status'] == "Delivered") ? $_POST['actual_date'] : null; // Allow only when Delivered

        // If status is "Pending" or "Processing", make shipping method and tracking number NULL
        if ($status == "Pending" || $status == "Processing") {
            $method = null;
            $tracking = null;
        } else {
            $method = $_POST['method'];
            $tracking = $_POST['tracking'];
        }

        $stmt = $conn->prepare("UPDATE shipping SET TrackingNum = ?, ShippingMethod = ?, ShippingStatus = ?, ActualDeliveryDate = ? WHERE ShippingID = ?");
        $stmt->execute([$tracking, $method, $status, $actual_date, $shipping_id]);

        header("Location: shipping.php");
        exit;
    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM shipping WHERE ShippingID = ?");
        $stmt->execute([$_POST['shipping_id']]);

        header("Location: shipping.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Shipping</title>
</head>
<body>
    <h2>Shipping Management</h2>
    <table border="1">
        <tr>
            <th>Shipping ID</th>
            <th>Order ID</th>
            <th>Customer ID</th>
            <th>Address</th>
            <th>Method</th>
            <th>Tracking</th>
            <th>Status</th>
            <th>Estimate Delivery Date</th>
            <th>Actual Delivery Date</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($shippings as $shipping) { ?>
            <tr>
                <td><?= $shipping['ShippingID'] ?></td>
                <td><?= $shipping['OrderID'] ?></td>
                <td><?= $shipping['CustID'] ?></td>
                <td><?= $shipping['ShippingAddress'] ?></td>
                <td><?= $shipping['ShippingMethod'] ?: "N/A" ?></td>
                <td><?= $shipping['TrackingNum'] ?: "N/A" ?></td>
                <td><?= $shipping['ShippingStatus'] ?></td>
                <td><?= $shipping['EstimateDeliveryDate'] ?></td>
                <td><?= $shipping['ActualDeliveryDate'] ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="shipping_id" value="<?= $shipping['ShippingID'] ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
    
    <h3>Add Shipping</h3>
    <form method="POST">
        <input type="text" name="order_id" placeholder="Order ID" required>
        <input type="text" name="cust_id" placeholder="Customer ID" required>
        <input type="text" name="address" placeholder="Shipping Address" required>
        
        <select name="status" required onchange="toggleFields(this)">
            <option value="Pending">Pending</option>
            <option value="Processing">Processing</option>
            <option value="Shipped">Shipped</option>
            <option value="Out for Delivery">Out for Delivery</option>
            <option value="Delivered">Delivered</option>
            <option value="Failed Delivery">Failed Delivery</option>
            <option value="Returned">Returned</option>
            <option value="Cancelled">Cancelled</option>
        </select>

        <select name="method">
            <option value="">Select Shipping Method</option>
            <option value="PosLaju">PosLaju</option>
            <option value="J&T Express">J&T Express</option>
            <option value="GDex">GDex</option>
            <option value="DHL Express">DHL Express</option>
            <option value="Ninja Van">Ninja Van</option>
            <option value="Lalamove / GrabExpress">Lalamove / GrabExpress</option>
        </select>

        <input type="text" name="tracking" placeholder="Tracking Number">
        <input type="date" name="est_date" required>
        <input type="text" name="admin_id" placeholder="Admin ID" required>
        <button type="submit" name="add">Add Shipping</button>
    </form>

    <h3>Update Shipping</h3>
    <form method="POST">
        <input type="text" name="shipping_id" placeholder="Shipping ID" required>

        <select name="status" required onchange="toggleFields(this)">
            <option value="Pending">Pending</option>
            <option value="Processing">Processing</option>
            <option value="Shipped">Shipped</option>
            <option value="Out for Delivery">Out for Delivery</option>
            <option value="Delivered">Delivered</option>
            <option value="Failed Delivery">Failed Delivery</option>
            <option value="Returned">Returned</option>
            <option value="Cancelled">Cancelled</option>
        </select>

        <select name="method">
            <option value="">Select Shipping Method</option>
            <option value="PosLaju">PosLaju</option>
            <option value="J&T Express">J&T Express</option>
            <option value="GDex">GDex</option>
            <option value="DHL Express">DHL Express</option>
            <option value="Ninja Van">Ninja Van</option>
            <option value="Lalamove / GrabExpress">Lalamove / GrabExpress</option>
        </select>

        <input type="text" name="tracking" placeholder="Tracking Number">
        <input type="date" name="actual_date" disabled>
        <button type="submit" name="update">Update Shipping</button>
    </form>

    <script>
        function toggleFields(selectElement) {
            var form = selectElement.closest("form");
            var methodField = form.querySelector("select[name='method']");
            var trackingField = form.querySelector("input[name='tracking']");
            var actualDateField = form.querySelector("input[name='actual_date']");

            if (selectElement.value === "Pending" || selectElement.value === "Processing") {
                methodField.disabled = true;
                trackingField.disabled = true;
                methodField.value = "";
                trackingField.value = "";
            } else {
                methodField.disabled = false;
                trackingField.disabled = false;
            }

            // Actual delivery date only enabled if status is "Delivered"
            actualDateField.disabled = selectElement.value !== "Delivered";
            if (actualDateField.disabled) actualDateField.value = "";
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("form select[name='status']").forEach(function(selectElement) {
                toggleFields(selectElement);
                selectElement.addEventListener("change", function() {
                    toggleFields(selectElement);
                });
            });
        });
    </script>
</body>
</html>
