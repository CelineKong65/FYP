<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$subject_filter = isset($_GET['subject']) ? trim($_GET['subject']) : '';

$contact_query = "SELECT * FROM contact_record";

if (!empty($subject_filter)) {
    $subject_filter = $conn->real_escape_string($subject_filter);
    $contact_query .= " WHERE Subject = '$subject_filter'";
}

$contact_query .= " ORDER BY Submission_date DESC";

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

            <form method="GET" action="" class="search">
                <label for="subject">Filter by subject :</label>
                <select name="subject" id="subject" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <option value="Product Inquiry" <?php if (isset($_GET['subject']) && $_GET['subject'] == 'Product Inquiry') echo 'selected'; ?>>Product Inquiry</option>
                    <option value="Order Status" <?php if (isset($_GET['subject']) && $_GET['subject'] == 'Order Status') echo 'selected'; ?>>Order Status</option>
                    <option value="Returns & Refunds" <?php if (isset($_GET['subject']) && $_GET['subject'] == 'Returns & Refunds') echo 'selected'; ?>>Returns & Refunds</option>
                    <option value="Other" <?php if (isset($_GET['subject']) && $_GET['subject'] == 'Other') echo 'selected'; ?>>Other</option>
                </select>
            </form>

            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">ID</th>
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
                                <td style="text-align: center;"><?php echo $contact['Contact_ID']; ?></td>
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