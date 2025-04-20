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

$product_query = "SELECT COUNT(*) AS total_products FROM product";
$product_result = $conn->query($product_query);
$total_products = $product_result->fetch_assoc()['total_products'];

$order_query = "SELECT COUNT(*) AS total_orders FROM orderpayment";
$order_result = $conn->query($order_query);
$total_orders = $order_result->fetch_assoc()['total_orders'];

$sales_query = "SELECT SUM(TotalPrice) AS total_sales FROM orderpayment";
$sales_result = $conn->query($sales_query);
$total_sales_row = $sales_result->fetch_assoc();
$total_sales = $total_sales_row['total_sales'] ?? 0;

$monthly_sales_query = "SELECT * FROM (
    SELECT 
        DATE_FORMAT(OrderDate, '%Y-%m') AS month, 
        SUM(TotalPrice) AS monthly_sales 
    FROM orderpayment 
    GROUP BY DATE_FORMAT(OrderDate, '%Y-%m') 
    ORDER BY month DESC 
    LIMIT 6
) AS recent_months
ORDER BY month ASC";

$monthly_sales_result = $conn->query($monthly_sales_query);
$monthly_sales_data = [];
while ($row = $monthly_sales_result->fetch_assoc()) {
    $monthly_sales_data[] = $row;
}

$product_sales_query = "SELECT 
    p.ProductName,
    SUM(od.Quantity) AS total_quantity_sold,
    SUM(od.Quantity * od.ProductPrice) AS total_sales_value
    FROM orderdetails od
    JOIN orderpayment op ON od.OrderID = op.OrderID
    JOIN product p ON od.ProductName = p.ProductName
    GROUP BY p.ProductID, p.ProductName
    ORDER BY total_quantity_sold DESC
    LIMIT 5";

$product_sales_result = $conn->query($product_sales_query);
$product_sales_data = [];
while ($row = $product_sales_result->fetch_assoc()) {
    $product_sales_data[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel='stylesheet' href='dashboard.css'>
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
                    <td>
                        <div class="card" onclick="window.location.href='order_view.php'" style="cursor: pointer;">
                            <h3>Total Sales</h3>
                            <p>RM <?php echo $total_sales; ?></p>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="chart-container">
                <div class="chart-box">
                    <h3 class="chart-title">Monthly Sales</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                
                <div class="chart-box">
                    <h3 class="chart-title">Top Selling Products</h3>
                    <canvas id="productSalesChart"></canvas>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script>
        // Monthly Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { 
                    return "'" . date('M Y', strtotime($item['month'])) . "'"; 
                }, $monthly_sales_data)); ?>],
                datasets: [{
                    label: 'Monthly Sales (RM)',
                    data: [<?php echo implode(',', array_column($monthly_sales_data, 'monthly_sales')); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'RM ' + value.toLocaleString();
                            }
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return 'RM ' + tooltipItem.yLabel.toLocaleString();
                        }
                    }
                }
            }
        });

        const productSalesCtx = document.getElementById('productSalesChart').getContext('2d');
        const productSalesChart = new Chart(productSalesCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { 
                    return "'" . addslashes($item['ProductName']) . "'"; 
                }, $product_sales_data)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($product_sales_data, 'total_quantity_sold')); ?>],
                    backgroundColor: [
                        'rgba(90, 184, 247, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 102, 102, 0.7)',   
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                legend: {
                    position: 'right',
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const label = data.labels[tooltipItem.index];
                            const value = data.datasets[0].data[tooltipItem.index];
                            const salesValues = <?php echo json_encode(array_column($product_sales_data, 'total_sales_value')); ?>;
                            const salesValue = parseFloat(salesValues[tooltipItem.index]);
                            return `${label}: ${value} units (RM ${salesValue.toFixed(2)})`;
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>