<?php
session_start();

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db_connection.php';

$loggedInAdminID = $_SESSION['AdminID'];
$position_check_query = "SELECT AdminPosition FROM admin WHERE AdminID = ?";
$stmt = $conn->prepare($position_check_query);
$stmt->bind_param("i", $loggedInAdminID);
$stmt->execute();
$result = $stmt->get_result();
$adminData = $result->fetch_assoc();
$loggedInPosition = $adminData['AdminPosition'];
$stmt->close();

// Function to generate random password
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

$admin_query = "SELECT * FROM admin ORDER BY 
                CASE WHEN AdminStatus = 'Active' THEN 0 ELSE 1 END, 
                AdminName ASC";
$admin_result = $conn->query($admin_query);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt = $conn->prepare("
        SELECT * FROM admin 
        WHERE BINARY AdminName LIKE ? OR BINARY AdminEmail LIKE ? 
        ORDER BY 
            AdminPosition = 'superadmin' DESC,
            AdminStatus = 'Active' DESC,
            AdminName ASC
    ");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $admin_result = $stmt->get_result();
} else {
    $admin_result = $conn->query("
        SELECT * FROM admin 
        ORDER BY 
            AdminPosition = 'superadmin' DESC,
            AdminStatus = 'Active' DESC,
            AdminName ASC
    ");
}

if (isset($_POST['update_admin'])) {
    $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);

    // Handle profile picture upload
    $image_name = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $profile_picture = $_FILES['profile_picture'];
        $image_extension = strtolower(pathinfo($profile_picture['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];

        if (!in_array($image_extension, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, JPEG, and PNG allowed.']);
            exit();
        }

        $stmt = $conn->prepare("SELECT AdminProfilePicture FROM admin WHERE AdminID = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->bind_result($existing_picture);
        $stmt->fetch();
        $stmt->close();

        $target_dir = "../image/admin/";
        if (!empty($existing_picture) && file_exists($target_dir . $existing_picture)) {
            unlink($target_dir . $existing_picture);
        }

        $image_name = "admin_" . $admin_id . "." . $image_extension;
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($profile_picture['tmp_name'], $target_file)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
            exit();
        }
    } else {
        $stmt = $conn->prepare("SELECT AdminProfilePicture FROM admin WHERE AdminID = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->bind_result($image_name);
        $stmt->fetch();
        $stmt->close();
    }

    $check_name_query = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("si", $name, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Admin name already exists. Please use a different name.']);
        exit();
    }
    $stmt->close();  

    $check_email_query = "SELECT AdminID FROM admin WHERE AdminEmail = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $admin_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different email.']);
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("si", $phone, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.']);
        exit();
    }
    $stmt->close();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format (must end with .com)']);
        exit();
    }

    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format']);
        exit();
    }

    $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ?, AdminProfilePicture = ? WHERE AdminID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $name, $email, $phone, $image_name, $admin_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update admin.']);
    }
    $stmt->close();
    exit();
}

if (isset($_POST['add_admin'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $phone = trim($_POST['phone']);
    $position = trim($_POST['position']);
    $status = 'Active';
    
    // Generate random 8-character password
    $password = generateRandomPassword(8);

    $check_name_query = "SELECT AdminID FROM admin WHERE AdminName = ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Admin name already exists. Please use a different name.']);
        exit();
    }
    $stmt->close();

    $check_email_query = "SELECT AdminID FROM admin WHERE AdminEmail = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different email.']);
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? ";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.']);
        exit();
    }
    $stmt->close();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format (must end with .com)']);
        exit();
    }

    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format']);
        exit();
    }

    $insert_query = "INSERT INTO admin (AdminName, AdminEmail, AdminPassword, AdminPhoneNum, AdminPosition, AdminStatus) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $position, $status);

    if ($stmt->execute()) {
        $new_admin_id = $conn->insert_id;
        
        // Handle profile picture upload for new admin
        if (!empty($_FILES['profile_picture']['name'])) {
            $profile_picture = $_FILES['profile_picture'];
            $image_extension = strtolower(pathinfo($profile_picture['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png'];

            if (in_array($image_extension, $allowed_types)) {
                $target_dir = "../image/admin/";
                $image_name = "admin_" . $new_admin_id . "." . $image_extension;
                $target_file = $target_dir . $image_name;

                if (move_uploaded_file($profile_picture['tmp_name'], $target_file)) {
                    $update_pic_query = "UPDATE admin SET AdminProfilePicture = ? WHERE AdminID = ?";
                    $stmt2 = $conn->prepare($update_pic_query);
                    $stmt2->bind_param("si", $image_name, $new_admin_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
            }
        }

        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'lawrencetan20050429@gmail.com'; //Your Gmail
            $mail->Password = 'khzd gkui ieyv aadf'; //Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('panzhixin99@gmail.com', 'WaterSport_Equipment'); // Your Gmail
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your New Admin Account Password';
            $mail->Body = "
                <h3>Your admin account has been created successfully!</h3>
                <p>Here are your login details:</p>
                <p><strong>Admin ID:</strong> {$new_admin_id}</p>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p>Please login and change your password immediately for security reasons.</p>
                <p>This is an automated message. Please do not reply.</p>
            ";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Admin added successfully! Password has been sent to the admin\'s email.']);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'message' => 'Admin added successfully but failed to send password email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add admin.']);
    }
    $stmt->close();
    exit();
}

if (isset($_POST['toggle_status'])) {
    $admin_id = intval($_POST['admin_id']);
    $current_status = $_POST['current_status'];
    
    $check_position = $conn->prepare("SELECT AdminPosition FROM admin WHERE AdminID = ?");
    $check_position->bind_param("i", $admin_id);
    $check_position->execute();
    $result = $check_position->get_result();
    $admin = $result->fetch_assoc();
    
    if ($admin['AdminPosition'] === 'superadmin') {
        $_SESSION['error'] = 'Cannot modify status of superadmin.';
    } else {
        $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';
        
        $update_query = "UPDATE admin SET AdminStatus = ? WHERE AdminID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: admin_view.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'name') {
        $sql = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $value, $admin_id);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } elseif ($type === 'email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $is_valid_format = false;
            $error_message = "Invalid email format";
        } elseif (!preg_match('/\.com$/', $value)) {
            $is_valid_format = false;
            $error_message = "Email must end with .com";
        } else {
            $sql = "SELECT AdminID FROM admin WHERE AdminEmail = ? AND AdminID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $admin_id);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
        }
    } elseif ($type === 'phone') {
        if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $value)) {
            $is_valid_format = false;
            $error_message = "Invalid phone format (XXX-XXX XXXX or XXX-XXXX XXXX)";
        } else {
            $sql = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? AND AdminID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $admin_id);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists, 'valid_format' => $is_valid_format, 'message' => $error_message]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin List</title>
    <link rel="stylesheet" href="admin_view.css">
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
            <h2>Admin List</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>

            <?php if ($loggedInPosition === 'superadmin'): ?>        
                <div class="add">
                    <button onclick="openAddModal()" class="add_btn">Add Admin</button>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">ID</th>
                        <th>Profile Picture</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th style="text-align: center;">Position</th>
                        <?php if ($loggedInPosition === 'superadmin'): ?>
                        <th style="text-align: center;">Status</th>
                            <th style="width: 180px;"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($admin_result && $admin_result->num_rows > 0): ?>
                        <?php while ($admin = $admin_result->fetch_assoc()): ?>
                            <?php
                            $admin_id = $admin['AdminID'];
                            $jpgPath = "../image/admin/admin_" . $admin_id . ".jpg";
                            $jpegPath = "../image/admin/admin_" . $admin_id . ".jpeg";
                            $pngPath = "../image/admin/admin_" . $admin_id . ".png";
                            $defaultPath = "../image/admin/admin.jpg";

                            if (file_exists($jpgPath)) {
                                $profile_pic_path = $jpgPath;
                            } elseif (file_exists($jpegPath)) {
                                $profile_pic_path = $jpegPath;
                            } elseif (file_exists($pngPath)) {
                                $profile_pic_path = $pngPath;
                            } else {
                                $profile_pic_path = $defaultPath;
                            }
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $admin['AdminID']; ?></td>
                                <td><img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" class="profile-pic"></td>
                                <td><?php echo $admin['AdminName']; ?></td>
                                <td><?php echo $admin['AdminEmail']; ?></td>
                                <td><?php echo $admin['AdminPhoneNum']; ?></td>
                                <td style="text-align: center;"><?php echo $admin['AdminPosition']; ?></td>
                                <?php if ($loggedInPosition === 'superadmin'): ?>
                                    <td class="<?php echo ($admin['AdminStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>" style="text-align: center;">
                                        <?php echo $admin['AdminStatus']; ?>
                                    </td>
                                    <td>
                                    <button name="edit_admin" onclick='openEditModal({
                                        id: <?php echo json_encode($admin["AdminID"]); ?>,
                                        name: <?php echo json_encode($admin["AdminName"]); ?>,
                                        email: <?php echo json_encode($admin["AdminEmail"]); ?>,
                                        phone: <?php echo json_encode($admin["AdminPhoneNum"]); ?>,
                                        position: <?php echo json_encode($admin["AdminPosition"]); ?>
                                    })'>Edit</button>
                                        <?php if ($admin['AdminPosition'] === 'superadmin'): ?>
                                            <button type="button" class="btn-disabled" title="Cannot modify superadmin status">
                                                <?php echo ($admin['AdminStatus'] == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        <?php else: ?>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['AdminID']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $admin['AdminStatus']; ?>">
                                                <button type="submit" class="btn-status <?php echo ($admin['AdminStatus'] == 'Active') ? 'btn-inactive' : 'btn-active'; ?>">
                                                    <?php echo ($admin['AdminStatus'] == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No admin found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Admin</h3>
            <form method="POST" action="" enctype="multipart/form-data" id="editAdminForm">
                <input type="hidden" name="admin_id" id="admin_id">
                
                <div class="edit-form">
                    <div class="left">
                        <label>Profile Picture:<span> (.jpg,.jpeg or .png only)</span></label>
                        <input class="img" type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png">
                        
                        <label>Name:</label>
                        <input type="text" name="name" id="name" required onblur="checkAvailability('name', this.value, document.getElementById('admin_id').value)">
                        <div id="name-error" class="error-message"></div>

                        <label>Email:</label>
                        <input type="email" name="email" id="email" placeholder="example@gmail.com" required onblur="checkAvailability('email', this.value, document.getElementById('admin_id').value)">
                        <div id="email-error" class="error-message"></div>
                    </div>
                    
                    <div class="right">
                        <label>Phone:</label>
                        <input type="text" name="phone" id="phone" placeholder="XXX-XXX XXXX or XXX-XXXX XXXX format" required onblur="checkAvailability('phone', this.value, document.getElementById('admin_id').value)">
                        <div id="phone-error" class="error-message"></div>

                        <label>Position:</label>
                        <div id="position-container" class="readonly-field"></div>
                    </div>
                </div>
                
                <div class="upd_div">
                    <button type="submit" name="update_admin">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Admin</h3>
            <form method="POST" action="" enctype="multipart/form-data" id="addAdminForm">
                <div class="edit-form">
                    <div class="left">
                        <label>Profile Picture:<span> (.jpg,.jpeg or .png only)</span></label>
                        <input class="img" type="file" name="profile_picture" id="add-profile_picture" accept=".jpg,.jpeg,.png">
                        
                        <label>Name:</label>
                        <input type="text" name="name" id="add-name" required onblur="checkAvailability('name', this.value)">
                        <div id="add-name-error" class="error-message"></div>
                        
                        <label>Email:</label>
                        <input type="email" name="email" id="add-email" placeholder="example@gmail.com" required onblur="checkAvailability('email', this.value)">
                        <div id="add-email-error" class="error-message"></div>
                    </div>
                    
                    <div class="right">
                        <label>Phone:</label>
                        <input type="text" name="phone" id="add-phone" placeholder="XXX-XXX XXXX or XXX-XXXX XXXX format" required onblur="checkAvailability('phone', this.value)">
                        <div id="add-phone-error" class="error-message"></div>
                        
                        <label>Position:</label>
                        <select name="position" id="add-position" required>
                            <option value="admin">admin</option>
                            <option value="superadmin">superadmin</option>
                        </select>
                    </div>
                </div>
                
                <div class="add_div">
                    <button type="submit" name="add_admin">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to clear all error messages
        function clearAllErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            document.querySelectorAll('.error-field, .valid-field').forEach(el => {
                el.classList.remove('error-field', 'valid-field');
            });
        }

        // Function to validate form
        function validateForm(formType) {
            let isValid = true;
            const requiredFields = ['name', 'email', 'phone'];
            const prefix = formType === 'edit' ? '' : 'add-';
            
            // Check basic required fields
            requiredFields.forEach(field => {
                const fieldId = prefix + field;
                const fieldValue = document.getElementById(fieldId).value.trim();
                const errorElement = document.getElementById(fieldId + '-error');
                const inputField = document.getElementById(fieldId);
                
                if (!fieldValue) {
                    errorElement.textContent = `${field.charAt(0).toUpperCase() + field.slice(1)} is required`;
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                    inputField.classList.remove('valid-field');
                    isValid = false;
                }
            });

            // Validate email format
            const email = document.getElementById(prefix + 'email').value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.com$/.test(email)) {
                document.getElementById(prefix + 'email-error').textContent = 'Invalid email format (must end with .com)';
                document.getElementById(prefix + 'email-error').style.display = 'block';
                document.getElementById(prefix + 'email').classList.add('error-field');
                document.getElementById(prefix + 'email').classList.remove('valid-field');
                isValid = false;
            }

            // Validate phone format
            const phone = document.getElementById(prefix + 'phone').value.trim();
            if (phone && !/^\d{3}-\d{3,4} \d{4}$/.test(phone)) {
                document.getElementById(prefix + 'phone-error').textContent = 'Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format';
                document.getElementById(prefix + 'phone-error').style.display = 'block';
                document.getElementById(prefix + 'phone').classList.add('error-field');
                document.getElementById(prefix + 'phone').classList.remove('valid-field');
                isValid = false;
            }

            return isValid;
        }

        // Function to check availability of name, email, phone
        function checkAvailability(type, value, adminId = 0) {
            const prefix = adminId === 0 ? 'add-' : '';
            const errorElement = document.getElementById(prefix + type + '-error');
            const inputField = document.getElementById(prefix + type);
            
            errorElement.textContent = '';
            errorElement.style.display = 'none';
            inputField.classList.remove('error-field', 'valid-field');

            if (!value.trim() && (type === 'name' || type === 'email' || type === 'phone')) {
                errorElement.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} is required`;
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                return;
            }

            let isValidFormat = true;
            let formatErrorMessage = '';
            
            if (type === 'email') {
                if (!/^[^\s@]+@[^\s@]+\.com$/.test(value)) {
                    isValidFormat = false;
                    formatErrorMessage = "Invalid email format (must contain @ and end with .com)";
                }
            } else if (type === 'phone') {
                if (!/^\d{3}-\d{3,4} \d{4}$/.test(value)) {
                    isValidFormat = false;
                    formatErrorMessage = "Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format";
                }
            }

            if (!isValidFormat) {
                errorElement.textContent = formatErrorMessage;
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                return;
            }

            const formData = new FormData();
            formData.append('check_availability', 'true');
            formData.append('type', type);
            formData.append('value', value);
            formData.append('admin_id', adminId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (!data.valid_format) {
                    errorElement.textContent = data.message;
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                } else if (data.exists) {
                    errorElement.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} already exists`;
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    inputField.classList.remove('error-field');
                    inputField.classList.add('valid-field');
                }
            })
            .catch(error => {
                console.error('Error checking availability:', error);
            });
        }

        // Handle form submissions with AJAX
        document.getElementById('editAdminForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm('edit')) {
                return;
            }

            const formData = new FormData(this);
            formData.append('update_admin', 'true');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating admin.');
            });
        });

        document.getElementById('addAdminForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm('add')) {
                return;
            }

            const formData = new FormData(this);
            formData.append('add_admin', 'true');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding admin.');
            });
        });

        // In the openEditModal function in JavaScript (which is called when clicking Edit):
        function openEditModal(adminData) {
            clearAllErrors();

            document.getElementById('admin_id').value = adminData.id;
            document.getElementById('name').value = adminData.name;
            document.getElementById('email').value = adminData.email;
            document.getElementById('phone').value = adminData.phone;

            // Display position as text instead of editable dropdown
            const positionHTML = `
                <input type="text" value="${adminData.position}" readonly class="readonly-input">
                <input type="hidden" name="position" value="${adminData.position}">
            `;
            document.getElementById('position-container').innerHTML = positionHTML;

            document.getElementById('editModal').style.display = 'block';
        }

        // Close Edit Modal
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editAdminForm').reset();
            clearAllErrors();
        }

        // Open Add Modal
        function openAddModal() {
            clearAllErrors();
            document.getElementById('addAdminForm').reset();
            document.getElementById('addModal').style.display = 'block';
        }

        // Close Add Modal
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('addAdminForm').reset();
            clearAllErrors();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const searchForm = document.querySelector('.search');
            
            if (searchInput && searchForm) {
                searchInput.addEventListener('input', function() {
                    if (this.value.trim() === '') {
                        searchForm.submit();
                    }
                });
            }
        });

        // Optional: close modal when clicking outside the modal box
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const addModal = document.getElementById('addModal');
            if (event.target === editModal) {
                closeModal();
            }
            if (event.target === addModal) {
                closeAddModal();
            }
        };
    </script>
</body>
</html>