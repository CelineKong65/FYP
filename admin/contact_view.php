<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$contact_query = "SELECT * FROM contact_record ORDER BY Submission_date DESC";
$contact_result = $conn->query($contact_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Records</title>
    <link rel='stylesheet' href='contact_view.css'>
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
            <h2>Contact Records</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($contact_result && $contact_result->num_rows > 0): ?>
                        <?php while ($contact = $contact_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $contact['Contact_id']; ?></td>
                                <td><?php echo htmlspecialchars($contact['CustName']); ?></td>
                                <td><?php echo htmlspecialchars($contact['CustEmail']); ?></td>
                                <td><?php echo htmlspecialchars($contact['CustPhoneNum'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($contact['Subject']); ?></td>
                                <td class="message-cell" title="<?php echo htmlspecialchars($contact['Message']); ?>">
                                    <?php echo htmlspecialchars($contact['Message']); ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($contact['Submission_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: red;"><b>No contact records found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>