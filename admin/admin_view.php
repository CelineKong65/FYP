<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

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
    $password = trim($_POST['password']);
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

    if (!empty($password)) {
        $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPassword = ?, AdminPhoneNum = ?, AdminPosition = ? WHERE AdminID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssi", $name, $email, $password, $phone, $position, $admin_id);
    } else {
        $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ?, AdminPosition = ? WHERE AdminID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssi", $name, $email, $phone, $position, $admin_id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Admin updated successfully!'); window.location.href='admin_view.php';</script>";
    } else {
        echo "<script>alert('Failed to update customer.'); window.location.href='admin_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['delete_admin'])) {
    $admin_id = intval($_POST['admin_id']);

    $delete_query = "DELETE FROM admin WHERE AdminID = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $admin_id);

    if ($stmt->execute()) {
        echo "<script>alert('Admin deleted successfully!'); window.location.href='admin_view.php';</script>";
    } else {
        echo "<script>alert('Admin to delete customer.'); window.location.href='admin_view.php';</script>";
    }
    exit();
}

if (isset($_POST['add_admin'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);

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

    $insert_query = "INSERT INTO admin (AdminName, AdminEmail, AdminPassword, AdminPhoneNum, AdminPosition) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssss", $name, $email, $password, $phone, $position);

    if ($stmt->execute()) {
        echo "<script>alert('Admin added successfully!'); window.location.href='admin_view.php';</script>";
    } else {
        echo "<script>alert('Failed to add admin.'); window.location.href='admin_view.php';</script>";
    }
    $stmt->close();
    exit();
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
                    
            <div class="add">
                <button onclick="openAddModal()" class="add_btn">Add Admin</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Password</th> 
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($admin_result && $admin_result->num_rows > 0): ?>
                        <?php while ($admin = $admin_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $admin['AdminID']; ?></td>
                                <td><?php echo $admin['AdminName']; ?></td>
                                <td><?php echo $admin['AdminEmail']; ?></td>
                                <td><?php echo $admin['AdminPhoneNum']; ?></td>
                                <td><?php echo $admin['AdminPosition']; ?></td>
                                <td><?php echo $admin['AdminPassword']; ?></td>
                                <td>
                                    <button name="edit_admin" onclick='editAdmin(<?php echo json_encode($admin["AdminID"]); ?>, <?php echo json_encode($admin["AdminName"]); ?>, <?php echo json_encode($admin["AdminEmail"]); ?>, <?php echo json_encode($admin["AdminPhoneNum"]); ?>, <?php echo json_encode($admin["AdminPosition"]); ?>, <?php echo json_encode($admin["AdminPassword"]); ?>)'>Edit</button>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['AdminID']; ?>">
                                        <button type="submit" name="delete_admin" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</button>
                                    </form>
                                </td>
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
                <select name="position" id="position" required>
                    <option value="admin">admin</option>
                    <option value="superadmin">superadmin</option>
                </select>
                <label>Password:</label>
                <input type="text" name="password" id="password">
                <p style="color:gray;">(Leave empty to keep the current password)</p>
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
                <label>Password:</label>
                <input type="text" name="password" id="password" required>
                <div class="add_div">
                    <button type="submit" name="add_admin">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editAdmin(admin_id, name, email, phone, position) {
            document.getElementById('admin_id').value = admin_id;
            document.getElementById('name').value = name;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;  // Now correctly assigned
            document.getElementById('position').value = position; 
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