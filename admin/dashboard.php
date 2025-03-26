<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$user_query = "SELECT COUNT(*) AS total_users FROM customer";
$user_result = $conn->query($user_query);
$total_users = $user_result->fetch_assoc()['total_users'];

$category_query = "SELECT COUNT(*) AS total_categories FROM category";
$category_result = $conn->query($category_query);
$total_categories = $category_result->fetch_assoc()['total_categories'];

$product_query = "SELECT COUNT(*) AS total_products FROM product";
$product_result = $conn->query($product_query);
$total_products = $product_result->fetch_assoc()['total_products'];

$order_query = "SELECT COUNT(*) AS total_orders FROM orderpayment";
$order_result = $conn->query($order_query);
$total_orders = $order_result->fetch_assoc()['total_orders'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
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

        .dashboard-table {
            width: 100%;
            max-width: 900px;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        td{
            padding: 20px;
        }

        .card {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.17);
            text-align: center;
            min-height: 100px;
            max-width: 400px;
        }

        h3 {
            font-size: 18px;
            margin: 10px 0;
            color: black;
        }

        p {
            font-size: 16px;
            color: #0077b6;
            font-weight: bold;
            font-size: 20px;
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
            <h2>Dashboard</h2>

            <table class="dashboard-table">
                <tr>
                <td>
                    <div class="card" onclick="window.location.href='customer_view.php'" style="cursor: pointer;">
                        <h3>Total Users</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                </td>
                    <td>
                    <div class="card" onclick="window.location.href='category_view.php'" style="cursor: pointer;">
                        <h3>Total Categories</h3>
                        <p><?php echo $total_categories; ?></p>
                    </div>
                </td>
                </tr>
                <tr>
                    <td>
                        <div class="card" onclick="window.location.href='product_view.php'" style="cursor: pointer;">
                            <h3>Total Products</h3>
                            <p><?php echo $total_products; ?></p>
                        </div>
                    </td>
                    <td>
                        <div class="card" onclick="window.location.href='order_view.php'" style="cursor: pointer;">
                            <h3>Total Orders</h3>
                            <p><?php echo $total_orders; ?></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
