<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar.css">
</head>
<body>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="customer_view.php">Customer List</a></li>
        <li><a href="admin_view.php">Admin List</a></li>
        <li><a href="category_view.php">Category List</a></li>
        <li><a href="brand_view.php">Brand List</a></li>
        <li><a href="product_view.php">Product List</a></li>
        <li><a href="order_view.php">Order List</a></li>
        <li><a href="contact_view.php">Contact Records</a></li>

        <!-- Feedback Menu with Submenu -->
        <li class="has-submenu">
            <a href="#">Feedback Records</a>
            <ul class="submenu">
                <li><a href="feedback_view.php">Customer Feedback</a></li>
                <li><a href="feedback_product.php">Product Feedback</a></li>
            </ul>
        </li>
        <li><a href="sales_report.php">Sales Report</a></li>
        <li><a href="profile.php">My Profile</a></li>
    </ul>
</body>
</html>
