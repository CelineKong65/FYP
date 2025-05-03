<?php
session_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
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
    }
    
    if (empty($custEmail)) {
        $errors['custEmail'] = "Email is required";
    } elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $custEmail)) {
        $errors['custEmail'] = "Invalid Gmail format";
    } else {
        // Check if email exists for another user
        $stmt = $conn->prepare("SELECT CustID FROM customer WHERE CustEmail = ? AND CustID != ?");
        $stmt->execute([$custEmail, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors['custEmail'] = "Email already exists";
        }
    }
    
    if (!empty($custPhoneNum)) {
        // Remove all formatting to check the actual digits
        $cleanPhone = preg_replace('/[-\s]/', '', $custPhoneNum);
        
        if (!preg_match("/^(\+?6?01)[0-46-9][0-9]{7,8}$/", $cleanPhone)) {
            $errors['custPhoneNum'] = "Invalid Malaysian phone format (e.g., 017-510 0205)";
        } else {
            $stmt = $conn->prepare("SELECT CustID FROM customer WHERE REPLACE(REPLACE(CustPhoneNum, '-', ''), ' ', '') = ? AND CustID != ?");
            $stmt->execute([$cleanPhone, $user_id]);
            if ($stmt->rowCount() > 0) {
                $errors['custPhoneNum'] = "Phone number already exists";
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
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate new password meets requirements
    if (empty($newPassword)) {
        $errors['newPassword'] = "New password is required";
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $newPassword)) {
        $errors['newPassword'] = "Password must be at least 8 characters with letters, numbers and special chars";
    }
    
    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $errors['confirmPassword'] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE customer SET CustPassword = ? WHERE CustID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$newPassword, $user_id]);
            
            $success = "Password updated successfully!";
        } catch (PDOException $e) {
            $errors['general'] = "Error updating password: " . $e->getMessage();
        }
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
            <div class="account-number">Account ID: <?= htmlspecialchars($customer['CustID']) ?></div>
            
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
                                placeholder="017-510 0205"
                                maxlength="12"
                                oninput="formatPhoneNumber(this)"
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
                        <?php if (isset($errors['newPassword'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['newPassword']) ?></div>
                        <?php endif; ?>
                        <ul class="password-req" id="passwordRequirements">
                            <li><i class="fas fa-circle"></i><span>At least 8 characters</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 uppercase letter</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 lowercase letter</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 number</span></li>
                            <li><i class="fas fa-circle"></i><span>At least 1 special symbol</span></li>
                            <li><i class="fas fa-circle"></i><span>No spaces</span></li>
                        </ul>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Confirm New Password</label>
                        <div class="pass-field">
                            <input type="password" name="confirmPassword" required
                                   class="<?= isset($errors['confirmPassword']) ? 'error-field' : '' ?>">
                            <i class="fas fa-eye" id="show-confirm-password"></i>
                        </div>
                        <?php if (isset($errors['confirmPassword'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['confirmPassword']) ?></div>
                        <?php endif; ?>
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
        // Password visibility toggle
        function setupPasswordToggle(passwordInputId, eyeIconId) {
            const eyeIcon = document.getElementById(eyeIconId);
            const passwordInput = document.getElementById(passwordInputId) || 
                                document.querySelector(`input[name="${passwordInputId}"]`);
            
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

        // Password strength meter
        const passwordInput = document.getElementById('newPassword');
        const passwordRequirements = document.getElementById('passwordRequirements');
        if (passwordInput && passwordRequirements) {
            const requirementList = document.querySelectorAll('.password-req li');
            const requirements = [
                {regex: /\S{8,}/, index: 0},        // At least 8 characters
                {regex: /[A-Z]/, index: 1},         // Uppercase letter
                {regex: /[a-z]/, index: 2},         // Lowercase letter
                {regex: /\d/, index: 3},            // Number
                {regex: /[@$!%*#?&]/, index: 4},    // Special character
                {regex: /^\S*$/, index: 5}          // No spaces
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

            // Real-time password validation
            passwordInput.addEventListener('input', function(e) {
                const currentPassword = document.querySelector('input[name="currentPassword"]')?.value;
                
                // Clear previous same-password errors
                this.parentNode.querySelectorAll('.error-message').forEach(msg => {
                    if (msg.textContent === 'New password cannot be the same as current password') {
                        msg.remove();
                    }
                });

                // Check password match
                if (currentPassword && this.value === currentPassword) {
                    showError(this, 'New password cannot be the same as current password');
                }

                // Update requirement indicators
                requirements.forEach(item => {
                    const isValid = item.regex.test(e.target.value);
                    const requirementItem = requirementList[item.index];
                    requirementItem.firstElementChild.className = isValid ? 
                        "fas fa-check-circle" : "fas fa-circle";
                    requirementItem.classList.toggle('valid', isValid);
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
            document.querySelector('input[name="custEmail"]')?.addEventListener('blur', validateEmail);
            document.querySelector('input[name="custPhoneNum"]')?.addEventListener('input', validatePhoneNumber);
            document.querySelector('input[name="postcode"]')?.addEventListener('input', validatePostcode);
            document.querySelector('select[name="state"]')?.addEventListener('change', validateState);
            document.querySelector('input[name="city"]')?.addEventListener('blur', validateCity);
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
                
                // Custom messages
                if (fieldName === 'state') message = 'Please select a state';
                if (fieldName === 'postcode') message = 'Postcode is required';
                if (fieldName === 'city') message = 'City is required';
                if (fieldName === 'custPhoneNum') message = 'Phone number is required';
                
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

        function validatePostcode() {
            // Restrict to 5 digits
            this.value = this.value.replace(/\D/g, '').substring(0, 5);
            
            // Validate length
            if (this.value.length !== 5 && this.value.length > 0) {
                showError(this, 'Must be 5 digits');
            }
        }

        function validateState() {
            if (this.value === '') {
                showError(this, 'Please select a state');
            }
        }

        function validateCity() {
            if (this.value.trim() === '') {
                showError(this, 'City is required');
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

<?php
include 'footer.php'; 
?>