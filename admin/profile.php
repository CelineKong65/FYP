<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$admin_id = $_SESSION['AdminID'];
$admin_query = "SELECT * FROM admin WHERE AdminID = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['password'] ?? '';
    
    $password_update = "";
    $params = [$name, $email, $phone];

    if (!empty($new_password)) {
        if (empty($current_password)) {
            echo "<script>alert('Current password is required to change password.'); window.location.href='profile.php';</script>";
            exit();
        } else {
            $password_query = "SELECT AdminPassword FROM admin WHERE AdminID = ?";
            $stmt = $conn->prepare($password_query);
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $db_password = $result->fetch_assoc()['AdminPassword'];
    
            if ($current_password !== $db_password) {
                echo "<script>alert('Current password is incorrect.'); window.location.href='profile.php';</script>";
                exit();
            } else {
                $password_update = ", AdminPassword = ?";
                $params[] = $new_password;
            }
        }
    }

    // Prepare update query
    $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ?" . $password_update . " WHERE AdminID = ?";
    $params[] = $admin_id;

    $stmt = $conn->prepare($update_query);

    // Bind parameters dynamically
    if (!empty($new_password)) {
        $stmt->bind_param("ssssi", ...$params);
    } else {
        $stmt->bind_param("sssi", ...$params);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating profile: " . $conn->error . "'); window.location.href='profile.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
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

        .profile-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            color: #1e3a8a;
            margin-bottom: 20px;
            text-align: center;
            margin-top: 0;
        }
        h3 {
            margin-top: 50px;
            font-size: 18px;
        }
        .profile-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .profile-table th, 
        .profile-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .profile-table th {
            width: 30%;
            font-weight: bold;
            color: #555;
        }
        .profile-table td {
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            font-size: 13px;
        }
        .position-display {
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        .btn {
            background-color: #1e3a8a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto 0;
        }
        .btn:hover {
            background-color: #15306b;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-note {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
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
            <div class="profile-container">
                <h2>My Profile</h2>
                
                <?php if (isset($success)): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <table class="profile-table">
                    <tr>
                        <th>Name:</th>
                        <td><?php echo htmlspecialchars($admin['AdminName']); ?></td>
                    </tr>
                    <tr>
                        <th>Position:</th>
                        <td><?php echo htmlspecialchars($admin['AdminPosition']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($admin['AdminEmail']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone Number:</th>
                        <td><?php echo htmlspecialchars($admin['AdminPhoneNum']); ?></td>
                    </tr>
                </table>

                <h3>Update Profile</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin['AdminName']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Position</label>
                        <div class="position-display"><?php echo htmlspecialchars($admin['AdminPosition']); ?></div>
                        <input type="hidden" name="position" value="<?php echo htmlspecialchars($admin['AdminPosition']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['AdminEmail']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['AdminPhoneNum']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password (required for password change)</label>
                        <input type="password" id="current_password" name="current_password">
                        <div class="password-note">Only needed if changing password below</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password">
                        <div class="password-note">Leave blank to keep current password</div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>