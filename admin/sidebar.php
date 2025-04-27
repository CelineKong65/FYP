<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        ul {
    list-style: none;
    padding: 0;
}

ul li {
    text-align: center;
    width: 200px;
    margin: auto;
    border-radius: 10px;
    position: relative;
}

ul li a {
    color: white;
    text-decoration: none;
    display: block;
    font-size: 18px;
    transition: 0.3s;
    padding: 15px 20px;
}

ul li a:hover {
    background-color: #1e3a8a;
    border-radius: 5px;
    font-weight: bold;
}

ul li .submenu {
    display: none;
    position: absolute;
    top: -25px;
    left: 95%;
    width: 220px;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
    text-align: left;
    padding: 8px 0;
}

ul li.has-submenu:hover .submenu {
    display: block;
}

ul li .submenu li a {
    padding: 14px 20px;
    font-size: 16px;
    color: black;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    font-weight: 500;
}

ul li .submenu li:last-child a {
    border-bottom: none;
}

ul li .submenu li a:hover {
    background: #1e3a8a;
    font-weight: 600;
    color: white;
}
    </style>
</head>
<body>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="customer_view.php">Customer List</a></li>
        <li><a href="admin_view.php">Admin List</a></li>
        <li><a href="category_view.php">Category List</a></li>
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
