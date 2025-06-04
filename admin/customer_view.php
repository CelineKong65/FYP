<?php
session_start();

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

$customer_query = "SELECT * FROM customer ORDER BY 
                  CASE WHEN CustomerStatus = 'Active' THEN 0 ELSE 1 END, 
                  CustName ASC";
$customer_result = $conn->query($customer_query);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $customer_query = "SELECT * FROM customer 
                       WHERE CustName LIKE ? OR CustEmail LIKE ? 
                       ORDER BY CASE WHEN CustomerStatus = 'Active' THEN 0 ELSE 1 END, 
                       CustName ASC";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $customer_result = $stmt->get_result();
} else {
    $customer_query = "SELECT * FROM customer ORDER BY 
    CASE WHEN CustomerStatus = 'Active' THEN 0 ELSE 1 END, 
    CustName ASC";
    $customer_result = $conn->query($customer_query);
}

if (isset($_POST['update_customer'])) {
    $cust_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 0;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $street = trim($_POST['street']);
    $postcode = trim($_POST['postcode']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']); 

    if (empty($name) || empty($email) || empty($phone)) {
        echo "<script>alert('Name, email and phone are required.'); window.location.href='customer_view.php';</script>";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo "<script>alert('Invalid email format. Email must be valid and end with .com'); window.location.href='customer_view.php';</script>";
        exit();
    }
    
    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo "<script>alert('Invalid phone number format. Use XXX-XXX XXXX or XXX-XXXX XXXX.'); window.location.href='customer_view.php';</script>";
        exit();
    }

    $image_name = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $profile_picture = $_FILES['profile_picture'];
        $image_extension = strtolower(pathinfo($profile_picture['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];

        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='customer_view.php';</script>";
            exit();
        }

        $stmt = $conn->prepare("SELECT CustProfilePicture FROM customer WHERE CustID = ?");
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->bind_result($existing_picture);
        $stmt->fetch();
        $stmt->close();

        $target_dir = "../image/user/";
        if (!empty($existing_picture) && file_exists($target_dir . $existing_picture)) {
            unlink($target_dir . $existing_picture);
        }

        $image_name = "user_" . $cust_id . "." . $image_extension;
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($profile_picture['tmp_name'], $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='customer_view.php';</script>";
            exit();
        }
    } else {
        $stmt = $conn->prepare("SELECT CustProfilePicture FROM customer WHERE CustID = ?");
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->bind_result($image_name);
        $stmt->fetch();
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT CustID FROM customer WHERE CustEmail = ? AND CustID != ?");
    $stmt->bind_param("si", $email, $cust_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT CustID FROM customer WHERE CustPhoneNum = ? AND CustID != ?");
    $stmt->bind_param("si", $phone, $cust_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    $update_query = "UPDATE customer SET 
                    CustName = ?, 
                    CustEmail = ?, 
                    CustPhoneNum = ?, 
                    StreetAddress = ?, 
                    Postcode = ?, 
                    City = ?, 
                    State = ?, 
                    CustProfilePicture = ? 
                    WHERE CustID = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssssi", $name, $email, $phone, $street, $postcode, $city, $state, $image_name, $cust_id);

    if ($stmt->execute()) {
        echo "<script>alert('Customer updated successfully!'); window.location.href='customer_view.php';</script>";
    } else {
        echo "<script>alert('Failed to update customer: " . addslashes($conn->error) . "'); window.location.href='customer_view.php';</script>";
    }
    
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $custID = (int)$_POST['cust_id'];
    $currentStatus = strtolower($_POST['current_status']);
    $newStatus = ($currentStatus == 'active') ? 'Inactive' : 'Active';

    $stmt = $conn->prepare("UPDATE customer SET CustomerStatus = ? WHERE CustID = ?");
    $stmt->bind_param("si", $newStatus, $custID);

    if ($stmt->execute()) {
        header("Location: customer_view.php");
        exit();
    } else {
        echo "Status update failed: " . $conn->error;
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $cust_id = isset($_POST['cust_id']) ? (int)$_POST['cust_id'] : 0;
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $is_valid_format = false;
            $error_message = "Invalid email format";
        } elseif (!preg_match('/\.com$/', $value)) {
            $is_valid_format = false;
            $error_message = "Email must end with .com";
        } else {
            $sql = "SELECT CustID FROM customer WHERE CustEmail = ? AND CustID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $cust_id);
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
            $sql = "SELECT CustID FROM customer WHERE CustPhoneNum = ? AND CustID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $cust_id);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
        }
    }

    echo json_encode(['exists' => $exists, 'valid_format' => $is_valid_format, 'message' => $error_message]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List</title>
    <link rel="stylesheet" href="customer_view.css">
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
            <h2>Customer List</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th style="text-align: center;">Profile Picture</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th style="width: 120px;">Phone</th>
                        <th style="width: 400px;">Address</th>
                        <th>Status</th> 
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customer_result->num_rows > 0): ?>
                        <?php while ($customer = $customer_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $customer['CustID']; ?></td>
                                <td style="text-align: center;">
                                    <?php
                                        $imageSrc = $customer['CustProfilePicture'] ? '../image/user/' . $customer['CustProfilePicture'] : '../image/user/user.png';
                                    ?>
                                    <img src="<?= $imageSrc ?>" alt="<?= $customer['CustProfilePicture'] ?>" class="cust-pic">
                                </td>
                                <td><?php echo $customer['CustName']; ?></td>
                                <td><?php echo $customer['CustEmail']; ?></td>
                                <td><?php echo $customer['CustPhoneNum']; ?></td>
                                <td>
                                    <?php
                                    $full_address = trim($customer['StreetAddress'] . ', ' . $customer['Postcode'] . ' ' . $customer['City'] . ', ' . $customer['State']);
                                    echo htmlspecialchars($full_address);
                                    ?>
                                </td>
                                <td class="<?php echo ($customer['CustomerStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $customer['CustomerStatus']; ?>
                                </td>
                                <td>
                                <button name="edit_customer" onclick='editCustomer(
                                    <?php echo json_encode($customer["CustID"]); ?>,
                                    <?php echo json_encode($customer["CustName"]); ?>,
                                    <?php echo json_encode($customer["CustEmail"]); ?>,
                                    <?php echo json_encode($customer["CustPhoneNum"]); ?>,
                                    <?php echo json_encode($customer["StreetAddress"]); ?>,
                                    <?php echo json_encode($customer["Postcode"]); ?>,
                                    <?php echo json_encode($customer["City"]); ?>,
                                    <?php echo json_encode($customer["State"]); ?>,
                                    <?php echo json_encode($customer["CustProfilePicture"]); ?>
                                )'>Edit
                                </button>
                                    <form method="post" action="">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="cust_id" value="<?php echo $customer['CustID']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $customer['CustomerStatus']; ?>">
                                        <button type="submit" class="btn-status <?php echo ($customer['CustomerStatus'] == 'Active') ? 'btn-inactive' : 'btn-active'; ?>">
                                            <?php echo ($customer['CustomerStatus'] == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No customers found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Customer</h3>
            <form method="POST" action="" enctype="multipart/form-data" class="edit" id="editCustomerForm">
                <input type="hidden" name="cust_id" id="cust_id">

                <div class="edit-form">
                    <div class="left">
                        <label>Profile Picture:<span> (.jpg,.jpeg or .png only)</span></label>
                        <input class="img" type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png">
                        <label>Name:<span class="required">*</span></label>
                        <input type="text" name="name" id="name" required onblur="checkAvailability('name', this.value)">
                        <div id="name-error" class="error-message"></div>
                        <label>Email:<span class="required">*</span></label>
                        <input type="email" name="email" id="email" placeholder="example@gmail.com" required onblur="checkAvailability('email', this.value)">
                        <div id="email-error" class="error-message"></div>
                        <label>Phone:<span class="required">*</span></label>
                        <input type="text" name="phone" id="phone" placeholder="XXX-XXX XXXX or XXX-XXXX XXXX format" required onblur="checkAvailability('phone', this.value)">
                        <div id="phone-error" class="error-message"></div>
                    </div>
                    <div class="right">
                        <label>Street Address:<span class="required">*</span></label>
                        <input type="text" name="street" id="street" required>
                        <div id="street-error" class="error-message"></div>
                        <label>Postcode:<span class="required">*</span></label>
                        <input type="text" name="postcode" id="postcode" required>
                        <div id="postcode-error" class="error-message"></div>
                        <label>City:<span class="required">*</span></label>
                        <input type="text" name="city" id="city" required>
                        <div id="city-error" class="error-message"></div>
                        <label>State:<span class="required">*</span></label>
                        <select name="state" id="state" required>
                            <option value="" disabled selected>-- Select City/State --</option>
                            <option value="Johor">Johor</option>
                            <option value="Kedah">Kedah</option>
                            <option value="Kelantan">Kelantan</option>
                            <option value="Melaka">Melaka</option>
                            <option value="Negeri Sembilan">Negeri Sembilan</option>
                            <option value="Pahang">Pahang</option>
                            <option value="Pulau Pinang">Pulau Pinang</option>
                            <option value="Perak">Perak</option>
                            <option value="Perlis">Perlis</option>
                            <option value="Selangor">Selangor</option>
                            <option value="Terengganu">Terengganu</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Sarawak">Sarawak</option>
                        </select>
                        <div id="state-error" class="error-message"></div>
                    </div>
                </div>            

                <div class="upd_div">
                    <button type="submit" name="update_customer">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateAddressFields() {
            const street = document.getElementById('street');
            const postcode = document.getElementById('postcode');
            const city = document.getElementById('city');
            const state = document.getElementById('state');

            // First clear all previous errors
            const fields = [street, postcode, city, state];
            fields.forEach(field => {
                const errorElement = document.getElementById(`${field.id}-error`);
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                field.classList.remove('error-field');
            });

            let isValid = true;

            const streetVal = street.value.trim();
            const postcodeVal = postcode.value.trim();
            const cityVal = city.value.trim();
            const stateVal = state.value.trim();

            // Make all address fields required (like name, email, phone)
            if (!streetVal) {
                document.getElementById('street-error').textContent = 'Street address is required';
                document.getElementById('street-error').style.display = 'block';
                street.classList.add('error-field');
                isValid = false;
            }

            if (!postcodeVal) {
                document.getElementById('postcode-error').textContent = 'Postcode is required';
                document.getElementById('postcode-error').style.display = 'block';
                postcode.classList.add('error-field');
                isValid = false;
            }

            if (!cityVal) {
                document.getElementById('city-error').textContent = 'City is required';
                document.getElementById('city-error').style.display = 'block';
                city.classList.add('error-field');
                isValid = false;
            }

            if (!stateVal) {
                document.getElementById('state-error').textContent = 'State is required';
                document.getElementById('state-error').style.display = 'block';
                state.classList.add('error-field');
                isValid = false;
            }

            if (streetVal && !/^[a-zA-Z0-9\s\-,.]+$/.test(streetVal)) {
                document.getElementById('street-error').textContent = 'Street address should contain only letters, numbers, spaces, hyphens, commas and periods';
                document.getElementById('street-error').style.display = 'block';
                street.classList.add('error-field');
                isValid = false;
            }

            if (cityVal && !/^[a-zA-Z\s]+$/.test(cityVal)) {
                document.getElementById('city-error').textContent = 'City should contain only letters and spaces';
                document.getElementById('city-error').style.display = 'block';
                city.classList.add('error-field');
                isValid = false;
            }

            // Validate postcode format if postcode exists
            if (postcodeVal && !/^\d{5}$/.test(postcodeVal)) {
                document.getElementById('postcode-error').textContent = 'Postcode must be 5 digits';
                document.getElementById('postcode-error').style.display = 'block';
                postcode.classList.add('error-field');
                isValid = false;
            }

            return isValid;
        }

        function validateForm() {
            let isValid = true;
            const requiredFields = ['name', 'email', 'phone', 'street', 'postcode', 'city', 'state'];
            
            // Check all required fields (including address fields)
            requiredFields.forEach(field => {
                const fieldValue = document.getElementById(field).value.trim();
                const errorElement = document.getElementById(`${field}-error`);
                const inputField = document.getElementById(field);
                
                if (!fieldValue) {
                    const fieldName = field === 'postcode' ? 'Postcode' : 
                                    field.charAt(0).toUpperCase() + field.slice(1);
                    errorElement.textContent = `${fieldName} is required`;
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                    inputField.classList.remove('valid-field');
                    isValid = false;
                }
            });

            const name = document.getElementById('name').value.trim();
            if (name && !/^[a-zA-Z\s]+$/.test(name)) {
                document.getElementById('name-error').textContent = 'Name should contain only letters and spaces';
                document.getElementById('name-error').style.display = 'block';
                document.getElementById('name').classList.add('error-field');
                document.getElementById('name').classList.remove('valid-field');
                isValid = false;
            }

            // Validate email format
            const email = document.getElementById('email').value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.com$/.test(email)) {
                document.getElementById('email-error').textContent = 'Invalid email format (must end with .com)';
                document.getElementById('email-error').style.display = 'block';
                document.getElementById('email').classList.add('error-field');
                document.getElementById('email').classList.remove('valid-field');
                isValid = false;
            }

            // Validate phone format
            const phone = document.getElementById('phone').value.trim();
            if (phone && !/^\d{3}-\d{3,4} \d{4}$/.test(phone)) {
                document.getElementById('phone-error').textContent = 'Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format';
                document.getElementById('phone-error').style.display = 'block';
                document.getElementById('phone').classList.add('error-field');
                document.getElementById('phone').classList.remove('valid-field');
                isValid = false;
            }

            // Validate postcode format
            const postcode = document.getElementById('postcode').value.trim();
            if (postcode && !/^\d{5}$/.test(postcode)) {
                document.getElementById('postcode-error').textContent = 'Postcode must be 5 digits';
                document.getElementById('postcode-error').style.display = 'block';
                document.getElementById('postcode').classList.add('error-field');
                document.getElementById('postcode').classList.remove('valid-field');
                isValid = false;
            }

            return isValid;
        }

        // Add event listeners to all fields for real-time validation
        ['name', 'email', 'phone', 'street', 'postcode', 'city'].forEach(field => {
            document.getElementById(field).addEventListener('blur', function() {
                if (field === 'street' || field === 'postcode' || field === 'city' || field === 'state') {
                    validateAddressFields();
                } else {
                    const value = this.value.trim();
                    const errorElement = document.getElementById(`${field}-error`);
                    
                    if (!value) {
                        errorElement.textContent = `${field.charAt(0).toUpperCase() + field.slice(1)} is required`;
                        errorElement.style.display = 'block';
                        this.classList.add('error-field');
                        this.classList.remove('valid-field');
                    } else {
                        // Additional validation for specific fields
                        if (field === 'name' && !/^[a-zA-Z\s]+$/.test(value)) {
                            errorElement.textContent = 'Name should contain only letters and spaces';
                            errorElement.style.display = 'block';
                            this.classList.add('error-field');
                            this.classList.remove('valid-field');
                        } else if (field === 'email' && !/^[^\s@]+@[^\s@]+\.com$/.test(value)) {
                            errorElement.textContent = 'Invalid email format (must end with .com)';
                            errorElement.style.display = 'block';
                            this.classList.add('error-field');
                            this.classList.remove('valid-field');
                        } else if (field === 'phone' && !/^\d{3}-\d{3,4} \d{4}$/.test(value)) {
                            errorElement.textContent = 'Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format';
                            errorElement.style.display = 'block';
                            this.classList.add('error-field');
                            this.classList.remove('valid-field');
                        } else {
                            errorElement.textContent = '';
                            errorElement.style.display = 'none';
                            this.classList.remove('error-field');
                            this.classList.add('valid-field');
                        }
                    }
                }
            });
        });

        document.getElementById('state').addEventListener('change', function() {
            validateAddressFields();
        });

        // Form submission handler
        document.getElementById('editCustomerForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
                return;
            }

            const errorMessages = document.querySelectorAll('.error-message');
            let hasErrors = false;
            
            errorMessages.forEach(error => {
                if (error.textContent && error.style.display !== 'none') {
                    hasErrors = true;
                }
            });

            if (hasErrors) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
                return;
            }
        });

        function checkAvailability(type, value) {
            const custId = document.getElementById('cust_id')?.value;
            const errorElement = document.getElementById(`${type}-error`);
            const inputField = document.getElementById(type);
            
            // Clear previous states
            errorElement.textContent = '';
            errorElement.style.display = 'none';
            inputField.classList.remove('error-field', 'valid-field');

            // Check for empty required fields
            if (!value.trim() && (type === 'name' || type === 'email' || type === 'phone')) {
                errorElement.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} is required`;
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                return;
            }

            // Validate format
            let isValidFormat = true;
            let formatErrorMessage = '';
            
            if (type === 'name') {
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    isValidFormat = false;
                    formatErrorMessage = "Name should contain only letters and spaces";
                }
            } else if (type === 'email') {
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
                inputField.classList.remove('valid-field'); // Explicitly remove valid state
                return;
            }

            // Check availability via AJAX
            const formData = new FormData();
            formData.append('check_availability', 'true');
            formData.append('type', type);
            formData.append('value', value);
            formData.append('cust_id', custId);

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
                    inputField.classList.remove('valid-field');
                } else if (data.exists) {
                    errorElement.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} already exists`;
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                    inputField.classList.remove('valid-field');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    inputField.classList.remove('error-field');
                    inputField.classList.add('valid-field');
                }
            })
            .catch(error => {
                console.error('Error checking availability:', error);
                inputField.classList.remove('valid-field');
            });
        }

        function editCustomer(id, name, email, phone, street, postcode, city, state, profile_picture, status) {
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            document.querySelectorAll('.error-field, .valid-field').forEach(el => {
                el.classList.remove('error-field', 'valid-field');
            });
            
            document.getElementById('cust_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            document.getElementById('street').value = street;
            document.getElementById('postcode').value = postcode;
            document.getElementById('city').value = city;
            document.getElementById('state').value = state;
            
            if (!name.trim()) {
                const errorElement = document.getElementById('name-error');
                const inputField = document.getElementById('name');
                errorElement.textContent = 'Name is required';
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
            }
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            document.querySelectorAll('.error-field, .valid-field').forEach(el => {
                el.classList.remove('error-field', 'valid-field');
            });
            
            document.getElementById('editModal').style.display = 'none';
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
 
    </script>
</body>
</html>