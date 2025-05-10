<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$rating_filter = isset($_GET['rating']) ? trim($_GET['rating']) : '';

// Base query
$product_feedback_query = "
    SELECT 
        pf.ProductID, 
        p.ProductName, 
        AVG(pf.Rating) AS AverageRating,
        COUNT(pf.ProductFeedbackID) AS TotalFeedbacks
    FROM product_feedback pf
    INNER JOIN product p ON pf.ProductID = p.ProductID
    GROUP BY pf.ProductID
";

// Add HAVING clause if a filter is selected
if ($rating_filter !== '') {
    $having_clause = "";
    switch ($rating_filter) {
        case '4':
            $having_clause = "HAVING AverageRating BETWEEN 4 AND 5";
            break;
        case '3':
            $having_clause = "HAVING AverageRating BETWEEN 3 AND 4";
            break;
        case '2':
            $having_clause = "HAVING AverageRating BETWEEN 2 AND 3";
            break;
        case '1':
            $having_clause = "HAVING AverageRating BETWEEN 1 AND 2";
            break;
    }
    
    // Insert the HAVING clause before the ORDER BY
    $product_feedback_query = str_replace("GROUP BY pf.ProductID", "GROUP BY pf.ProductID " . $having_clause, $product_feedback_query);
}

// Add ORDER BY
$product_feedback_query .= " ORDER BY AverageRating DESC";

$product_feedback_result = $conn->query($product_feedback_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Feedback</title>
    <link rel='stylesheet' href='feedback_product.css'>
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
            <h2>Product Feedback</h2>
            
            <form method="GET" action="" class="search">
                <label for="rating">Filter by average rating:</label>
                <select name="rating" id="rating" onchange="this.form.submit()">
                    <option value="">All Ratings</option>
                    <option value="4" <?php if ($rating_filter === '4') echo 'selected'; ?>>4 - 5</option>
                    <option value="3" <?php if ($rating_filter === '3') echo 'selected'; ?>>3 - 4</option>
                    <option value="2" <?php if ($rating_filter === '2') echo 'selected'; ?>>2 - 3</option>
                    <option value="1" <?php if ($rating_filter === '1') echo 'selected'; ?>>1 - 2</option>
                </select>
            </form>

            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 50px;">Product ID</th>
                        <th style="width: 300px;">Product Name</th>
                        <th style="text-align: center; width: 100px;">Average Rating</th>
                        <th style="text-align: center; width: 80px;">Total Feedbacks</th>
                        <th style="width: 200px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($product_feedback_result && $product_feedback_result->num_rows > 0): ?>
                        <?php while ($product_feedback = $product_feedback_result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $product_feedback['ProductID']; ?></td>
                                <td><?php echo htmlspecialchars($product_feedback['ProductName']); ?></td>
                                <td style="text-align: center;"><?php echo number_format($product_feedback['AverageRating'], 2); ?></td>
                                <td style="text-align: center;"><?php echo $product_feedback['TotalFeedbacks']; ?></td>
                                <td style="text-align: center;">
                                    <button name="view_details" onclick="window.location.href='feedback_product_details.php?product_id=<?php echo $product_feedback['ProductID']; ?>'" >View Details</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: red;"><b>No product feedback records found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</body>
</html>
