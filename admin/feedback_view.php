<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$feedback_query = "
    SELECT f.FeedbackID, f.Rating, f.Feedback, c.CustName
    FROM feedback_rating f
    INNER JOIN customer c ON f.CustID = c.CustID
    ORDER BY f.FeedbackID DESC
";

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
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Rating</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($feedback_result && $feedback_result->num_rows > 0): ?>
                        <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $feedback['FeedbackID']; ?></td>
                                <td><?php echo htmlspecialchars($feedback['CustName']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['Rating']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['Feedback']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: red;"><b>No feedback records found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
