<?php
session_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['ajax_check'])) {
    header('Content-Type: application/json');
    $response = ['available' => true];
    
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;

    if (!empty($field) && !empty($value)) {
        try {
            // Special handling for phone number
            if ($field === 'CustPhoneNum') {
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
                $stmt = $conn->prepare("SELECT CustID FROM customer WHERE REPLACE(REPLACE(CustPhoneNum, '-', ''), ' ', '') = ? AND CustID != ?");
                $stmt->execute([preg_replace('/[-\s]/', '', $value), $userId]);
                
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
                $stmt = $conn->prepare("SELECT CustID FROM customer WHERE $field = ? AND CustID != ?");
                $stmt->execute([$value, $userId]);
                $response['available'] = ($stmt->rowCount() === 0);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    echo json_encode($response);
    exit();
}

// Get customer data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM customer WHERE CustID = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("Customer not found");
}

$errors = [];
$success = '';

// Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $file_type = $_FILES['profile_picture']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        $errors['profile_picture'] = "Only JPG, JPEG, PNG & GIF files are allowed";
    } elseif ($_FILES['profile_picture']['size'] > 5000000) { // 5MB max
        $errors['profile_picture'] = "File size must be less than 5MB";
    } else {
        $target_dir = "image/user/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = "user_" . $user_id . "." . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            // Delete old profile picture if it exists and isn't the default
            $old_pic = $customer['CustProfilePicture'] ?? '';
            if ($old_pic && $old_pic != 'image/user/default-profile.jpg' && file_exists($old_pic)) {
                unlink($old_pic);
            }
            
            // Update database
            $sql = "UPDATE customer SET CustProfilePicture = ? WHERE CustID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$target_file, $user_id]);
            
            $success = "Profile picture updated successfully!";
            // Refresh customer data
            $stmt = $conn->prepare("SELECT * FROM customer WHERE CustID = ?");
            $stmt->execute([$user_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors['profile_picture'] = "Sorry, there was an error uploading your file";
        }
    }
}

// Account Information Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account'])) {
    $custName = trim($_POST['custName']);
    $custEmail = trim($_POST['custEmail']);
    $custPhoneNum = trim($_POST['custPhoneNum']);
    $streetAddress = trim($_POST['streetAddress']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $state = trim($_POST['state']);
    
    // Validate fields
    if (empty($custName)) {
        $errors['custName'] = "Full name is required";
    } else {
        // Check if name is already taken
        $stmt = $conn->prepare("SELECT CustID FROM customer WHERE CustName = ? AND CustID != ?");
        $stmt->execute([$custName, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors['custName'] = "The name \"$custName\" is already taken. Please choose a different name.";
        }
    }
    
    if (empty($custEmail)) {
        $errors['custEmail'] = "Email is required";
    } elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $custEmail)) {
        $errors['custEmail'] = "Invalid Gmail format. Exp: example@gmail.com";
    } else {
        // Check if email exists for another user
        $stmt = $conn->prepare("SELECT CustID FROM customer WHERE CustEmail = ? AND CustID != ?");
        $stmt->execute([$custEmail, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors['custEmail'] = "Email already exists";
        }
    }
    
    if (!empty($custPhoneNum)) {
        // First validate the format
        if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $custPhoneNum)) {
            $errors['custPhoneNum'] = "Format: XXX-XXX XXXX or XXX-XXXX XXXX";
        } else {
            // Then validate it's a proper Malaysian number
            $cleanPhone = preg_replace('/[-\s]/', '', $custPhoneNum);
            if (!preg_match("/^(\+?6?01)[0-46-9][0-9]{7,8}$/", $cleanPhone)) {
                $errors['custPhoneNum'] = "Invalid Malaysian phone number";
            } else {
                // Then check for duplicates
                $stmt = $conn->prepare("SELECT CustID FROM customer WHERE REPLACE(REPLACE(CustPhoneNum, '-', ''), ' ', '') = ? AND CustID != ?");
                $stmt->execute([$cleanPhone, $user_id]);
                if ($stmt->rowCount() > 0) {
                    $errors['custPhoneNum'] = "Phone number already exists";
                }
            }
        }
    }
    
    if (!empty($postcode)) {
        if (!preg_match("/^\d{5}$/", $postcode)) {
            $errors['postcode'] = "Postcode must be exactly 5 digits (numbers only)";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE customer SET 
                    CustName = ?, CustEmail = ?, CustPhoneNum = ?, 
                    StreetAddress = ?, City = ?, Postcode = ?, State = ?
                    WHERE CustID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $custName, $custEmail, $custPhoneNum,
                $streetAddress, $city, $postcode, $state,
                $user_id
            ]);
            
            $success = "Account information updated successfully!";
            // Refresh customer data
            $stmt = $conn->prepare("SELECT * FROM customer WHERE CustID = ?");
            $stmt->execute([$user_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errors['general'] = "Error updating account: " . $e->getMessage();
        }
    }
}

   // Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errors['password'] = 'All password fields are required';
    }
    
    $password_query = "SELECT CustPassword FROM customer WHERE CustID = ?";
    $stmt = $conn->prepare($password_query);
    $stmt->execute([$user_id]);
    $db_password = $stmt->fetchColumn();

    if (empty($errors)) {
        $update_query = "UPDATE customer SET CustPassword = ? WHERE CustID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->execute([$newPassword, $user_id]);
        
        $stmt = $conn -> prepare("SELECT * FROM customer WHERE CustID = ?");
        $stmt->execute([$user_id]);
        $customer = $stmt -> fetch(PDO::FETCH_ASSOC);
        $success = 'Password updated successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="account.css">
    <style>
    .error-message {
        color: #e74c3c;
        font-size: 0.8rem;
        margin-top: 5px;
        display: block;
    }
    .error-field {
        border-color: #e74c3c !important;
    }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="#"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="order_history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="rate_products.php"><i class="fa fa-star" style="color: white;"></i>Rate</a></li>
            <li><a href="topup.php"><i class="fa-solid fa-money-bill" style="color: white;"></i>Top Up</a></li>
        </ul>
        <div class="sidebar-footer">
            <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> LOG OUT</button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($customer['CustProfilePicture'] ?? 'image/user/default-profile.jpg') ?>" 
                 alt="Profile Picture" 
                 class="profile-picture"
                 onerror="this.src='image/user/user.png'">
            <h1 class="profile-title">Welcome, <?= htmlspecialchars($customer['CustName']) ?></h1>
            
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
            <form method="POST" action="">
                <div class="form-section">
                    <h3>Change User information here</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Full Name</label>
                            <input type="text" name="custName" value="<?= htmlspecialchars($customer['CustName']) ?>" required
                                   class="<?= isset($errors['custName']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['custName'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['custName']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="required">Email Address</label>
                            <input type="email" name="custEmail" value="<?= htmlspecialchars($customer['CustEmail']) ?>" required
                                   class="<?= isset($errors['custEmail']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['custEmail'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['custEmail']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Street Address</label>
                            <input type="text" name="streetAddress" value="<?= htmlspecialchars($customer['StreetAddress'] ?? 'Not provided') ?>" required
                                   class="<?= isset($errors['streetAddress']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['streetAddress'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['streetAddress']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($customer['City'] ?? 'Not provided') ?>" required class="<?= isset($errors['city']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['city'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['city']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <select name="state" required class="state <?= isset($errors['state']) ? 'error-field' : '' ?>">
                                <option value="">Select State</option>
                                <?php
                                $states = [
                                    "Johor", "Melaka", "Negeri Sembilan", "Kedah", "Kelantan", 
                                    "Pahang", "Penang", "Perak", "Perlis", "Sabah", 
                                    "Sarawak", "Selangor", "Terengganu"
                                ];
                                foreach ($states as $stateOption) {
                                    $selected = ($customer['State'] == $stateOption) ? 'selected' : '';
                                    echo "<option value=\"$stateOption\" $selected>$stateOption</option>";
                                }
                                ?>
                            </select>
                            <?php if (isset($errors['state'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['state']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                    <div class="form-group">
                        <label>Postcode</label>
                        <input type="text" name="postcode" required
                            value="<?= htmlspecialchars($customer['Postcode'] ?? '') ?>" 
                            pattern="\d{5}" 
                            title="5-digit postcode"
                            maxlength="5"
                            oninput="this.value = this.value.replace(/\D/g, '')"
                            class="<?= isset($errors['postcode']) ? 'error-field' : '' ?>">
                        <?php if (isset($errors['postcode'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['postcode']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                            <input type="text" name="custPhoneNum" required
                                value="<?= htmlspecialchars($customer['CustPhoneNum'] ?? '') ?>" 
                                placeholder="XXX-XXX XXXX or XXX-XXXX XXXX"
                                class="<?= isset($errors['custPhoneNum']) ? 'error-field' : '' ?>">
                            <?php if (isset($errors['custPhoneNum'])): ?>
                                <div class="error-message"><?= htmlspecialchars($errors['custPhoneNum']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_account" class="update-btn">Update Information</button>
                </div>
            </form>
            
            <form method="POST" action="">
                <div class="form-section">
                    <h3>Change Password</h3>
                    <div class="form-group">
                        <label class="required">Current Password</label>
                        <div class="pass-field">
                            <input type="password" name="currentPassword" id="currentPassword" value="<?= htmlspecialchars($customer['CustPassword'] ?? 'Not provided') ?>" required
                                   class="<?= isset($errors['currentPassword']) ? 'error-field' : '' ?>">
                            <i class="fas fa-eye" id="show-current-password"></i>
                        </div>
                        <?php if (isset($errors['currentPassword'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['currentPassword']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">New Password</label>
                        <div class="pass-field">
                            <input type="password" name="newPassword" id="newPassword" required
                                class="<?= isset($errors['newPassword']) ? 'error-field' : '' ?>">
                            <i class="fas fa-eye" id="show-new-password"></i>
                        </div>
                        <div id="newPassword-error" class="error-message">
                            <?php if (isset($errors['newPassword'])): ?>
                                <?= htmlspecialchars($errors['newPassword']) ?>
                            <?php endif; ?>
                        </div>
                        <ul class="password-req" id="passwordRequirements">
                            <li data-requirement="length"><i class="fas fa-circle"></i><span>At least 8 characters</span></li>
                            <li data-requirement="uppercase"><i class="fas fa-circle"></i><span>At least 1 uppercase letter</span></li>
                            <li data-requirement="lowercase"><i class="fas fa-circle"></i><span>At least 1 lowercase letter</span></li>
                            <li data-requirement="number"><i class="fas fa-circle"></i><span>At least 1 number</span></li>
                            <li data-requirement="special"><i class="fas fa-circle"></i><span>At least 1 special symbol</span></li>
                            <li data-requirement="spaces"><i class="fas fa-circle"></i><span>No spaces</span></li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label class="required">Confirm New Password</label>
                        <div class="pass-field">
                            <input type="password" name="confirmPassword" id="confirmPassword" required
                                class="<?= isset($errors['confirmPassword']) ? 'error-field' : '' ?>">
                            <i class="fas fa-eye" id="show-confirm-password"></i>
                        </div>
                        <div id="confirmPassword-error" class="error-message">
                            <?php if (isset($errors['confirmPassword'])): ?>
                                <?= htmlspecialchars($errors['confirmPassword']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_password" class="update-btn">Update Password</button>
                </div>
            </form>
            
            <div class="welcome-message">
                <h3>Welcome to Watersport Equipment</h3>
                <p>Last updated: <?= date('F j, Y, g:i a', strtotime($customer['updated_at'] ?? 'now')) ?></p>
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
    const passwordForm = document.querySelector('form[action*="update_password"]');
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

    // Form submission handler
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            if (validatePasswordForm()) {
                // If validation passes, prepare for password update
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the current password display
                        currentPasswordInput.value = document.getElementById('newPassword').value;
                        // Show success message
                        alert('Password updated successfully!');
                        // Optionally reload the page to reflect changes
                        location.reload();
                    } else {
                        alert('Error updating password: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating password');
                });
                
                e.preventDefault(); // Prevent default form submission
            } else {
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
    document.getElementById('profile_picture').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            document.getElementById('upload_submit').click();
        }
    });
});
</script>
</body>
</html>

<?php
include 'footer.php'; 
?>