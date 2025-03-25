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
        <li><a href="shipping.php">Shipping Record</a></li>
        <li><a href="profile.php">My Profile</a></li>
    </ul>
</body>
</html>
