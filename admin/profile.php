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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_account'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
            echo "<script>alert('Invalid email format. Email must be valid and end with .com'); window.location.href='profile.php';</script>";
            exit();
        }
        
        // Validate phone format
        if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
            echo "<script>alert('Invalid phone number format. Use XXX-XXX XXXX or XXX-XXXX XXXX.'); window.location.href='profile.php';</script>";
            exit();
        }

        // Check if name already exists (excluding current admin)
        $name_check_query = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
        $stmt = $conn->prepare($name_check_query);
        $stmt->bind_param("si", $name, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('The name \"$name\" is already taken. Please choose a different name.'); window.location.href='profile.php';</script>";
            exit();
        }

        // Check if email already exists (excluding current admin)
        $email_check_query = "SELECT AdminID FROM admin WHERE AdminEmail = ? AND AdminID != ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('The email \"$email\" is already in use. Please use a different email.'); window.location.href='profile.php';</script>";
            exit();
        }

        // Check if phone already exists (excluding current admin)
        $phone_check_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? AND AdminID != ?";
        $stmt = $conn->prepare($phone_check_query);
        $stmt->bind_param("si", $phone, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('The phone number \"$phone\" is already in use. Please use a different phone number.'); window.location.href='profile.php';</script>";
            exit();
        }

        // If all checks pass, update the profile
        $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ? WHERE AdminID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $name, $email, $phone, $admin_id);

        if ($stmt->execute()) {
            echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating profile'); window.location.href='profile.php';</script>";
            exit();
        }
    }
    elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo "<script>alert('All password fields are required'); window.location.href='profile.php';</script>";
            exit();
        }
        
        if ($new_password !== $confirm_password) {
            echo "<script>alert('New passwords do not match'); window.location.href='profile.php';</script>";
            exit();
        }
        
        $password_query = "SELECT AdminPassword FROM admin WHERE AdminID = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $db_password = $result->fetch_assoc()['AdminPassword'];
        
        if ($current_password !== $db_password) {
            echo "<script>alert('Current password is incorrect'); window.location.href='profile.php';</script>";
            exit();
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
            echo "<script>alert('New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'); window.location.href='profile.php';</script>";
            exit();
        }
    
        $update_query = "UPDATE admin SET AdminPassword = ? WHERE AdminID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_password, $admin_id);
    
        if ($stmt->execute()) {
            echo "<script>alert('Password updated successfully!'); window.location.href='profile.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating password'); window.location.href='profile.php';</script>";
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
    <link rel='stylesheet' href='profile.css'>
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
            <div class="profile-header">
                <h2>My Profile</h2> 
                
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
            </div>
        
        <div class="profile-container">
            <form method="POST" action="" id="profileForm">
                <div class="form-section">
                    <h3>Change Admin information here</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Full Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($admin['AdminName']) ?>" required>
                            <div id="name-error" class="error"></div>
                        </div>
                        <div class="form-group">
                           <label class="required">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['AdminEmail']) ?>" required>
                            <div id="email-error" class="error"></div>
                        </div>
                    </div>
                            
                    <div class="form-row">
                        <div class="form-group">
                            <label>Position</label>
                            <div class="position-display"><?php echo htmlspecialchars($admin['AdminPosition']); ?></div>
                            <input type="hidden" name="position" value="<?php echo htmlspecialchars($admin['AdminPosition']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone" class="required">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['AdminPhoneNum']); ?>" required>
                            <div id="phone-error" class="error"></div>
                        </div>
                    </div>
                    <div class="upd_div">    
                        <button type="submit" name="update_account" class="update-btn">Update Information</button>
                    </div>
                </div>
            </form>
            
            <form method="POST" action="" id="passwordForm">
                <div class="form-section-pass">
                    <h3>Change Password</h3>
                    <div class="form-group">
                        <label for="current_password" class="required">Current Password</label>
                        <div class="pass-field">
                            <input type="password" name="currentPassword" id="currentPassword" value="<?= htmlspecialchars($admin['AdminPassword'] ?? 'Not provided') ?>" equired>
                            <i class="fas fa-eye" id="show-current-password"></i>
                        </div>
                        <div id="currentPassword-error" class="error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="required">New Password</label>
                        <div class="pass-field">
                            <input type="password" name="newPassword" id="newPassword" required>
                            <i class="fas fa-eye" id="show-new-password"></i>
                        </div>
                        <div id="newPassword-error" class="error"></div>
                        <ul class="password-req" id="passwordRequirements" style="display: none;">
                            <li><i class="fas fa-circle"></i><span>At least 8 characters</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 uppercase letter</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 lowercase letter</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 number</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 special symbol</span></li>
                            <li><i class="fas fa-circle"></i><span>No spaces</span></li>
                        </ul>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password_confirm" class="required">Confirm New Password</label>
                        <div class="pass-field">
                            <input type="password" name="confirmPassword" id="confirmPassword" required>
                            <i class="fas fa-eye" id="show-confirm-password"></i>
                        </div>
                        <div id="confirmPassword-error" class="error"></div>
                    </div>
                    
                    <div class="upd_div">
                        <button type="submit" name="update_password" class="update-btn">Update Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    </div>
    <script>
    // DOM Elements
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const currentPasswordInput = document.querySelector('input[name="currentPassword"]');
    const confirmPasswordInput = document.querySelector('input[name="confirmPassword"]');
    const passwordRequirements = document.getElementById('passwordRequirements');

    // Password visibility toggle
    document.getElementById('show-current-password').addEventListener('click', function() {
        const input = document.querySelector('input[name="currentPassword"]');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });
    
    document.getElementById('show-new-password').addEventListener('click', function() {
        const input = document.getElementById('newPassword');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });
    
    document.getElementById('show-confirm-password').addEventListener('click', function() {
        const input = document.querySelector('input[name="confirmPassword"]');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });

    // Show/hide password requirements
    if (newPasswordInput) {
        newPasswordInput.addEventListener('focus', () => {
            passwordRequirements.style.display = 'block';
        });
        
        newPasswordInput.addEventListener('blur', () => {
            if (!passwordRequirements.contains(document.activeElement)) {
                passwordRequirements.style.display = 'none';
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!newPasswordInput.contains(e.target) && !passwordRequirements.contains(e.target)) {
                passwordRequirements.style.display = 'none';
            }
        });
        
        // Password requirement validation
        const requirementList = document.querySelectorAll('.password-req li');
        
        const requirements = [
            {regex: /\S{8,}/, index: 0},
            {regex: /[A-Z]/, index: 1},
            {regex: /[a-z]/, index: 2},
            {regex: /\d/, index: 3},
            {regex: /[@$!%*#?&]/, index: 4},
            {regex: /^\S*$/, index: 5}
        ];
        
        newPasswordInput.addEventListener('keyup', (e) => {
            requirements.forEach(item => {
                const isValid = item.regex.test(e.target.value);
                const requirementItem = requirementList[item.index];
                
                requirementItem.firstElementChild.className = isValid ? 
                    "fas fa-check-circle" : "fas fa-circle";
                requirementItem.classList.toggle('valid', isValid);
            });
        });
    }

    function validateName(name) {
        if (name.trim() === '') {
            return 'Name is required';
        }
        if (name.length < 2) {
            return 'Name must be at least 2 characters';
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

    function validateCurrentPassword(password) {
        if (password.trim() === '') {
            return 'Current password is required';
        }
        return '';
    }

    // Update the password form submission handler
    passwordForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        clearError(currentPasswordInput);
        clearError(newPasswordInput);
        clearError(confirmPasswordInput);
        
        // Validate current password
        const currentPasswordError = validateCurrentPassword(currentPasswordInput.value);
        if (currentPasswordError) {
            showError(currentPasswordInput, currentPasswordError);
            isValid = false;
        }
        
        // Validate new password
        const passwordValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(newPasswordInput.value);
        if (!passwordValid) {
            showError(newPasswordInput, 'Password must meet all requirements');
            isValid = false;
        }
        
        // Validate password confirmation
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            showError(confirmPasswordInput, 'Passwords do not match');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    function validateNewPassword(password) {
        if (password.trim() === '') {
            return 'Password is required';
        }
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(password)) {
            return 'Password must contain: 8+ chars, 1 uppercase, 1 lowercase, 1 number, and 1 special character';
        }
        return '';
    }

    // Update the password input event listener
    newPasswordInput.addEventListener('input', function() {
        const error = validateNewPassword(this.value);
        const passwordValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(this.value);
        
        if (error && this.value.trim() !== '') {
            showError(this, error);
        } else {
            clearError(this);
        }
        
        // Update password requirements visibility
        requirements.forEach(item => {
            const isValid = item.regex.test(this.value);
            const requirementItem = requirementList[item.index];
            
            requirementItem.firstElementChild.className = isValid ? 
                "fas fa-check-circle" : "fas fa-circle";
            requirementItem.classList.toggle('valid', isValid);
        });
        
        if (confirmPasswordInput.value) {
            if (this.value !== confirmPasswordInput.value) {
                showError(confirmPasswordInput, 'Passwords do not match');
            } else {
                clearError(confirmPasswordInput);
            }
        }
    });

    function showError(field, message) {
        const fieldElement = field;
        const errorElement = document.getElementById(`${field.id}-error`);
        
        if (fieldElement) {
            fieldElement.classList.add('error-field');
        }
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function clearError(field) {
        const fieldElement = field;
        const errorElement = document.getElementById(`${field.id}-error`);
        
        if (fieldElement) {
            fieldElement.classList.remove('error-field');
        }
        
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Real-time validation for profile form
    document.getElementById('name').addEventListener('input', function() {
        const error = validateName(this.value);
        if (error) {
            showError(this, error);
        } else {
            clearError(this);
        }
    });

    document.getElementById('email').addEventListener('input', function() {
        const error = validateEmail(this.value);
        if (error) {
            showError(this, error);
        } else {
            clearError(this);
        }
    });

    document.getElementById('phone').addEventListener('input', function() {
        const error = validatePhone(this.value);
        if (error) {
            showError(this, error);
        } else {
            clearError(this);
        }
    });

    currentPasswordInput.addEventListener('input', function() {
        const error = validateCurrentPassword(this.value);
        if (error) {
            showError(this, error);
        } else {
            clearError(this);
        }
    });

    // Password form validation
    newPasswordInput.addEventListener('input', function() {
        const error = validateNewPassword(this.value);
        if (error) {
            showError(this, error);
        } else {
            clearError(this);
        }
        
        if (confirmPasswordInput.value) {
            if (this.value !== confirmPasswordInput.value) {
                showError(confirmPasswordInput, 'Passwords do not match');
            } else {
                clearError(confirmPasswordInput);
            }
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        if (newPasswordInput.value !== this.value) {
            showError(this, 'Passwords do not match');
        } else {
            clearError(this);
        }
    });

    // Form submission handlers
    profileForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        const nameError = validateName(document.getElementById('name').value);
        if (nameError) {
            showError(document.getElementById('name'), nameError);
            isValid = false;
        }
        
        const emailError = validateEmail(document.getElementById('email').value);
        if (emailError) {
            showError(document.getElementById('email'), emailError);
            isValid = false;
        }
        
        const phoneError = validatePhone(document.getElementById('phone').value);
        if (phoneError) {
            showError(document.getElementById('phone'), phoneError);
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    passwordForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        clearError(currentPasswordInput);
        clearError(newPasswordInput);
        clearError(confirmPasswordInput);
        
        // Validate current password
        if (currentPasswordInput.value.trim() === '') {
            showError(currentPasswordInput, 'Current password is required');
            isValid = false;
        }
        
        // Validate new password
        const passwordValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(newPasswordInput.value);
        if (!passwordValid) {
            showError(newPasswordInput, 'Password must meet all requirements');
            isValid = false;
        }
        
        // Validate password confirmation
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            showError(confirmPasswordInput, 'Passwords do not match');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>