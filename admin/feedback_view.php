<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$rating_filter = isset($_GET['rating']) ? trim($_GET['rating']) : '';

$feedback_query = "
    SELECT f.FeedbackID, f.Rating, f.Feedback, f.FeedbackDate, c.CustName
    FROM feedback_rating f
    INNER JOIN customer c ON f.CustID = c.CustID
";

if ($rating_filter !== '') {
    $rating_filter = $conn->real_escape_string($rating_filter);
    $feedback_query .= " WHERE f.Rating = '$rating_filter'";
}

$feedback_query .= " ORDER BY f.FeedbackID DESC";

$feedback_result = $conn->query($feedback_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Records</title>
    <link rel='stylesheet' href='feedback_view.css'>
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
            <h2>Feedback Records</h2>
            
            <form method="GET" action="" class="search">
                <label for="rating">Filter by rating:</label>
                <select name="rating" id="rating" onchange="this.form.submit()">
                    <option value="">All Ratings</option>
                    <option value="5" <?php if ($rating_filter === '5') echo 'selected'; ?>>5 Stars</option>
                    <option value="4" <?php if ($rating_filter === '4') echo 'selected'; ?>>4 Stars</option>
                    <option value="3" <?php if ($rating_filter === '3') echo 'selected'; ?>>3 Stars</option>
                    <option value="2" <?php if ($rating_filter === '2') echo 'selected'; ?>>2 Stars</option>
                    <option value="1" <?php if ($rating_filter === '1') echo 'selected'; ?>>1 Stars</option>
                </select>
            </form>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">ID</th>
                        <th>Name</th>
                        <th style="text-align: center;">Rating</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($feedback_result && $feedback_result->num_rows > 0): ?>
                        <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $feedback['FeedbackID']; ?></td>
                                <td><?php echo htmlspecialchars($feedback['CustName']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($feedback['Rating']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['Feedback']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($feedback['FeedbackDate'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: red;"><b>No feedback records found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
