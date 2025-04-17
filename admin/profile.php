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
    $new_password = $_POST['new_password'] ?? '';
    
    $password_update = "";
    $params = [$name, $email, $phone];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo "<script>alert('Invalid email format. Email must be valid and end with .com'); window.location.href='profile.php';</script>";
        exit();
    }
    
    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo "<script>alert('Invalid phone number format. Use XXX-XXX XXXX or XXX-XXXX XXXX.'); window.location.href='profile.php';</script>";
        exit();
    }

    if (!empty($current_password)) {
        $password_query = "SELECT AdminPassword FROM admin WHERE AdminID = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $db_password = $result->fetch_assoc()['AdminPassword'];
        
        if ($current_password !== $db_password) {
            echo "<script>alert('Current password is incorrect.'); window.location.href='profile.php';</script>";
            exit();
        }
        
        if (empty($new_password)) {
            echo "<script>alert('New password is required when changing password.'); window.location.href='profile.php';</script>";
            exit();
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
            echo "<script>alert('New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'); window.location.href='profile.php';</script>";
            exit();
        }
        
        $password_update = ", AdminPassword = ?";
        $params[] = $new_password;
    } elseif (!empty($new_password)) {
        echo "<script>alert('Current password is required to change password.'); window.location.href='profile.php';</script>";
        exit();
    }

    $name_check_query = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
    $stmt = $conn->prepare($name_check_query);
    $stmt->bind_param("si", $name, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('The name \"$name\" is already taken. Please choose a different name.'); window.location.href='profile.php';</script>";
        exit();
    }

    $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ?" . $password_update . " WHERE AdminID = ?";
    $params[] = $admin_id;

    $stmt = $conn->prepare($update_query);

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
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
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
                            <label for="current_password">Current Password (Leave empty to keep the current password)</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="pass-field">
                                <input type="password" id="new_password" name="new_password" placeholder="123$abcd">
                                <i class="fa-solid fa-eye" id="show-password"></i>
                            </div>
                            
                            <div class="content">
                                <p>Password minimum requirements</p>
                                <ul class="password-req">
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 8 characters</span>
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 uppercase letter [A...Z]</span>
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 lowercase letter [a...z]</span>
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 number</span>
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 special symbol [!...$]</span>
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>No spaces</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        </div>
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
    const passwordInput = document.querySelector(".pass-field input");
    const eyeIcon = document.querySelector(".pass-field i");
    const requirementList = document.querySelectorAll(".password-req li");

    const requirements = [
        {regex: /\S{8,}/, index: 0},       // Minimum 8 non-whitespace characters
        {regex: /[A-Z]/, index: 1},        // At least one uppercase
        {regex: /[a-z]/, index: 2},        // At least one lowercase
        {regex: /\d/, index: 3},           // At least one digit
        {regex: /[@$!%*#?&]/, index: 4},   // At least one special symbol
        {regex: /^\S*$/, index: 5}         // No space
    ];

    passwordInput.addEventListener("keyup", (e) => {
        requirements.forEach(item => {
            const isValid = item.regex.test(e.target.value);
            const requirementItem = requirementList[item.index];

            if(isValid){
                requirementItem.firstElementChild.className = "fa-solid fa-circle-check";
                requirementItem.classList.add("valid");
            }else{
                requirementItem.firstElementChild.className = "fa-solid fa-circle";
                requirementItem.classList.remove("valid");
            }
        });
    });

    eyeIcon.addEventListener("click", () => {
        passwordInput.type = passwordInput.type === "password" ? "text" : "password";
        eyeIcon.className = `fa-solid fa-eye${passwordInput.type === "password" ? "" : "-slash"}`;
    });

    const form = document.querySelector("form");
    form.addEventListener("submit", function(event) {
        const currentPassword = document.getElementById("current_password");
        const newPassword = document.getElementById("new_password");
        const requirementItems = document.querySelectorAll(".password-req li");
        
        // Check if new password is being set
        if (newPassword.value.trim() !== "") {
            // Check current password is provided
            if (currentPassword.value.trim() === "") {
                alert("Please enter your current password to change your password.");
                currentPassword.focus();
                event.preventDefault();
                return;
            }
            
            // Check all requirements are met
            let allValid = true;
            requirementItems.forEach(item => {
                if (!item.classList.contains("valid")) {
                    allValid = false;
                }
            });
            
            if (!allValid) {
                alert("Please ensure your new password meets all requirements.");
                newPassword.focus();
                event.preventDefault();
                return;
            }
            
            // Additional regex validation
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(newPassword.value)) {
                alert("New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.");
                newPassword.focus();
                event.preventDefault();
                return;
            }
        }
    });
});

    </script>
</body>
</html>