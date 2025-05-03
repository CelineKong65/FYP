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

    $check_name_query = "SELECT AdminID FROM admin WHERE AdminName = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("si", $name, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Admin name already exists. Please use a different name.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();  

    $check_email_query = "SELECT AdminID FROM admin WHERE AdminEmail = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $admin_id);
    $stmt->execute();
    $stmt->store_result();

    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? AND AdminID != ?";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("si", $phone, $admin_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo "<script>alert('Invalid email format (must end with .com)'); window.location.href='admin_view.php';</script>";
        exit();
    }

    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo "<script>alert('Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format'); window.location.href='admin_view.php';</script>";
        exit();
    }
    
    $original_query = "SELECT * FROM admin WHERE AdminID = ?";
    $stmt = $conn->prepare($original_query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $original_result = $stmt->get_result();
    $original_data = $original_result->fetch_assoc();
    $stmt->close();

    $has_changes = (
        $name !== $original_data['AdminName'] ||
        $email !== $original_data['AdminEmail'] ||
        $phone !== $original_data['AdminPhoneNum'] ||
        $position !== $original_data['AdminPosition']
    );

    if (!$has_changes) {
        echo "<script>alert('No changes detected.'); window.location.href='admin_view.php';</script>";
        exit();
    } else {
            $update_query = "UPDATE admin SET AdminName = ?, AdminEmail = ?, AdminPhoneNum = ?, AdminPosition = ? WHERE AdminID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $name, $email, $phone, $position, $admin_id);
        }
    
        if ($stmt->execute()) {
            echo "<script>alert('Admin updated successfully!'); window.location.href='admin_view.php';</script>";
        } else {
            echo "<script>alert('Failed to update admin.'); window.location.href='admin_view.php';</script>";
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
        echo "<script>alert('Admin name already exists. Please use a different name.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_email_query = "SELECT AdminID FROM admin WHERE AdminEmail = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode([
            'error' => 'Email already exists. Please use a different email.',
            'field' => 'email'
        ]);
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT AdminID FROM admin WHERE AdminPhoneNum = ? ";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='admin_view.php';</script>";
        exit();
    }
    $stmt->close();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo "<script>alert('Invalid email format (must end with .com)'); window.location.href='admin_view.php';</script>";
        exit();
    }

    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo "<script>alert('Contact number must be in XXX-XXX XXXX or XXX-XXXX XXXX format'); window.location.href='admin_view.php';</script>";
        exit();
    }

    $insert_query = "INSERT INTO admin (AdminName, AdminEmail, AdminPassword, AdminPhoneNum, AdminPosition, AdminStatus) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $position, $status);

    if ($stmt->execute()) {
        $new_admin_id = $conn->insert_id;
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
            echo "<script>alert('Admin added successfully! Password has been sent to the admin\'s email.); window.location.href='admin_view.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Admin added successfully but failed to send password email. ); window.location.href='admin_view.php';</script>";
        }
        
        header("Location: admin_view.php");
        exit();
    } else {
        echo "<script>alert('Failed to add admin.'); window.location.href='admin_view.php';</script>";
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
                            <tr>
                                <td style="text-align: center;"><?php echo $admin['AdminID']; ?></td>
                                <td><?php echo $admin['AdminName']; ?></td>
                                <td><?php echo $admin['AdminEmail']; ?></td>
                                <td><?php echo $admin['AdminPhoneNum']; ?></td>
                                <td style="text-align: center;"><?php echo $admin['AdminPosition']; ?></td>
                                <?php if ($loggedInPosition === 'superadmin'): ?>
                                    <td class="<?php echo ($admin['AdminStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>" style="text-align: center;">
                                        <?php echo $admin['AdminStatus']; ?>
                                    </td>
                                    <td>
                                        <button name="edit_admin" onclick='editAdmin(
                                                <?php echo json_encode($admin["AdminID"]); ?>,
                                                <?php echo json_encode($admin["AdminName"]); ?>,
                                                <?php echo json_encode($admin["AdminEmail"]); ?>,
                                                <?php echo json_encode($admin["AdminPhoneNum"]); ?>,
                                                <?php echo json_encode($admin["AdminPosition"]); ?>,
                                                <?php echo json_encode($admin["AdminPassword"]); ?>)'>Edit
                                        </button>
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
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="admin_id" id="admin_id">
                <label>Name:</label>
                <input type="text" name="name" id="name" required onblur="checkAvailability('name', this.value, document.getElementById('admin_id').value)">
                <div id="name-error" class="error"></div>

                <label>Email:</label>
                <input type="email" name="email" id="email" placeholder="example@gmail.com" required onblur="checkAvailability('email', this.value, document.getElementById('admin_id').value)">
                <div id="email-error" class="error"></div>

                <label>Phone:</label>
                <input type="text" name="phone" id="phone" placeholder="XXX-XXX XXXX or XXX-XXXX XXXX format" required onblur="checkAvailability('phone', this.value, document.getElementById('admin_id').value)">
                <div id="phone-error" class="error"></div>

                <label>Position:</label>
                <div id="position-container">

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
            <form method="POST" action="" enctype="multipart/form-data">
                <label>Name:</label>
                <input type="text" name="name" id="add-name" required onblur="checkAvailability('name', this.value)">
                <div id="add-name-error" class="error"></div>
                
                <label>Email:</label>
                <input type="email" name="email" id="add-email" placeholder="example@gmail.com" required onblur="checkAvailability('email', this.value)">
                <div id="add-email-error" class="error"></div>
                
                <label>Phone:</label>
                <input type="text" name="phone" id="add-phone" placeholder="XXX-XXX XXXX or XXX-XXXX XXXX format" required onblur="checkAvailability('phone', this.value)">
                <div id="add-phone-error" class="error"></div>
                
                <label>Position:</label>
                <select name="position" id="add-position" required>
                    <option value="admin">admin</option>
                    <option value="superadmin">superadmin</option>
                </select>
                
                <div class="add_div">
                    <button type="submit" name="add_admin">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
