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

$upload_error = '';
$upload_success = '';
$form_error = '';
$form_success = '';

// Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    // ... (keep your existing profile picture upload code) ...
}

// Account Information Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account'])) {
    $custName = trim($_POST['custName']);
    $custEmail = trim($_POST['custEmail']);
    $custPhoneNum = trim($_POST['custPhoneNum']);
    $custAddress = trim($_POST['custAddress']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $state = trim($_POST['state']);
    
    // Validate email format
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $custEmail)) {
        $form_error = "Invalid gmail format";
    }
    // Validate phone format
    elseif (!preg_match("/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/", $custPhoneNum)) {
        $form_error = "Invalid Malaysian phone number (e.g., 012-3456789)";
    }
    // Validate postcode format
    elseif (!preg_match("/^\d{5}$/", $postcode)) {
        $form_error = "Please enter a valid 5-digit postcode";
    } else {
        try {
            $sql = "UPDATE customer SET 
                    CustName = ?, CustEmail = ?, CustPhoneNum = ?, 
                    CustAddress = ?, City = ?, Postcode = ?, State = ?
                    WHERE CustID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $custName, $custEmail, $custPhoneNum,
                $custAddress, $city, $postcode, $state,
                $user_id
            ]);
            
            $form_success = "Account information updated successfully!";
            // Refresh customer data
            $stmt->execute([$user_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $form_error = "Error updating account: " . $e->getMessage();
        }
    }
}

// Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate current password
    if (!password_verify($currentPassword, $customer['CustPassword'])) {
        $form_error = "Current password is incorrect";
    }
    // Validate new password format
    elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $newPassword)) {
        $form_error = "Password must follow requirements";
    }
    // Check password confirmation
    elseif ($newPassword !== $confirmPassword) {
        $form_error = "Passwords do not match";
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE customer SET CustPassword = ? WHERE CustID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$hashedPassword, $user_id]);
            
            $form_success = "Password updated successfully!";
        } catch (PDOException $e) {
            $form_error = "Error updating password: " . $e->getMessage();
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
    <link rel="stylesheet" href="account.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>webiance</h2>
        </div>
        <ul class="sidebar-menu">
            <li>My job Feed</li>
            <li class="active"><a href="#"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="#"><i class="fas fa-comment"></i> Desiricord</a></li>
            <li><a href="#"><i class="fas fa-bookmark"></i> Saved jobs</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="order_history.php"><i class="fas fa-history"></i> Order History</a></li>
        </ul>
        <div class="sidebar-footer">
            <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> LOG OUT</button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($customer['CustProfilePicture'] ?? 'images/default-profile.jpg') ?>" 
                 alt="Profile Picture" 
                 class="profile-picture"
                 onerror="this.src='images/default-profile.jpg'">
            <h1 class="profile-title">Welcome, <?= htmlspecialchars($customer['CustName']) ?></h1>
            <div class="account-number">Account Number: <?= htmlspecialchars($customer['CustID']) ?></div>
            
            <form class="upload-form" method="post" enctype="multipart/form-data">
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
                <label for="profile_picture"><i class="fas fa-camera"></i> Upload a New Photo</label>
                <button type="submit" style="display: none;" id="upload_submit"></button>
            </form>
            
            <?php if (!empty($upload_success)): ?>
                <div class="message success"><?= $upload_success ?></div>
            <?php endif; ?>
            <?php if (!empty($upload_error)): ?>
                <div class="message error"><?= $upload_error ?></div>
            <?php endif; ?>
        </div>
        
        <div class="profile-container">
            <form method="POST" action="">
                <div class="form-section">
                    <h3>Change User information here</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Full Name</label>
                            <input type="text" name="custName" value="<?= htmlspecialchars($customer['CustName']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Email Address</label>
                            <input type="email" name="custEmail" value="<?= htmlspecialchars($customer['CustEmail']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Address</label>
                            <input type="text" name="custAddress" value="<?= htmlspecialchars($customer['CustAddress'] ?? 'Not provided') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($customer['City'] ?? 'Not provided') ?>">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <select name="state" class="state">
                                <option value="">Select State</option>
                                <?php
                                $states = [
                                    "Johor", "Melaka", "Negeri Sembilan", "Kedah", "Kelantan", 
                                    "Pahang", "Penang", "Perak", "Perlis", "Sabah", 
                                    "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur", "Putrajaya"
                                ];
                                foreach ($states as $stateOption) {
                                    $selected = ($customer['State'] == $stateOption) ? 'selected' : '';
                                    echo "<option value=\"$stateOption\" $selected>$stateOption</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Postcode</label>
                            <input type="text" name="postcode" value="<?= htmlspecialchars($customer['Postcode'] ?? 'Not provided') ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="custPhoneNum" value="<?= htmlspecialchars($customer['CustPhoneNum'] ?? 'Not provided') ?>">
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
                            <input type="password" name="currentPassword" required>
                            <i class="fas fa-eye" id="show-current-password"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">New Password</label>
                        <div class="pass-field">
                            <input type="password" name="newPassword" id="newPassword" required>
                            <i class="fas fa-eye" id="show-new-password"></i>
                        </div>
                        <ul class="password-req">
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
                            <input type="password" name="confirmPassword" required>
                            <i class="fas fa-eye" id="show-confirm-password"></i>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_password" class="update-btn">Update Password</button>
                </div>
            </form>
            
            <?php if (!empty($form_success)): ?>
                <div class="message success"><?= $form_success ?></div>
            <?php endif; ?>
            <?php if (!empty($form_error)): ?>
                <div class="message error"><?= $form_error ?></div>
            <?php endif; ?>
            
            <div class="welcome-message">
                <h3>Welcome to Watersport Equipment</h3>
                <p>Last updated: <?= date('F j, Y, g:i a', strtotime($customer['updated_at'] ?? 'now')) ?></p>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when profile picture is selected
        document.getElementById('profile_picture').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('upload_submit').click();
            }
        });
        
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
        
        // Password requirement validation
        const passwordInput = document.getElementById('newPassword');
        const requirementList = document.querySelectorAll('.password-req li');
        
        const requirements = [
            {regex: /\S{8,}/, index: 0},
            {regex: /[A-Z]/, index: 1},
            {regex: /[a-z]/, index: 2},
            {regex: /\d/, index: 3},
            {regex: /[@$!%*#?&]/, index: 4},
            {regex: /^\S*$/, index: 5}
        ];
        
        if (passwordInput) {
            passwordInput.addEventListener('keyup', (e) => {
                requirements.forEach(item => {
                    const isValid = item.regex.test(e.target.value);
                    const requirementItem = requirementList[item.index];
                    
                    requirementItem.firstElementChild.className = isValid ? 
                        "fas fa-check-circle" : "fas fa-circle";
                    requirementItem.classList.toggle('valid', isValid);
                });
            });
        }
        
        // Real-time validation for email and phone
        document.querySelector('input[name="custEmail"]').addEventListener('blur', function() {
            validateEmail(this.value);
        });
        
        document.querySelector('input[name="custPhoneNum"]').addEventListener('blur', function() {
            validatePhone(this.value);
        });
        
        function validateEmail(email) {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid Gmail address');
                return false;
            }
            return true;
        }
        
        function validatePhone(phone) {
            const phoneRegex = /^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/;
            if (!phoneRegex.test(phone)) {
                alert('Please enter a valid Malaysian phone number (e.g., 012-3456789)');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

<?php
include 'footer.php'; 
?>