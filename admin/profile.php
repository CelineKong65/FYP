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
    $adminId = $_SESSION['AdminID'] ?? 0;

    if (!empty($field) && !empty($value)) {
        try {
            // Special handling for phone number
            if ($field === 'AdminPhoneNum') {
                // First validate format
                if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $value)) {
                    $response = [
                        'available' => false,
                        'valid_format' => false,
                        'message' => 'Format: XXX-XXX XXXX or XXX-XXXX XXXX'
                    ];
                    echo json_encode($response);
                    exit();
                }

                // Then validate Malaysian number format and check availability
                $stmt = $conn->prepare("SELECT AdminID FROM admin WHERE REPLACE(REPLACE(AdminPhoneNum, '-', ''), ' ', '') = ? AND AdminID != ?");
                $stmt->execute([preg_replace('/[-\s]/', '', $value), $adminId]);
                
                if (!preg_match("/^(\+?6?01)[0-46-9][0-9]{7,8}$/", preg_replace('/[-\s]/', '', $value))) {
                    $response = [
                        'available' => false,
                        'valid_format' => false,
                        'message' => 'Invalid Malaysian phone number'
                    ];
                } else {
                    $response['available'] = ($stmt->rowCount() === 0);
                }
                
            } else {
                // Other field validations (email, username, etc.)
                $stmt = $conn->prepare("SELECT AdminID FROM admin WHERE $field = ? AND AdminID != ?");
                $stmt->execute([$value, $adminId]);
                $response['available'] = ($stmt->rowCount() === 0);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
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

// Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
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
        
        // Delete old profile picture first (if exists and not default)
        $old_pic = $admin['AdminProfilePicture'] ?? '';
        if ($old_pic && $old_pic != 'admin.jpg') {
            $old_file_path = $target_dir . basename($old_pic);
            if (file_exists($old_file_path)) {
                @unlink($old_file_path);
            }
            
            // Also delete any other potential formats of the same admin's picture
            $old_pattern = $target_dir . "admin_" . $admin_id . ".*";
            $old_files = glob($old_pattern);
            foreach ($old_files as $file) {
                if ($file != $target_file && file_exists($file)) {
                    @unlink($file);
                }
            }
        }
        
        // Move new file and update database
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            // Update database with just the filename
            $sql = "UPDATE admin SET AdminProfilePicture = ? WHERE AdminID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_filename, $admin_id]);
            
            $success = "Profile picture updated successfully!";
            // Refresh admin data
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

// Account Information Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account'])) {
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

// Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['currentPassword'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors['password'] = 'All password fields are required';
    }
    
    if ($new_password !== $confirm_password) {
        $errors['confirmPassword'] = 'New passwords do not match';
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
                                   placeholder="XXX-XXX XXXX or XXX-XXXX XXXX" class="<?= isset($errors['phone']) ? 'error-field' : '' ?>">
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
                            <input type="password" name="currentPassword" id="currentPassword" value="<?= htmlspecialchars($admin['AdminPassword'] ?? 'Not provided') ?>" required
                                   class="<?= isset($errors['currentPassword']) ? 'error-field' : '' ?>">
                            <i class="fas fa-eye" id="show-current-password"></i>
                        </div>
                        <?php if (isset($errors['currentPassword'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['currentPassword']) ?></div>
                        <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            // ================== PASSWORD FEATURES ================== //
            function setupPasswordToggle(passwordInputId, eyeIconId) {
                const eyeIcon = document.getElementById(eyeIconId);
                const passwordInput = document.getElementById(passwordInputId);
                
                if (eyeIcon && passwordInput) {
                    eyeIcon.addEventListener('click', function() {
                        const type = passwordInput.type === 'password' ? 'text' : 'password';
                        passwordInput.type = type;
                        this.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                    });
                }
            }

            // Initialize password toggles
            setupPasswordToggle('currentPassword', 'show-current-password');
            setupPasswordToggle('newPassword', 'show-new-password');
            setupPasswordToggle('confirmPassword', 'show-confirm-password');

            // Make current password display-only but viewable
            const currentPasswordInput = document.querySelector('input[name="currentPassword"]');
            if (currentPasswordInput) {
                currentPasswordInput.readOnly = true; // Make it non-editable
                currentPasswordInput.type = 'password'; // Start masked
                const eyeIcon = document.getElementById('show-current-password');
                if (eyeIcon) {
                    eyeIcon.style.cursor = 'pointer'; // Show it's clickable
                }
            }

            function validatePasswordRequirements(password) {
                const requirements = [
                    /^.{8,}$/,      // At least 8 characters
                    /[A-Z]/,         // Uppercase letter
                    /[a-z]/,         // Lowercase letter
                    /\d/,            // Number
                    /[@$!%*#?&]/,    // Special character
                    /^\S*$/          // No spaces
                ];
                return requirements.every(regex => regex.test(password));
            }

            // DOM elements
            const passwordInput = document.getElementById('newPassword');
            const passwordRequirements = document.getElementById('passwordRequirements');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const passwordForm = document.getElementById('passwordForm');
            const updatePasswordBtn = document.querySelector('button[name="update_password"]');
            const requirementItems = document.querySelectorAll('.password-req li');

            if (passwordInput && passwordRequirements) {
                const requirements = [
                    {regex: /^.{8,}$/, index: 0}, // At least 8 characters
                    {regex: /[A-Z]/, index: 1},    // Uppercase letter
                    {regex: /[a-z]/, index: 2},    // Lowercase letter
                    {regex: /\d/, index: 3},       // Number
                    {regex: /[@$!%*#?&]/, index: 4}, // Special character
                    {regex: /^\S*$/, index: 5}     // No spaces
                ];

                // Show requirements on focus
                passwordInput.addEventListener('focus', () => {
                    passwordRequirements.style.display = 'block';
                });

                // Hide requirements on blur
                passwordInput.addEventListener('blur', () => {
                    setTimeout(() => {
                        if (!passwordRequirements.contains(document.activeElement)) {
                            passwordRequirements.style.display = 'none';
                        }
                    }, 200);
                });

                // Real-time validation for new password
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    
                    // Update requirement indicators
                    requirements.forEach(req => {
                        const item = requirementItems[req.index];
                        const icon = item.querySelector('i');
                        const isValid = req.regex.test(password);
                        
                        icon.className = isValid ? 'fas fa-check-circle text-success' : 'fas fa-circle';
                        item.classList.toggle('valid', isValid);
                    });

                    validatePasswordFields();
                });

                // Real-time validation for confirm password
                confirmPasswordInput.addEventListener('input', function() {
                    validatePasswordFields();
                });

                function validatePasswordFields() {
                    const newPassword = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    const currentPassword = currentPasswordInput?.value;
                    
                    // Clear previous errors
                    clearError(passwordInput);
                    clearError(confirmPasswordInput);
                    
                    // Validate new password requirements
                    if (newPassword && !validatePasswordRequirements(newPassword)) {
                        showError(passwordInput, 'Password must contain: 8+ chars, uppercase, lowercase, number, special char');
                        disableUpdateButton();
                        return;
                    }
                    
                    // Check if new password matches current password
                    if (newPassword && currentPassword && newPassword === currentPassword) {
                        showError(passwordInput, 'New password cannot be the same as current password');
                        disableUpdateButton();
                        return;
                    }
                    
                    // Check if passwords match
                    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                        showError(confirmPasswordInput, 'Passwords do not match');
                        disableUpdateButton();
                        return;
                    }
                    
                    // If all validations pass
                    if (newPassword && confirmPassword && 
                        newPassword === confirmPassword && 
                        validatePasswordRequirements(newPassword) &&
                        (!currentPassword || newPassword !== currentPassword)) {
                        enableUpdateButton();
                    } else {
                        disableUpdateButton();
                    }
                }

                function disableUpdateButton() {
                    if (updatePasswordBtn) {
                        updatePasswordBtn.disabled = true;
                        updatePasswordBtn.classList.add('disabled-btn');
                    }
                }

                function enableUpdateButton() {
                    if (updatePasswordBtn) {
                        updatePasswordBtn.disabled = false;
                        updatePasswordBtn.classList.remove('disabled-btn');
                    }
                }

                // Initial validation
                validatePasswordFields();
            }

            // Form submission handler for password form
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    if (!validatePasswordForm()) {
                        e.preventDefault();
                        const firstError = this.querySelector('.error-field');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });
            }

            function validatePasswordForm() {
                const newPassword = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const currentPassword = currentPasswordInput?.value;
                let isValid = true;
                
                // Clear all errors first
                clearError(passwordInput);
                clearError(confirmPasswordInput);

                // Validate new password exists
                if (!newPassword.trim()) {
                    showError(passwordInput, 'New password is required');
                    isValid = false;
                }
                // Validate password requirements
                else if (!validatePasswordRequirements(newPassword)) {
                    showError(passwordInput, 'Password must contain: 8+ chars, uppercase, lowercase, number, special char');
                    isValid = false;
                }
                // Check if matches current password
                else if (currentPassword && newPassword === currentPassword) {
                    showError(passwordInput, 'New password cannot be the same as current password');
                    isValid = false;
                }

                // Validate confirm password
                if (!confirmPassword.trim()) {
                    showError(confirmPasswordInput, 'Please confirm your password');
                    isValid = false;
                } else if (confirmPassword !== newPassword) {
                    showError(confirmPasswordInput, 'Passwords do not match');
                    isValid = false;
                }

                return isValid;
            }

            // ================== GENERAL VALIDATION FUNCTIONS ================== //
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
            const profilePictureInput = document.getElementById('profile_picture');
            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        document.getElementById('upload_submit').click();
                    }
                });
            }

            function addRealTimeValidation(inputElement, validationFn) {
                inputElement.addEventListener('input', function () {
                    clearError(this);
                    const message = validationFn(this.value.trim());
                    if (message) {
                        showError(this, message);
                    }
                });
            }

            function validateName(value) {
                if (!value) return 'Full name is required';
                if (!/^[a-zA-Z\s]+$/.test(value)) return 'Name should contain only letters and spaces';
                return '';
            }

            function validateEmail(value) {
                if (!value) return 'Email is required';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Please enter a valid email address';
                return '';
            }

            function validatePhone(value) {
                if (!value) return 'Phone number is required';
                if (!/^(\d{3}-\d{3} \d{4}|\d{3}-\d{4} \d{4})$/.test(value)) {
                    return 'Please enter a valid phone number (XXX-XXX XXXX or XXX-XXXX XXXX)';
                }
                return '';
            }

            // ================== PROFILE FORM VALIDATION ================== //
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Validate name
                    const nameError = validateName(nameInput.value.trim());
                    if (nameError) {
                        showError(nameInput, nameError);
                        isValid = false;
                    }
                    const emailError = validateEmail(emailInput.value.trim());
                    if (emailError) {
                        showError(emailInput, emailError);
                        isValid = false;
                    }
                    const phoneError = validatePhone(phoneInput.value.trim());
                    if (phoneError) {
                        showError(phoneInput, phoneError);
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        const firstError = this.querySelector('.error-field');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });
            }
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');

            addRealTimeValidation(nameInput, validateName);
            addRealTimeValidation(emailInput, validateEmail);
            addRealTimeValidation(phoneInput, validatePhone);
        });
</script>
</body>
</html>