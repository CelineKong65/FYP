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
    <link rel="stylesheet" href="profile.css">
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

                <div class="upd_profile_div">
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
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password (Leave empty to keep the current password)</label>
                            <input type="password" id="password" name="password">
                            <div class="upd_div">
                                <button type="submit" name="update_profile" class="btn">Update</button>
                            </div>
                        </div>
                    </form>
                </div>  
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const currentPassword = document.getElementById("current_password");
            const newPassword = document.getElementById("password");
            const form = document.querySelector("form");

            form.addEventListener("submit", function(event) {
                if (currentPassword.value.trim() !== "" && newPassword.value.trim() === "") {
                    alert("Please enter a new password if you are providing a current password.");
                    newPassword.focus();
                    event.preventDefault();
                }
            });
        });
    </script>

</body>
</html>