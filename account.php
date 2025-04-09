<?php
// session_start();
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

// Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $target_dir = "image/";
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            $upload_error = "Failed to create upload directory.";
        };
    }
    
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if directory is writable
    if (!is_writable($target_dir)) {
        $upload_error = "Upload directory is not writable.";
    } else {
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check === false) {
            $upload_error = "File is not an image.";
        } 
        // Check file size (500KB max)
        elseif ($_FILES["profile_picture"]["size"] > 500000) {
            $upload_error = "Sorry, your file is too large (max 500KB).";
        } 
        // Allow certain file formats
        elseif (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            $upload_error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            // Generate filename
            $new_filename = "user_" . $user_id . "." . $imageFileType;
            $target_file = $target_dir . $new_filename;
            
            // Delete exist picture
            if (!empty($customer['CustProfilePicture']) && file_exists($customer['CustProfilePicture'])) {
                unlink($customer['CustProfilePicture']);
            }
            
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                // Update new profile to database
                $update_stmt = $conn->prepare("UPDATE customer SET CustProfilePicture = ? WHERE CustID = ?");
                $update_stmt->execute([$target_file, $user_id]);
                
                // Refresh customer data
                $stmt->execute([$user_id]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $upload_success = "Profile picture updated successfully!";
            } else {
                $upload_error = "Sorry, there was an error uploading your file.";
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
    <title>My Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="account.css"> 
</head>
<body>
    <div class="container">
            <div class="profile-header">
                <img src="<?= htmlspecialchars($customer['CustProfilePicture'] ?? 'images/default-profile.jpg') ?>" 
                    alt="Profile Picture" 
                    class="profile-picture"
                    onerror="this.src='images/default-profile.jpg'">
                <h1>Welcome, <?= htmlspecialchars($customer['CustName']) ?></h1>
                <div class="account-number">Account Number: <?= htmlspecialchars($customer['CustID']) ?></div>
                
                <form class="upload-form" method="post" enctype="multipart/form-data">
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
                    <label for="profile_picture"><i class="fas fa-camera"></i> Change Profile Picture</label>
                    <button type="submit" style="display: none;" id="upload_submit"></button>
                </form>
                
                <?php if (!empty($upload_success)): ?>
                    <div class="message success"><?= $upload_success ?></div>
                <?php endif; ?>
                <?php if (!empty($upload_error)): ?>
                    <div class="message error"><?= $upload_error ?></div>
                <?php endif; ?>
            </div>

            <div class="profile-content">
                <div class="profile-nav">
                    <h2>My Account</h2>
                    <ul class="bar">
                        <li class="option"><a href="my-address.php"><i class="fas fa-map-marker-alt"></i> My Address: <?= htmlspecialchars($customer['CustAddress'] ?? 'Not provided') ?></a></li>
                        <li class="option"><a href="order_history.php"><i class="fas fa-history"></i> Purchase History</a></li>
                        <li class="option"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>

                <div class="profile-main">
                    <div class="welcome-message">
                        <h3>Welcome to Watersport Equipment</h3>
                        <p>Email: <?= htmlspecialchars($customer['CustEmail']) ?></p>
                        <p>Phone: <?= htmlspecialchars($customer['CustPhoneNum'] ?? 'Not provided') ?></p>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // Auto-submit form when file is selected
        document.getElementById('profile_picture').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('upload_submit').click();
            }
        });
    </script>
</body>
</html>

<?php
include 'footer.php'; 
?>