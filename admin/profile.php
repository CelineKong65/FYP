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

    // Check if either password field has content
    if (!empty($current_password) || !empty($new_password)) {
        if (empty($current_password)) {
            echo "<script>alert('Current password is required to change password.'); window.location.href='profile.php';</script>";
            exit();
        }
        
        if (empty($new_password)) {
            echo "<script>alert('New password is required when changing password.'); window.location.href='profile.php';</script>";
            exit();
        }
        
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
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
            echo "<script>alert('New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'); window.location.href='profile.php';</script>";
            exit();
        }
        
        $password_update = ", AdminPassword = ?";
        $params[] = $new_password;
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
                    <form method="POST" action="" id="profileForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin['AdminName']); ?>" required>
                            <div id="name-error" class="error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Position</label>
                            <div class="position-display"><?php echo htmlspecialchars($admin['AdminPosition']); ?></div>
                            <input type="hidden" name="position" value="<?php echo htmlspecialchars($admin['AdminPosition']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['AdminEmail']); ?>" required>
                            <div id="email-error" class="error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['AdminPhoneNum']); ?>" required>
                            <div id="phone-error" class="error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password (required if changing password)</label>
                            <input type="password" id="current_password" name="current_password">
                            <div id="current-password-error" class="error"></div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password (required if changing password)</label>
                            <div class="pass-field">
                                <input type="password" id="new_password" name="new_password" placeholder="123$abcd">
                                <i class="fa-solid fa-eye" id="show-password"></i>
                                <div id="new-password-error" class="error"></div>
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
                        <div class="upd_div">
                            <button type="submit" name="update_profile" class="btn">Update</button>
                        </div>
                    </form>
                </div>  
            </div>
        </div>
    </div>
    <script>
    // Validation functions
    function validateName(name) {
        if (name.trim() === '') {
            return 'Name is required';
        }
        return '';
    }

    function validateEmail(email) {
        if (email.trim() === '') {
            return 'Email is required';
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || !email.endsWith('.com')) {
            return 'Invalid email format (must contain @ and end with .com)';
        }
        return '';
    }

    function validatePhone(phone) {
        if (phone.trim() === '') {
            return 'Phone is required';
        }
        if (!/^\d{3}-\d{3,4} \d{4}$/.test(phone)) {
            return 'Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format';
        }
        return '';
    }

    function validateNewPassword(password) {
        if (password.trim() === '') {
            return '';
        }
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(password)) {
            return 'Password must be at least 8 characters with uppercase, lowercase, number and special character';
        }
        return '';
    }

    function checkPasswordRequirements(password) {
        const requirements = [
            {regex: /.{8,}/},            // At least 8 characters
            {regex: /[A-Z]/},             // At least one uppercase
            {regex: /[a-z]/},             // At least one lowercase
            {regex: /\d/},                // At least one digit
            {regex: /[@$!%*#?&]/},        // At least one special symbol
            {regex: /^\S*$/}              // No space
        ];
        
        return requirements.every(req => req.regex.test(password));
    }

    // Show/hide error functions
    function showError(fieldId, errorId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('error-field');
        }
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function clearError(fieldId, errorId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove('error-field');
        }
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Update password requirements UI
    function updatePasswordRequirements(password) {
        const requirements = [
            {regex: /.{8,}/, index: 0},
            {regex: /[A-Z]/, index: 1},
            {regex: /[a-z]/, index: 2},
            {regex: /\d/, index: 3},
            {regex: /[@$!%*#?&]/, index: 4},
            {regex: /^\S*$/, index: 5}
        ];

        requirements.forEach(item => {
            const isValid = item.regex.test(password);
            const requirementItem = document.querySelectorAll(".password-req li")[item.index];
            
            if (requirementItem) {
                requirementItem.firstElementChild.className = isValid ? "fa-solid fa-circle-check" : "fa-solid fa-circle";
                requirementItem.classList.toggle("valid", isValid);
            }
        });
    }

    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Validate basic fields
        const nameError = validateName(document.getElementById('name').value);
        if (nameError) {
            showError('name', 'name-error', nameError);
            isValid = false;
        } else {
            clearError('name', 'name-error');
        }

        const emailError = validateEmail(document.getElementById('email').value);
        if (emailError) {
            showError('email', 'email-error', emailError);
            isValid = false;
        } else {
            clearError('email', 'email-error');
        }

        const phoneError = validatePhone(document.getElementById('phone').value);
        if (phoneError) {
            showError('phone', 'phone-error', phoneError);
            isValid = false;
        } else {
            clearError('phone', 'phone-error');
        }

        // Validate password fields
        const currentPassword = document.getElementById('current_password').value.trim();
        const newPassword = document.getElementById('new_password').value.trim();

        // Only validate if at least one field has content
        if (currentPassword || newPassword) {
            if (!currentPassword) {
                showError('current_password', 'current-password-error', 'Current password is required');
                isValid = false;
            } else {
                clearError('current_password', 'current-password-error');
            }

            if (!newPassword) {
                showError('new_password', 'new-password-error', 'New password is required');
                isValid = false;
            } else {
                const newPasswordError = validateNewPassword(newPassword);
                if (newPasswordError) {
                    showError('new_password', 'new-password-error', newPasswordError);
                    isValid = false;
                } else if (!checkPasswordRequirements(newPassword)) {
                    showError('new_password', 'new-password-error', 'Please meet all password requirements');
                    isValid = false;
                } else {
                    clearError('new_password', 'new-password-error');
                }
            }
        } else {
            // Clear errors when both fields are empty
            clearError('current_password', 'current-password-error');
            clearError('new_password', 'new-password-error');
        }

        return isValid;
    }

    // Real-time validation and event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profileForm');
        const currentPasswordInput = document.getElementById('current_password');
        const newPasswordInput = document.getElementById('new_password');
        const eyeIcon = document.getElementById('show-password');

        // Real-time validation for name, email, phone
        document.getElementById('name').addEventListener('input', function() {
            const error = validateName(this.value);
            if (error) {
                showError('name', 'name-error', error);
            } else {
                clearError('name', 'name-error');
            }
        });

        document.getElementById('email').addEventListener('input', function() {
            const error = validateEmail(this.value);
            if (error) {
                showError('email', 'email-error', error);
            } else {
                clearError('email', 'email-error');
            }
        });

        document.getElementById('phone').addEventListener('input', function() {
            const error = validatePhone(this.value);
            if (error) {
                showError('phone', 'phone-error', error);
            } else {
                clearError('phone', 'phone-error');
            }
        });

        // Password fields validation
        function validatePasswordFields() {
            const currentPassword = currentPasswordInput.value.trim();
            const newPassword = newPasswordInput.value.trim();

            // Only validate if at least one field has content
            if (currentPassword || newPassword) {
                if (!currentPassword) {
                    showError('current_password', 'current-password-error', 'Current password is required');
                    return false;
                } else {
                    clearError('current_password', 'current-password-error');
                }

                if (!newPassword) {
                    showError('new_password', 'new-password-error', 'New password is required');
                    return false;
                } else {
                    const newPasswordError = validateNewPassword(newPassword);
                    if (newPasswordError) {
                        showError('new_password', 'new-password-error', newPasswordError);
                        return false;
                    } else if (!checkPasswordRequirements(newPassword)) {
                        showError('new_password', 'new-password-error', 'Please meet all password requirements');
                        return false;
                    } else {
                        clearError('new_password', 'new-password-error');
                    }
                }
            } else {
                // Clear errors when both fields are empty
                clearError('current_password', 'current-password-error');
                clearError('new_password', 'new-password-error');
            }
            return true;
        }

        currentPasswordInput.addEventListener('input', function() {
            validatePasswordFields();
        });

        newPasswordInput.addEventListener('input', function() {
            validatePasswordFields();
            updatePasswordRequirements(this.value);
        });

        // Toggle password visibility
        if (eyeIcon) {
            eyeIcon.addEventListener('click', function() {
                const type = newPasswordInput.type === 'password' ? 'text' : 'password';
                newPasswordInput.type = type;
                this.className = type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
            });
        }

        // Form submission
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>