function validateField(type, value, prefix = '') {
    const errorElement = document.getElementById(`${prefix}${type}-error`);
    const inputField = document.getElementById(`${prefix}${type}`);

    // Clear previous states
    errorElement.textContent = '';
    errorElement.style.display = 'none';
    inputField?.classList.remove('error-field', 'valid-field');

    // Required field check
    if (!value.trim()) {
        errorElement.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} is required`;
        errorElement.style.display = 'block';
        inputField?.classList.add('error-field');
        return false;
    }

    // Format validation
    let isValid = true;
    switch(type) {
        case 'email':
            isValid = /^[^\s@]+@[^\s@]+\.com$/.test(value);
            if (!isValid) {
                errorElement.textContent = "Invalid email format (must contain @ and end with .com)";
            }
            break;
        case 'phone':
            isValid = /^\d{3}-\d{3,4} \d{4}$/.test(value);
            if (!isValid) {
                errorElement.textContent = "Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format";
            }
            break;
    }

    if (!isValid) {
        errorElement.style.display = 'block';
        inputField?.classList.add('error-field');
        return false;
    }

    inputField?.classList.add('valid-field');
    return true;
}

// Field validation handlers
function setupValidationHandlers() {
    // Edit modal fields
    document.getElementById('name')?.addEventListener('blur', function() {
        validateField('name', this.value);
    });

    document.getElementById('email')?.addEventListener('blur', function() {
        validateField('email', this.value);
    });

    document.getElementById('phone')?.addEventListener('blur', function() {
        validateField('phone', this.value);
    });

    // Add modal fields
    document.getElementById('add-name')?.addEventListener('blur', function() {
        validateField('name', this.value, 'add-');
    });

    document.getElementById('add-email')?.addEventListener('blur', function() {
        validateField('email', this.value, 'add-');
    });

    document.getElementById('add-phone')?.addEventListener('blur', function() {
        validateField('phone', this.value, 'add-');
    });
}

// Form submission handling
function initializeFormSubmissions() {
    // Add form submission
    document.querySelector('#addModal form')?.addEventListener('submit', function(e) {
        const isValid = [
            validateField('name', document.getElementById('add-name').value, 'add-'),
            validateField('email', document.getElementById('add-email').value, 'add-'),
            validateField('phone', document.getElementById('add-phone').value, 'add-')
        ].every(result => result);

        if (!isValid) {
            e.preventDefault();
            alert('Please fix all errors before submitting.');
        }
    });

    // Edit form submission
    document.querySelector('#editModal form')?.addEventListener('submit', function(e) {
        const isValid = [
            validateField('name', document.getElementById('name').value),
            validateField('email', document.getElementById('email').value),
            validateField('phone', document.getElementById('phone').value)
        ].every(result => result);

        if (!isValid) {
            e.preventDefault();
            alert('Please fix all errors before submitting.');
        }
    });
}

// Modal management functions
function editAdmin(admin_id, name, email, phone, position) {
    clearAllErrors();
    document.getElementById('admin_id').value = admin_id;
    document.getElementById('name').value = name;
    document.getElementById('email').value = email;
    document.getElementById('phone').value = phone;

    const positionContainer = document.getElementById('position-container');
    positionContainer.innerHTML = position === 'superadmin' ? 
        `<div class="position-display">${position}</div>
         <input type="hidden" name="position" value="superadmin">` :
        `<select name="position" id="position" required>
            <option value="admin" ${position === 'admin' ? 'selected' : ''}>admin</option>
            <option value="superadmin" ${position === 'superadmin' ? 'selected' : ''}>superadmin</option>
         </select>`;

    document.getElementById('addModal').style.display = 'none';
    document.getElementById('editModal').style.display = 'block';
}

function openAddModal() {
    clearAllErrors();
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('addModal').style.display = 'block';
    document.querySelector('#addModal form').reset();
}

// UI helpers
function clearAllErrors() {
    ['', 'add-'].forEach(prefix => {
        ['name', 'email', 'phone'].forEach(type => {
            const input = document.getElementById(`${prefix}${type}`);
            const error = document.getElementById(`${prefix}${type}-error`);
            input?.classList.remove('error-field', 'valid-field');
            error.textContent = '';
            error.style.display = 'none';
        });
    });
}

// Initial setup
document.addEventListener('DOMContentLoaded', function() {
    setupValidationHandlers();
    initializeFormSubmissions();
    
    // Search functionality
    const searchInput = document.querySelector('input[name="search"]');
    searchInput?.addEventListener('input', function() {
        if (this.value.trim() === '') {
            this.closest('form').submit();
        }
    });
});

// Modal close handlers
window.onclick = function(event) {
    if (event.target.matches('.modal')) {
        event.target.style.display = 'none';
        clearAllErrors();
    }
};
</script>
</body>
</html>