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

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['profile_picture'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['profile_picture'] = "Only JPG, JPEG, PNG & GIF files are allowed";
        } elseif ($_FILES['profile_picture']['size'] > 5000000) {
            $errors['profile_picture'] = "File size must be less than 5MB";
        } else {
            $target_dir = "image/user/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $old_pic = $admin['AdminProfilePicture'] ?? '';
                if ($old_pic && $old_pic != 'image/user/default-profile.jpg' && file_exists($old_pic)) {
                    unlink($old_pic);
                }
                
                $sql = "UPDATE admin SET AdminProfilePicture = ? WHERE AdminID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $target_file, $admin_id);
                $stmt->execute();
                
                $success = "Profile picture updated successfully!";
                $stmt = $conn->prepare($admin_query);
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $admin_result = $stmt->get_result();
                $admin = $admin_result->fetch_assoc();
            } else {
                $errors['profile_picture'] = "Sorry, there was an error uploading your file";
            }
        }
    }
    elseif (isset($_POST['update_account'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
            $errors['email'] = 'Invalid email format. Email must be valid and end with .com';
        }
        
        if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
            $errors['phone'] = 'Invalid phone number format. Use XXX-XXX XXXX or XXX-XXXX XXXX.';
        }

        $name_check_query = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
        $stmt = $conn->prepare($name_check_query);
        $stmt->bind_param("si", $name, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['name'] = "The name \"$name\" is already taken. Please choose a different name.";
        }

        $email_check_query = "SELECT AdminID FROM admin WHERE AdminEmail = ? AND AdminID != ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['email'] = "The email \"$email\" is already in use. Please use a different email.";
        }

        $phone_check_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? AND AdminID != ?";
        $stmt = $conn->prepare($phone_check_query);
        $stmt->bind_param("si", $phone, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['phone'] = "The phone number \"$phone\" is already in use. Please use a different phone number.";
        }

        if (empty($errors)) {
            $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ? WHERE AdminID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssi", $name, $email, $phone, $admin_id);

            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                $stmt = $conn->prepare($admin_query);
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $admin_result = $stmt->get_result();
                $admin = $admin_result->fetch_assoc();
            } else {
                $errors['general'] = 'Error updating profile';
            }
        }
    }
    elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors['password'] = 'All password fields are required';
        }
        
        if ($new_password !== $confirm_password) {
            $errors['confirmPassword'] = 'New passwords do not match';
        }
        
        $password_query = "SELECT AdminPassword FROM admin WHERE AdminID = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $db_password = $result->fetch_assoc()['AdminPassword'];
        
        if ($current_password !== $db_password) {
            $errors['currentPassword'] = 'Current password is incorrect';
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
            $errors['newPassword'] = 'New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
        }
    
        if (empty($errors)) {
            $update_query = "UPDATE admin SET AdminPassword = ? WHERE AdminID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_password, $admin_id);
        
            if ($stmt->execute()) {
                $success = 'Password updated successfully!';
            } else {
                $errors['general'] = 'Error updating password';
            }
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
    <style>
        .error-message {
    color: #e74c3c;
    font-size: 0.8rem;
    margin-top: 5px;
    display: block;
}
.error-field {
    border-color: #e74c3c !important;
    box-shadow: 0 0 0 1px #e74c3c;
}
select.error-field {
    border: 1px solid #e74c3c !important;
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
        <div class="profile-header">
        <img src="../image/admin/<?= htmlspecialchars($admin['AdminProfilePicture'] ?? 'admin.jpg') ?>" 
            alt="Profile Picture" 
            class="profile-picture"
            onerror="this.src='../image/admin/admin.jpg'">
            <h1 class="profile-title">Welcome, <?= htmlspecialchars($admin['AdminName']) ?></h1>
            <div class="account-number">Admin ID: <?= htmlspecialchars($admin['AdminID']) ?></div>
            
            <form class="upload-form" method="post" enctype="multipart/form-data">
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
                <label for="profile_picture"><i class="fas fa-camera"></i> Upload a New Photo</label>
                <button type="submit" style="display: none;" id="upload_submit"></button>
                <?php if (isset($errors['profile_picture'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['profile_picture']) ?></div>
                <?php endif; ?>
            </form>
            
            <?php if (!empty($success)): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($errors['general'])): ?>
                <div class="message error"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="profile-container">
            <form method="POST" action="" id="profileForm">
                <div class="form-section">
                    <h3>Change Admin information here</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Full Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($admin['AdminName']) ?>" required
                                   class="<?= isset($errors['name']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                           <label class="required">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['AdminEmail']) ?>" required
                                   class="<?= isset($errors['email']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
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
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['AdminPhoneNum']); ?>" required
                                   class="<?= isset($errors['phone']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" name="update_account" class="update-btn">Update Information</button>
                </div>
            </form>
            
            <form method="POST" action="" id="passwordForm">
                <div class="form-section-pass">
                    <h3>Change Password</h3>
                    <div class="form-group">
                        <label for="current_password" class="required">Current Password</label>
                        <div class="pass-field">
                            <input type="password" name="currentPassword" id="currentPassword" value="<?= htmlspecialchars($admin['AdminPassword'] ?? 'Not provided') ?>" required>
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
                    
                        <button type="submit" name="update_password" class="update-btn">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    </div>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    // ========== PASSWORD TOGGLE ==========
    function setupPasswordToggle(passwordInputId, eyeIconId) {
        const eyeIcon = document.getElementById(eyeIconId);
        const passwordInput = document.getElementById(passwordInputId) || 
                              document.querySelector(`input[name="${passwordInputId}"]`);
        if (eyeIcon && passwordInput) {
            eyeIcon.addEventListener('click', function () {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            });
        }
    }

    setupPasswordToggle('currentPassword', 'show-current-password');
    setupPasswordToggle('newPassword', 'show-new-password');
    setupPasswordToggle('confirmPassword', 'show-confirm-password');

    // ========== PASSWORD VALIDATION ==========
    const passwordInput = document.getElementById('newPassword');
    const passwordRequirements = document.getElementById('passwordRequirements');
    const requirementList = document.querySelectorAll('.password-req li');
    const requirements = [
        { regex: /\S{8,}/, index: 0 },
        { regex: /[A-Z]/, index: 1 },
        { regex: /[a-z]/, index: 2 },
        { regex: /\d/, index: 3 },
        { regex: /[@$!%*#?&]/, index: 4 },
        { regex: /^\S*$/, index: 5 }
    ];

    if (passwordInput && passwordRequirements) {
        passwordInput.addEventListener('focus', () => {
            passwordRequirements.style.display = 'block';
        });

        passwordInput.addEventListener('blur', () => {
            setTimeout(() => {
                if (!passwordRequirements.contains(document.activeElement)) {
                    passwordRequirements.style.display = 'none';
                }
            }, 200);
        });

        passwordInput.addEventListener('input', function (e) {
            const currentPassword = document.getElementById('currentPassword')?.value;

            // Remove existing error
            this.parentNode.querySelectorAll('.error-message').forEach(msg => {
                if (msg.textContent === 'New password cannot be the same as current password') {
                    msg.remove();
                }
            });

            if (currentPassword && this.value === currentPassword) {
                showError(this, 'New password cannot be the same as current password');
            } else {
                clearError(this);
            }

            requirements.forEach(item => {
                const isValid = item.regex.test(e.target.value);
                const reqItem = requirementList[item.index];
                reqItem.firstElementChild.className = isValid ? "fas fa-check-circle" : "fas fa-circle";
                reqItem.classList.toggle('valid', isValid);
            });
        });
    }
        // ================== VALIDATION SYSTEM ================== //
        // Initialize validation
        initValidation();

        function initValidation() {
            // Form submission handling
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', handleFormSubmit);
            });

            // Real-time field validation
            document.querySelectorAll('input[required], select[required]').forEach(field => {
                field.addEventListener('blur', validateField);
                field.addEventListener('input', validateField);
            });

            // Special field validations
            document.querySelector('input[name="email"]')?.addEventListener('blur', validateEmail);
            document.querySelector('input[name="phone"]')?.addEventListener('input', validatePhoneNumber);
        }

        function handleFormSubmit(e) {
            let isValid = true;
            let firstError = null;

            // Validate all required fields
            this.querySelectorAll('input[required], select[required]').forEach(field => {
                if (!validateField({ target: field })) {
                    isValid = false;
                    if (!firstError) firstError = field;
                }
            });

            if (!isValid) {
                e.preventDefault();
                firstError?.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center',
                    inline: 'nearest'
                });
            }
        }

        function validateField(e) {
            const field = e.target;
            const value = field.tagName === 'SELECT' ? field.value : field.value.trim();
            
            // Required field validation
            if (field.required && value === '') {
                const fieldName = field.name;
                let message = 'Cannot be empty';
                
                showError(field, message);
                return false;
            }
            
            clearError(field);
            return true;
        }

        function validateEmail() {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
            if (!emailRegex.test(this.value)) {
                showError(this, 'Invalid Gmail format');
            }
        }

        function validatePhoneNumber() {
            // Format to XXX-XXX XXXX or XXX-XXXX XXXX
            let phone = this.value.replace(/[-\s]/g, '');
            if (phone.length > 11) phone = phone.substring(0, 11);
            
            let formatted = '';
            if (phone.length > 0) {
                formatted = phone.substring(0, 3);
                if (phone.length > 3) formatted += '-' + phone.substring(3, 6);
                if (phone.length > 6) formatted += ' ' + phone.substring(6);
            }
            this.value = formatted;

            // Validate format
            if (phone && !/^(\+?6?01)[0-46-9][0-9]{7,8}$/.test(phone)) {
                showError(this, 'Invalid Malaysian phone format');
            }
        }

        function showError(field, message) {
            field.classList.add('error-field');
            let errorElement = field.nextElementSibling;
            
            if (!errorElement || !errorElement.classList.contains('error-message')) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                field.parentNode.insertBefore(errorElement, field.nextSibling);
            }
            
            errorElement.textContent = message;
        }

        function clearError(field) {
            field.classList.remove('error-field');
            const errorElement = field.nextElementSibling;
            if (errorElement?.classList.contains('error-message')) {
                errorElement.remove();
            }
        }

        // ================== PROFILE PHOTO UPLOAD ================== //
        document.getElementById('profile_picture').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('upload_submit').click();
            }
        });
    });
</script>

</body>
</html>