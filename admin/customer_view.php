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

$customer_query = "SELECT * FROM customer";
$customer_result = $conn->query($customer_query);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $stmt = $conn->prepare("
        SELECT * FROM customer 
        WHERE (CustName LIKE ? OR CustEmail LIKE ?)
        AND CustomerStatus = 'active'
    ");
    $searchTerm = '%' . $search_query . '%';
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $customer_result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("
        SELECT * FROM customer 
        WHERE CustomerStatus = 'active'
    ");
    $stmt->execute();
    $customer_result = $stmt->get_result();
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
    $status = trim($_POST['status']);
    $password = trim($_POST['password']);
    $profile_picture = isset($_FILES['profile_picture']['name']) ? $_FILES['profile_picture']['name'] : null;
    $profile_picture_tmp = isset($_FILES['profile_picture']['tmp_name']) ? $_FILES['profile_picture']['tmp_name'] : null;

    if (!empty($profile_picture)) {
        $image_extension = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='customer_view.php';</script>";
            exit();
        }

        $image_name = $cust_id . "." . $image_extension;
        $target_dir = "../image/user/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($profile_picture_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='customer_view.php';</script>";
            exit();
        }

        $existing_picture_query = "SELECT CustProfilePicture FROM customer WHERE CustID = ?";
        $stmt = $conn->prepare($existing_picture_query);
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->bind_result($existing_picture);
        $stmt->fetch();
        $stmt->close();

        if (!empty($existing_picture) && file_exists($target_dir . $existing_picture)) {
            unlink($target_dir . $existing_picture);
        }
    } else {
        $existing_picture_query = "SELECT CustProfilePicture FROM customer WHERE CustID = ?";
        $stmt = $conn->prepare($existing_picture_query);
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->bind_result($existing_picture);
        $stmt->fetch();
        $stmt->close();
        $image_name = $existing_picture;
    }

    $check_email_query = "SELECT CustID FROM customer WHERE CustEmail = ? AND CustID != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $cust_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_name_query = "SELECT CustID FROM customer WHERE CustName = ? AND CustID != ?";
    $stmt = $conn->prepare($check_name_query);
    $stmt->bind_param("si", $name, $cust_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Name already exists. Please use a different name.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    $check_phone_query = "SELECT CustID FROM customer WHERE CustPhoneNum = ? AND CustID != ?";
    $stmt = $conn->prepare($check_phone_query);
    $stmt->bind_param("si", $phone, $cust_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo "<script>alert('Invalid email format. Email must be valid and end with .com'); window.location.href='customer_view.php';</script>";
        exit();
    }
    
    // Validate phone
    if (!preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        echo "<script>alert('Invalid phone number format. Use XXX-XXX XXXX or XXX-XXXX XXXX.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    
    $original_query = "SELECT * FROM customer WHERE CustID = ?";
    $stmt = $conn->prepare($original_query);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $original_result = $stmt->get_result();
    $original_data = $original_result->fetch_assoc();
    $stmt->close();

    $has_changes = (
        $name !== $original_data['CustName'] ||
        $email !== $original_data['CustEmail'] ||
        $phone !== $original_data['CustPhoneNum'] ||
        $street !== $original_data['StreetAddress'] ||
        $postcode !== $original_data['Postcode'] ||
        $city !== $original_data['City'] ||
        $state !== $original_data['State'] ||
        $status !== $original_data['CustomerStatus'] ||
        (!empty($password)) ||
        ($image_name !== $original_data['CustProfilePicture'])
    );

    if (!$has_changes) {
        echo "<script>alert('No changes detected.'); window.location.href='customer_view.php';</script>";
        exit();
    } else {
        if (!empty($password)) {
            $update_query = "UPDATE customer SET CustName = ?, CustEmail = ?, CustPassword = ?, CustPhoneNum = ?, StreetAddress = ?, Postcode = ?, City = ?, State = ?, CustomerStatus  = ?, CustProfilePicture = ? WHERE CustID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssssssssi", $name, $email, $password, $phone, $street, $postcode, $city, $state, $status, $image_name, $cust_id);        
        } else {
            $update_query = "UPDATE customer SET CustName = ?, CustEmail = ?, CustPhoneNum = ?, StreetAddress = ?, Postcode = ?, City = ?, State = ?, CustomerStatus  = ?, CustProfilePicture = ? WHERE CustID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssssssi", $name, $email, $phone, $street, $postcode, $city, $state, $status, $image_name, $cust_id);        
        }        
    
        if ($stmt->execute()) {
            echo "<script>alert('Customer updated successfully!'); window.location.href='customer_view.php';</script>";
        } else {
            echo "<script>alert('Failed to update customer.'); window.location.href='customer_view.php';</script>";
        }
        $stmt->close();
        exit();
    }
    
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $custID = (int)$_POST['cust_id'];
    $currentStatus = strtolower($_POST['current_status']);
    
    $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE customer SET CustomerStatus = ? WHERE CustID = ?");
    $stmt->bind_param("si", $newStatus, $custID);
    
    if ($stmt->execute()) {
        header("Location: customer_view.php");
        exit();
    } else {
        $error = "Status update failed: " . $conn->error;
    }
    $stmt->close();
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
                <input type="text" name="search" placeholder="Search by name or email" 
                    value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profile Picture</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th style="width: 120px;">Phone</th>
                        <th style="width: 400px;">Address</th>
                        <?php if ($loggedInPosition === 'superadmin'): ?>
                            <th>Password</th>
                        <?php endif; ?>
                        <th>Status</th> 
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customer_result->num_rows > 0): ?>
                        <?php while ($customer = $customer_result->fetch_assoc()): ?>
                            <?php
                            $cust_id = $customer['CustID'];
                            $jpgPath = "../image/user/{$cust_id}.jpg";
                            $jpegPath = "../image/user/{$cust_id}.jpeg";
                            $pngPath = "../image/user/{$cust_id}.png";

                            if (file_exists($jpgPath)) {
                                $profile_pic_path = $jpgPath;
                            } elseif (file_exists($jpegPath)) {
                                $profile_pic_path = $jpegPath;
                            } elseif (file_exists($pngPath)) {
                                $profile_pic_path = $pngPath;
                            } else {
                                $profile_pic_path = "../image/user/user.png";
                            }
                            ?>
                            <tr>
                                <td><?php echo $customer['CustID']; ?></td>
                                <td><img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" width="50"></td>
                                <td><?php echo $customer['CustName']; ?></td>
                                <td><?php echo $customer['CustEmail']; ?></td>
                                <td><?php echo $customer['CustPhoneNum']; ?></td>
                                <td>
                                    <?php
                                    $full_address = trim($customer['StreetAddress'] . ', ' . $customer['Postcode'] . ' ' . $customer['City'] . ', ' . $customer['State']);
                                    echo htmlspecialchars($full_address);
                                    ?>
                                </td>
                                <?php if ($loggedInPosition === 'superadmin'): ?>
                                    <td><?php echo $customer['CustPassword']; ?></td>
                                <?php endif; ?>
                                <td class="<?php echo ($customer['CustomerStatus'] === 'active') ? 'status-active' : 'status-inactive'; ?>">
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
                                        <?php echo json_encode($customer["CustProfilePicture"]); ?>,
                                        <?php echo json_encode($customer["CustomerStatus"]); ?>)'>Edit
                                    </button>
                                    <form method="post" action="">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="cust_id" value="<?php echo $customer['CustID']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $customer['CustomerStatus']; ?>">
                                        <button type="submit" class="btn-status <?php echo ($customer['CustomerStatus'] == 'active') ? 'btn-inactive' : 'btn-active'; ?>">
                                            <?php echo ($customer['CustomerStatus'] == 'active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;color:red;"><b>No customers found.</b></td>
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
            <form method="POST" action="" enctype="multipart/form-data" class="edit">
                <input type="hidden" name="cust_id" id="cust_id">

                <div class="edit-form">
                    <div class="left">
                        <label>Profile Picture:<span> (.jpp,.jpeg or .png only)</span></label>
                        <input class="img" type="file" name="profile_picture" id="profile_picture">
                        <label>Name:</label>
                        <input type="text" name="name" id="name" required>
                        <div id="name-error" class="error"></div>
                        <label>Email:</label>
                        <input type="email" name="email" id="email" placeholder="example@gmail.com" required>
                        <div id="email-error" class="error"></div>
                        <label>Phone:</label>
                        <input type="text" name="phone" id="phone" placeholder="XXX-XXX XXXX or XXX-XXXX XXXX format" required>
                        <div id="phone-error" class="error"></div>
                        <label>Password:</label>
                        <input type="password" name="password">
                        <p>(Leave empty to keep the current password)</p>
                    </div>
                    <div class="right">
                        <label>Street Address:</label>
                        <input type="text" name="street" id="street" required>
                        <div id="street-error" class="error"></div>
                        <label>Postcode:</label>
                        <input type="text" name="postcode" id="postcode" required>
                        <div id="postcode-error" class="error"></div>
                        <label>City:</label>
                        <input type="text" name="city" id="city" required>
                        <div id="city-error" class="error"></div>
                        <label>State:</label>
                        <select name="state" id="state" required>
                            <option value="">-- Select City/State --</option>
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
                        <label>Status:</label>
                        <select name="status" id="status" required>
                            <option value="active" <?php echo isset($customer['CustomerStatus']) && $customer['CustomerStatus'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo isset($customer['CustomerStatus']) && $customer['CustomerStatus'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>            

                <div class="upd_div">
                    <button type="submit" name="update_customer">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Validation functions
    function validateName(name) {
        if (name.trim() === '') {
            return 'Name is required';
        }
        return '';
    }

    function validateEmail(email) {
        if (email.trim() === '') {
            return 'Email is required';
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || !email.endsWith('.com')) {
            return 'Invalid email format (must contain @ and end with .com)';
        }
        return '';
    }

    function validatePhone(phone) {
        if (phone.trim() === '') {
            return 'Phone is required';
        }
        if (!/^\d{3}-\d{3,4} \d{4}$/.test(phone)) {
            return 'Phone must be in XXX-XXX XXXX or XXX-XXXX XXXX format';
        }
        return '';
    }

    function validateStreet(street) {
        if (street.trim() === '') {
            return 'Street address is required';
        }
        return '';
    }

    function validatePostcode(postcode) {
        if (postcode.trim() === '') {
            return 'Postcode is required';
        }
        if (!/^\d{5}$/.test(postcode)) {
            return 'Postcode must be 5 digits';
        }
        return '';
    }

    function validateCity(city) {
        if (city.trim() === '') {
            return 'City is required';
        }
        return '';
    }

    function validateState(state) {
        if (state.trim() === '') {
            return 'State is required';
        }
        return '';
    }

    // Show/hide error functions
    function showError(fieldId, errorId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('error-field');
        }
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function clearError(fieldId, errorId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove('error-field');
        }
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Add event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Edit modal validation
        const editName = document.getElementById('name');
        const editEmail = document.getElementById('email');
        const editPhone = document.getElementById('phone');
        const editStreet = document.getElementById('street');
        const editPostcode = document.getElementById('postcode');
        const editCity = document.getElementById('city');
        const editState = document.getElementById('state');

        if (editName) editName.addEventListener('input', function() {
            const error = validateName(this.value);
            if (error) {
                showError('name', 'name-error', error);
            } else {
                clearError('name', 'name-error');
            }
        });

        if (editEmail) editEmail.addEventListener('input', function() {
            const error = validateEmail(this.value);
            if (error) {
                showError('email', 'email-error', error);
            } else {
                clearError('email', 'email-error');
            }
        });

        if (editPhone) editPhone.addEventListener('input', function() {
            const error = validatePhone(this.value);
            if (error) {
                showError('phone', 'phone-error', error);
            } else {
                clearError('phone', 'phone-error');
            }
        });

        if (editStreet) editStreet.addEventListener('input', function() {
            const error = validateStreet(this.value);
            if (error) {
                showError('street', 'street-error', error);
            } else {
                clearError('street', 'street-error');
            }
        });

        if (editPostcode) editPostcode.addEventListener('input', function() {
            const error = validatePostcode(this.value);
            if (error) {
                showError('postcode', 'postcode-error', error);
            } else {
                clearError('postcode', 'postcode-error');
            }
        });

        if (editCity) editCity.addEventListener('input', function() {
            const error = validateCity(this.value);
            if (error) {
                showError('city', 'city-error', error);
            } else {
                clearError('city', 'city-error');
            }
        });

        if (editState) editState.addEventListener('change', function() {
            const error = validateState(this.value);
            if (error) {
                showError('state', 'state-error', error);
            } else {
                clearError('state', 'state-error');
            }
        });

        // Form submission validation
        document.querySelector('#editModal form').addEventListener('submit', function(e) {
            let isValid = true;
            
            const name = document.getElementById('name').value;
            const nameError = validateName(name);
            if (nameError) {
                showError('name', 'name-error', nameError);
                isValid = false;
            }
            
            const email = document.getElementById('email').value;
            const emailError = validateEmail(email);
            if (emailError) {
                showError('email', 'email-error', emailError);
                isValid = false;
            }
            
            const phone = document.getElementById('phone').value;
            const phoneError = validatePhone(phone);
            if (phoneError) {
                showError('phone', 'phone-error', phoneError);
                isValid = false;
            }
            
            const street = document.getElementById('street').value;
            const streetError = validateStreet(street);
            if (streetError) {
                showError('street', 'street-error', streetError);
                isValid = false;
            }
            
            const postcode = document.getElementById('postcode').value;
            const postcodeError = validatePostcode(postcode);
            if (postcodeError) {
                showError('postcode', 'postcode-error', postcodeError);
                isValid = false;
            }
            
            const city = document.getElementById('city').value;
            const cityError = validateCity(city);
            if (cityError) {
                showError('city', 'city-error', cityError);
                isValid = false;
            }
            
            const state = document.getElementById('state').value;
            const stateError = validateState(state);
            if (stateError) {
                showError('state', 'state-error', stateError);
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });

    // Modal functions
    function editCustomer(id, name, email, phone, street, postcode, city, state, profile_picture, status) {
        // Clear any previous errors
        closeModal();
        
        // Set values
        document.getElementById('cust_id').value = id;
        document.getElementById('name').value = name;
        document.getElementById('email').value = email;
        document.getElementById('phone').value = phone;
        document.getElementById('street').value = street;
        document.getElementById('postcode').value = postcode;
        document.getElementById('city').value = city;
        document.getElementById('state').value = state;
        document.getElementById('status').value = status.toLowerCase();
        
        // Show modal
        document.getElementById('editModal').style.display = 'block';
    }

    function closeModal() {
        // Clear all errors
        clearError('name', 'name-error');
        clearError('email', 'email-error');
        clearError('phone', 'phone-error');
        clearError('street', 'street-error');
        clearError('postcode', 'postcode-error');
        clearError('city', 'city-error');
        clearError('state', 'state-error');
        
        // Hide modal
        document.getElementById('editModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        const searchForm = document.querySelector('.search');
        
        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function() {
                // If search input is empty, submit the form to show all results
                if (this.value.trim() === '') {
                    searchForm.submit();
                }
            });
        }
    });
    window.onclick = function(event) {
        if (event.target == document.getElementById("editModal")) {
            closeModal();
        }
    }
    </script>
</body>
</html>