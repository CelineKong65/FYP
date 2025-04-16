<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db_connection.php';

$loggedInAdminID = $_SESSION['AdminID'];
$position_check_query = "SELECT AdminPosition FROM admin WHERE AdminID = ?";
$stmt = $conn->prepare($position_check_query);
$stmt->bind_param("i", $loggedInAdminID);
$stmt->execute();
$result = $stmt->get_result();
$adminData = $result->fetch_assoc();
$loggedInPosition = $adminData['AdminPosition'];
$stmt->close();

$admin_query = "SELECT * FROM admin";
$admin_result = $conn->query($admin_query);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $admin_query = "SELECT * FROM admin WHERE AdminName LIKE ? OR AdminEmail LIKE ?";
    $stmt = $conn->prepare($admin_query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $admin_result = $stmt->get_result();
}

if (isset($_POST['update_admin'])) {
    $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);

    $check_name_query = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("si", $name, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Admin name already exists. Please use a different name.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();  

    $check_email_query = "SELECT AdminID FROM admin WHERE AdminEmail = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("si", $phone, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();


    $original_query = "SELECT * FROM admin WHERE AdminID = ?";
    $stmt = $conn->prepare($original_query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $original_result = $stmt->get_result();
    $original_data = $original_result->fetch_assoc();
    $stmt->close();

    $has_changes = (
        $name !== $original_data['AdminName'] ||
        $email !== $original_data['AdminEmail'] ||
        $phone !== $original_data['AdminPhoneNum'] ||
        $position !== $original_data['AdminPosition']
    );

    if (!$has_changes) {
        echo "<script>alert('No changes detected.'); window.location.href='admin_view.php';</script>";
        exit();
    } else {
            $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ?, AdminPosition = ? WHERE AdminID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $name, $email, $phone, $position, $admin_id);
        }
    
        if ($stmt->execute()) {
            echo "<script>alert('Admin updated successfully!'); window.location.href='admin_view.php';</script>";
        } else {
            echo "<script>alert('Failed to update admin.'); window.location.href='admin_view.php';</script>";
        }
        $stmt->close();
        exit();
}

if (isset($_POST['add_admin'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);
    $status = 'active';

    $check_name_query = "SELECT AdminID FROM admin WHERE AdminName = ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Admin name already exists. Please use a different name.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_email_query = "SELECT AdminID FROM admin WHERE AdminEmail = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? ";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    $insert_query = "INSERT INTO admin (AdminName, AdminEmail, AdminPassword, AdminPhoneNum, AdminPosition, AdminStatus) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $position, $status);

    if ($stmt->execute()) {
        echo "<script>alert('Admin added successfully!'); window.location.href='admin_view.php';</script>";
    } else {
        echo "<script>alert('Failed to add admin.'); window.location.href='admin_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    if ($loggedInPosition !== 'superadmin') {
        echo "<script>alert('Only Super Admin can change admin status.'); window.location.href='admin_view.php';</script>";
        exit();
    }

    $admin_id = (int)$_POST['admin_id'];

    if ($admin_id == $loggedInAdminID) {
        echo "<script>alert('You cannot deactivate yourself!'); window.location.href='admin_view.php';</script>";
        exit();
    }
    
    $currentStatus = strtolower($_POST['current_status']);

    $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';

    $stmt = $conn->prepare("UPDATE admin SET AdminStatus = ? WHERE AdminID = ?");
    $stmt->bind_param("si", $newStatus, $admin_id);

    if ($stmt->execute()) {
        header("Location: admin_view.php");
        exit();
    } else {
        $error = "Status update failed: " . $conn->error;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin List</title>
    <link rel="stylesheet" href="admin_view.css">
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
            <h2>Admin List</h2>

            <?php if (isset($_SESSION['message'])): ?>
                <p class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
            <?php endif; ?>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>

            <?php if ($loggedInPosition === 'superadmin'): ?>        
                <div class="add">
                    <button onclick="openAddModal()" class="add_btn">Add Admin</button>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <?php if ($loggedInPosition === 'superadmin'): ?>
                        <th style="text-align: center;">Status</th>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($admin_result && $admin_result->num_rows > 0): ?>
                        <?php while ($admin = $admin_result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $admin['AdminID']; ?></td>
                                <td><?php echo $admin['AdminName']; ?></td>
                                <td><?php echo $admin['AdminEmail']; ?></td>
                                <td><?php echo $admin['AdminPhoneNum']; ?></td>
                                <td><?php echo $admin['AdminPosition']; ?></td>
                                <?php if ($loggedInPosition === 'superadmin'): ?>
                                    <td class="<?php echo ($admin['AdminStatus'] === 'active') ? 'status-active' : 'status-inactive'; ?>" style="text-align: center;">
                                        <?php echo $admin['AdminStatus']; ?>
                                    </td>
                                    <td>
                                        <button name="edit_admin" onclick='editAdmin(
                                                <?php echo json_encode($admin["AdminID"]); ?>,
                                                <?php echo json_encode($admin["AdminName"]); ?>,
                                                <?php echo json_encode($admin["AdminEmail"]); ?>,
                                                <?php echo json_encode($admin["AdminPhoneNum"]); ?>,
                                                <?php echo json_encode($admin["AdminPosition"]); ?>,
                                                <?php echo json_encode($admin["AdminPassword"]); ?>)'>Edit
                                        </button>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['AdminID']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $admin['AdminStatus']; ?>">
                                                <button type="submit" class="btn-status <?php echo ($admin['AdminStatus'] == 'active') ? 'btn-inactive' : 'btn-active'; ?>">
                                                    <?php echo ($admin['AdminStatus'] == 'active') ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No admin found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Admin</h3>
            <form method="POST" action="" enctype="multipart/form-data" class="edit">
                <input type="hidden" name="admin_id" id="admin_id">
                <label>Name:</label>
                <input type="text" name="name" id="name" required>
                <label>Email:</label>
                <input type="email" name="email" id="email" required>
                <label>Phone:</label>
                <input type="text" name="phone" id="phone" required>
                <label>Position:</label>
                <div id="position-container">
                    <!-- Position field will be inserted here dynamically -->
                </div>
                <div class="upd_div">
                    <button type="submit" name="update_admin">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Admin</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <label>Name:</label>
                <input type="text" name="name" id="name" required>
                <label>Email:</label>
                <input type="email" name="email" id="email" required>
                <label>Phone:</label>
                <input type="text" name="phone" id="phone" required>
                <label>Position:</label>
                <select name="position" id="position" required>
                    <option value="admin">admin</option>
                    <option value="superadmin">superadmin</option>
                </select>
                <div class="add_div">
                    <button type="submit" name="add_admin">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editAdmin(admin_id, name, email, phone, position, password) {
            document.getElementById('admin_id').value = admin_id;
            document.getElementById('name').value = name;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            
            // Get the position container
            const positionContainer = document.getElementById('position-container');
            
            // Check if the admin being edited is a superadmin
            if (position === 'superadmin') {
                // For superadmin, show read-only display
                positionContainer.innerHTML = `
                    <div class="position-display">${position}</div>
                    <input type="hidden" name="position" value="superadmin">
                `;
            } else {
                // For regular admin, show select dropdown
                positionContainer.innerHTML = `
                    <select name="position" id="position" required>
                        <option value="admin" ${position === 'admin' ? 'selected' : ''}>admin</option>
                        <option value="superadmin" ${position === 'superadmin' ? 'selected' : ''}>superadmin</option>
                    </select>
                `;
            }

            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
    </script>
</body>
</html>