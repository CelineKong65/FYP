<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

if (isset($_POST['ajax_check'])) {
    header('Content-Type: application/json');
    $response = ['available' => true];
    
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;

    if (!empty($field) && !empty($value)) {
        try {
            // Special handling for phone number
            if ($field === 'AdminPhoneNum') {
                $cleanValue = preg_replace('/[-\s]/', '', $value);
                $stmt = $conn->prepare("SELECT AdminID FROM admin WHERE REPLACE(REPLACE(AdminPhoneNum, '-', ''), ' ', '') = ? AND AdminID != ?");
                $stmt->execute([$cleanValue, $adminId]);
            } else {
                $stmt = $conn->prepare("SELECT AdminID FROM admin WHERE $field = ? AND AdminID != ?");
                $stmt->execute([$value, $adminId]);
            }
            
            $response['available'] = ($stmt->rowCount() === 0);
        } catch (PDOException $e) {
            // Keep available=true on error
        }
    }
    
    echo json_encode($response);
    exit();
}

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
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/jpg' => 'jpg'];
        $file_type = $_FILES['profile_picture']['type'];
        
        // Validate file type
        if (!array_key_exists($file_type, $allowed_types)) {
            $errors['profile_picture'] = "Only JPG, JPEG & PNG files are allowed";
        } elseif ($_FILES['profile_picture']['size'] > 5000000) { // 5MB max
            $errors['profile_picture'] = "File size must be less than 5MB";
        } else {
            $target_dir = "../image/admin/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Get proper extension based on MIME type
            $file_ext = $allowed_types[$file_type];
            $new_filename = "admin_" . $admin_id . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            $relative_path = "image/admin/" . $new_filename; // For database storage
            
            // Delete old profile picture first (if exists and not default)
            $old_pic = $admin['AdminProfilePicture'] ?? '';
            if ($old_pic) {
                // Convert database path to server path if needed
                $old_file_path = strpos($old_pic, '../') === 0 ? $old_pic : "../" . $old_pic;
                
                // Delete if it's not the default image and exists
                if ($old_file_path != '../image/admin/admin.jpg' && file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
                
                // Also delete any other potential formats of the same admin's picture
                $old_pattern = $target_dir . "admin_" . $admin_id . ".*";
                $old_files = glob($old_pattern);
                foreach ($old_files as $file) {
                    if ($file != $target_file && file_exists($file)) {
                        unlink($file);
                    }
                }
            }
            
            // Move new file and update database
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                // Update database with relative path
                $sql = "UPDATE admin SET AdminProfilePicture = ? WHERE AdminID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$relative_path, $admin_id]);
                
                $success = "Profile picture updated successfully!";
                
                // Refresh admin data
                $stmt = $conn->prepare("SELECT * FROM admin WHERE AdminID = ?");
                $stmt->execute([$admin_id]);
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
                $stmt = $conn->prepare($admin_query);
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $admin_result = $stmt->get_result();
                $admin = $admin_result->fetch_assoc();
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
        <?php
            $admin_id = $admin['AdminID'];
            $jpgPath = "../image/admin/admin_" . $admin_id . ".jpg";
            $jpegPath = "../image/admin/admin_" . $admin_id . ".jpeg";
            $pngPath = "../image/admin/admin_" . $admin_id . ".png";
            $defaultPath = "../image/admin/admin.jpg";

            // Choose web path for browser
            if (file_exists($jpgPath)) {
                $profile_pic_src = "../image/admin/admin_" . $admin_id . ".jpg";
            } elseif (file_exists($jpegPath)) {
                $profile_pic_src = "../image/admin/admin_" . $admin_id . ".jpeg";
            } elseif (file_exists($pngPath)) {
                $profile_pic_src = "../image/admin/admin_" . $admin_id . ".png";
            } else {
                $profile_pic_src = $defaultPath;
            }
            ?>
            <img src="<?= htmlspecialchars($profile_pic_src) ?>" 
                alt="Profile Picture" 
                class="profile-picture"
                onerror="this.src='../image/admin/admin.jpg';">

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
            // ========== CONSTANTS AND CONFIG ==========
            const DEBOUNCE_DELAY = 500;
            const PASSWORD_REQ_TIMEOUT = 2000;
            const ADMIN_ID = <?= json_encode($admin_id) ?>;

            // ========== DOM ELEMENTS ==========
            const forms = {
                profile: document.getElementById('profileForm'),
                password: document.getElementById('passwordForm'),
                upload: document.querySelector('.upload-form')
            };

            const fields = {
                name: document.getElementById('name'),
                email: document.getElementById('email'),
                phone: document.getElementById('phone'),
                currentPassword: document.getElementById('currentPassword'),
                newPassword: document.getElementById('newPassword'),
                confirmPassword: document.getElementById('confirmPassword'),
                profilePicture: document.getElementById('profile_picture')
            };

            const passwordRequirements = document.getElementById('passwordRequirements');
            const requirementItems = document.querySelectorAll('.password-req li');

            // ========== PASSWORD TOGGLE VISIBILITY ==========
            function setupPasswordToggle(passwordInputId, eyeIconId) {
                const eyeIcon = document.getElementById(eyeIconId);
                const passwordInput = document.getElementById(passwordInputId);

                if (eyeIcon && passwordInput) {
                    eyeIcon.addEventListener('click', function () {
                        const type = passwordInput.type === 'password' ? 'text' : 'password';
                        passwordInput.type = type;
                        this.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                    });
                }
            }

            // Initialize all password toggles
            setupPasswordToggle('currentPassword', 'show-current-password');
            setupPasswordToggle('newPassword', 'show-new-password');
            setupPasswordToggle('confirmPassword', 'show-confirm-password');

            // ========== PASSWORD VALIDATION ==========
            if (fields.newPassword) {
                const requirements = [
                    { regex: /^.{8,}$/, index: 0 }, // At least 8 characters
                    { regex: /[A-Z]/, index: 1 },    // Uppercase letter
                    { regex: /[a-z]/, index: 2 },    // Lowercase letter
                    { regex: /\d/, index: 3 },       // Number
                    { regex: /[@$!%*#?&]/, index: 4 }, // Special character
                    { regex: /^\S*$/, index: 5 }     // No spaces
                ];

                let hidePasswordTimeout;

                fields.newPassword.addEventListener('focus', () => {
                    clearTimeout(hidePasswordTimeout);
                    passwordRequirements.style.display = 'block';
                });

                fields.newPassword.addEventListener('blur', () => {
                    hidePasswordTimeout = setTimeout(() => {
                        passwordRequirements.style.display = 'none';
                    }, PASSWORD_REQ_TIMEOUT);
                });

                fields.newPassword.addEventListener('input', function () {
                    const password = this.value;
                    
                    // Validate each requirement
                    requirements.forEach(item => {
                        const isValid = item.regex.test(password);
                        const reqItem = requirementItems[item.index];
                        reqItem.firstElementChild.className = isValid ? "fas fa-check-circle" : "fas fa-circle";
                        reqItem.classList.toggle('valid', isValid);
                    });

                    // Check if new password matches current password
                    if (fields.currentPassword && password === fields.currentPassword.value) {
                        showError(this, 'New password cannot be the same as current password');
                    } else {
                        clearError(this);
                    }

                    // Check password confirmation
                    if (fields.confirmPassword.value && password !== fields.confirmPassword.value) {
                        showError(fields.confirmPassword, 'Passwords do not match');
                    } else if (fields.confirmPassword.value) {
                        clearError(fields.confirmPassword);
                    }
                });

                fields.confirmPassword.addEventListener('input', function () {
                    if (this.value !== fields.newPassword.value) {
                        showError(this, 'Passwords do not match');
                    } else {
                        clearError(this);
                    }
                });
            }

            // ========== FORM VALIDATION ==========
            function initFormValidation() {
                // Email validation
                if (fields.email) {
                    fields.email.addEventListener('blur', validateEmail);
                }

                // Phone number validation and formatting
                if (fields.phone) {
                    fields.phone.addEventListener('input', formatPhoneNumber);
                    fields.phone.addEventListener('blur', validatePhoneNumber);
                }

                // Profile form submission
                if (forms.profile) {
                    forms.profile.addEventListener('submit', function(e) {
                        if (!validateProfileForm()) {
                            e.preventDefault();
                            scrollToFirstError();
                        }
                    });
                }

                // Password form submission
                if (forms.password) {
                    forms.password.addEventListener('submit', function(e) {
                        if (!validatePasswordForm()) {
                            e.preventDefault();
                            scrollToFirstError();
                        }
                    });
                }

                // Profile picture upload
                if (fields.profilePicture) {
                    fields.profilePicture.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            validateImageUpload(this.files[0])
                                .then(() => document.getElementById('upload_submit').click())
                                .catch(error => {
                                    showError(this, error.message);
                                });
                        }
                    });
                }
            }

            // ========== VALIDATION FUNCTIONS ==========
            function validateProfileForm() {
                let isValid = true;

                // Name validation
                if (fields.name && fields.name.value.trim().length < 2) {
                    showError(fields.name, 'Name must be at least 2 characters');
                    isValid = false;
                } else if (fields.name) {
                    clearError(fields.name);
                }

                // Email validation
                if (fields.email && !validateEmail(fields.email)) {
                    isValid = false;
                }

                // Phone validation
                if (fields.phone && !validatePhoneNumber(fields.phone)) {
                    isValid = false;
                }

                return isValid;
            }

            function validatePasswordForm() {
                let isValid = true;

                // Current password validation
                if (fields.currentPassword && fields.currentPassword.value.trim() === '') {
                    showError(fields.currentPassword, 'Current password is required');
                    isValid = false;
                } else if (fields.currentPassword) {
                    clearError(fields.currentPassword);
                }

                // New password validation
                if (fields.newPassword) {
                    const password = fields.newPassword.value;
                    
                    if (password.trim() === '') {
                        showError(fields.newPassword, 'New password is required');
                        isValid = false;
                    } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/.test(password)) {
                        showError(fields.newPassword, 'Password does not meet requirements');
                        isValid = false;
                    } else if (fields.currentPassword && password === fields.currentPassword.value) {
                        showError(fields.newPassword, 'New password cannot be the same as current password');
                        isValid = false;
                    } else {
                        clearError(fields.newPassword);
                    }

                    // Confirm password validation
                    if (fields.confirmPassword.value.trim() === '') {
                        showError(fields.confirmPassword, 'Please confirm your new password');
                        isValid = false;
                    } else if (fields.confirmPassword.value !== password) {
                        showError(fields.confirmPassword, 'Passwords do not match');
                        isValid = false;
                    } else {
                        clearError(fields.confirmPassword);
                    }
                }

                return isValid;
            }

            function validateEmail(inputElement = fields.email) {
                const email = inputElement.value.trim();
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                
                if (!emailRegex.test(email) || !email.endsWith('.com')) {
                    showError(inputElement, 'Please enter a valid email ending with .com');
                    return false;
                }
                
                clearError(inputElement);
                return true;
            }

            function formatPhoneNumber() {
                let phone = this.value.replace(/[^\d]/g, '');
                
                if (phone.length > 11) {
                    phone = phone.substring(0, 11);
                }
                
                let formatted = '';
                if (phone.length > 0) {
                    formatted = phone.substring(0, 3);
                    if (phone.length > 3) formatted += '-' + phone.substring(3, 6);
                    if (phone.length > 6) formatted += ' ' + phone.substring(6);
                }
                
                this.value = formatted;
            }

            function validatePhoneNumber(inputElement = fields.phone) {
                const phone = inputElement.value.replace(/[-\s]/g, '');
                const phoneRegex = /^(\+?6?01)[0-46-9][0-9]{7,8}$/;
                
                if (phone && !phoneRegex.test(phone)) {
                    showError(inputElement, 'Please enter a valid Malaysian phone number');
                    return false;
                }
                
                clearError(inputElement);
                return true;
            }

            async function validateImageUpload(file) {
                return new Promise((resolve, reject) => {
                    // Check file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    if (!validTypes.includes(file.type)) {
                        reject(new Error('Only JPG, JPEG, PNG & GIF files are allowed'));
                        return;
                    }

                    // Check file size (5MB max)
                    if (file.size > 5000000) {
                        reject(new Error('File size must be less than 5MB'));
                        return;
                    }

                    // Verify it's actually an image
                    const img = new Image();
                    img.onload = () => resolve();
                    img.onerror = () => reject(new Error('File is not a valid image'));
                    img.src = URL.createObjectURL(file);
                });
            }

            // ========== ERROR HANDLING ==========
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

            function clearAllErrors(form) {
                form.querySelectorAll('.error-field').forEach(field => {
                    field.classList.remove('error-field');
                });
                
                form.querySelectorAll('.error-message').forEach(error => {
                    error.remove();
                });
            }

            function scrollToFirstError() {
                const firstError = document.querySelector('.error-field');
                if (firstError) {
                    firstError.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'nearest'
                    });
                    firstError.focus();
                }
            }

            // ========== REAL-TIME VALIDATION ==========
            function setupRealTimeValidation() {
                const debounceTimers = {};

                function debounce(field, callback) {
                    clearTimeout(debounceTimers[field.name]);
                    debounceTimers[field.name] = setTimeout(() => callback(field), DEBOUNCE_DELAY);
                }

                // Name validation
                if (fields.name) {
                    fields.name.addEventListener('input', () => {
                        debounce(fields.name, validateName);
                    });
                }

                // Email validation
                if (fields.email) {
                    fields.email.addEventListener('input', () => {
                        debounce(fields.email, validateEmail);
                    });
                }

                // Phone validation
                if (fields.phone) {
                    fields.phone.addEventListener('input', () => {
                        debounce(fields.phone, validatePhoneNumber);
                    });
                }
            }

            function validateName(field) {
                const name = field.value.trim();
                if (name.length < 2) {
                    showError(field, 'Name must be at least 2 characters');
                    return false;
                }
                
                clearError(field);
                return true;
            }

            // ========== INITIALIZATION ==========
            initFormValidation();
            setupRealTimeValidation();

            // Initialize any existing errors from server-side validation
            document.querySelectorAll('.error-field').forEach(field => {
                const errorMessage = field.nextElementSibling?.textContent;
                if (errorMessage) {
                    showError(field, errorMessage);
                }
            });
        });
</script>

</body>
</html>