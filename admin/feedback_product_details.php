<?php
session_start();
if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

if (!isset($_GET['product_id'])) {
    header("Location: product_list.php");
    exit();
}

$product_id = $_GET['product_id'];

$product_query_sql = "SELECT * FROM product WHERE ProductID = ?";
$stmt = $conn->prepare($product_query_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "Product not found.";
    exit();
}

// UPDATED: Also select OrderID
$feedback_query = "
    SELECT pf.OrderID, pf.Rating, pf.Feedback AS Comment, pf.FeedbackDate, c.CustID, c.CustName
    FROM product_feedback pf
    JOIN customer c ON pf.CustID = c.CustID
    WHERE pf.ProductID = ?
    ORDER BY pf.FeedbackDate DESC
";
$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$feedbacks = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Details</title>
    <link rel="stylesheet" href="feedback_product_details.css">
</head>
<body>
    <div class="header">
        <?php include 'header.php'; ?>
    </div>
    <div class="container">
        <div class="main-content">
            <div class="order-header">
                <button name="back" class="view-details-btn" onclick="window.location.href='feedback_product.php'">Back</button>
                <h2>Feedback - <?php echo htmlspecialchars($product['ProductName']); ?></h2>
                <button name="print" onclick="printPage()">Print</button>
            </div>
            <div class="product-info">
                <h3>Product Information</h3>
                <table class="product-details-table">
                    <tr>
                        <td style="text-align: center; vertical-align: middle;">
                            <?php
                            $imageName = $product['ProductPicture'];
                            $imagePath = "../image/" . $imageName;
                            if (file_exists($imagePath) && !empty($imageName)) {
                                echo "<img src='{$imagePath}' alt='{$product['ProductName']}' width='150'>";
                            } else {
                                echo "<img src='../image/placeholder.jpg' alt='Image not available' width='150'>";
                            }
                            ?>
                        </td>
                        <td class="product-text">
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($product['ProductID']); ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($product['ProductName']); ?></p>
                            <p><strong>Price:</strong> RM <?php echo number_format($product['ProductPrice'], 2); ?></p>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['ProductDesc']); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="feedback-section">
                <h3>Customer Feedback</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 150px;">Customer</th>
                            <th style="width: 40px; text-align: center;">Rating</th>
                            <th style="width: 300px;">Comment</th>
                            <th style="width: 110px; text-align: center;">Feedback Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($feedbacks->num_rows > 0) {
                            while ($feedback = $feedbacks->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($feedback['CustName']); ?></td>
                            <td style="text-align: center;"><?php echo $feedback['Rating']; ?> / 5</td>
                            <td>
                                <?php 
                                // Check if comment is empty
                                if (empty($feedback['Comment'])) {
                                    echo "<span class='bcm-warning'>-</span>";
                                } else {
                                    echo htmlspecialchars($feedback['Comment']);
                                }
                                ?>
                            </td>
                            <td style="text-align: center;"><?php echo $feedback['FeedbackDate']; ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='5' class='center'>No feedback available.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</body>
</html>
