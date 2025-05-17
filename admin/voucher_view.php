<?php
session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $voucher_query = "SELECT * FROM voucher WHERE VoucherCode LIKE ? 
                      ORDER BY 
                          VorcherStatus = 'Active' DESC,
                          VoucherCode ASC";
    $stmt = $conn->prepare($voucher_query);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $voucher_result = $stmt->get_result();
} else {
    $voucher_query = "SELECT * FROM voucher 
                      ORDER BY 
                          VorcherStatus = 'Active' DESC,
                          VoucherCode ASC";
    $voucher_result = $conn->query($voucher_query);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $voucher_id = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'code') {
        $error = validateVoucherCode($value);
        if ($error !== '') {
            $is_valid_format = false;
            $error_message = $error;
        } else {
            $sql = "SELECT VoucherID FROM voucher WHERE VoucherCode = ? AND VoucherID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $voucher_id);
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
function validateDescription($desc) {
    if (trim($desc) === '') {
        return 'Voucher description is required';
    }
    if (strlen($desc) > 255) {
        return 'Description must be 255 characters or less';
    }
    return '';
}

function validateVoucherCode($code) {
    if (trim($code) === '') {
        return 'Voucher code is required';
    }
    if (strlen($code) > 50) {
        return 'Voucher code must be 50 characters or less';
    }
    if (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
        return 'Only letters and numbers allowed (no spaces or special characters)';
    }
    return '';
}

function validateDiscountValue($value) {
    if (!is_numeric($value)) {
        return 'Discount value must be a number';
    }
    if ($value <= 0) {
        return 'Discount value must be greater than 0';
    }
    if ($value > 99999999.99) {
        return 'Discount value is too large';
    }
    return '';
}

function validateMinPurchase($value) {
    if (!is_numeric($value)) {
        return 'Minimum purchase must be a number';
    }
    if ($value < 0) {
        return 'Minimum purchase cannot be negative';
    }
    if ($value > 99999999.99) {
        return 'Minimum purchase is too large';
    }
    return '';
}

if (isset($_POST['add_voucher'])) {
    $voucher_code = trim($_POST['voucher_code']);
    $voucher_desc = trim($_POST['voucher_desc']);
    $discount_value = trim($_POST['discount_value']);
    $min_purchase = trim($_POST['min_purchase']);
    $expire_date = $_POST['expire_date'] ?: null;
    $admin_id = $_SESSION['AdminID'];

    // Validate inputs
    $code_error = validateVoucherCode($voucher_code);
    $value_error = validateDiscountValue($discount_value);
    $min_error = validateMinPurchase($min_purchase);
    
    if ($code_error !== '' || $value_error !== '' || $min_error !== '') {
        $error_message = implode("\n", array_filter([$code_error, $value_error, $min_error]));
        echo "<script>alert('$error_message'); window.location.href='voucher_view.php';</script>";
        exit();
    }

    // Check if voucher code exists
    $check_query = "SELECT VoucherID FROM voucher WHERE VoucherCode = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $voucher_code);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Voucher code already exists.'); window.location.href='voucher_view.php';</script>";
        exit();
    }
    $stmt->close();

    // Handle image upload
    $voucher_picture = $_FILES['voucher_picture']['name'] ?? null;
    $voucher_picture_tmp = $_FILES['voucher_picture']['tmp_name'] ?? null;
    $image_name = null;

    if (!empty($voucher_picture)) {
        $image_extension = strtolower(pathinfo($voucher_picture, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];

        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='voucher_view.php';</script>";
            exit();
        }

        $clean_name = strtolower(str_replace([' ', '/'], '_', $voucher_code));
        $clean_name = preg_replace("/[^a-z0-9_]/", "", $clean_name);
        $image_name = $clean_name . '.' . $image_extension;
        
        $target_dir = "../image/voucher/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($voucher_picture_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='voucher_view.php';</script>";
            exit();
        }
    }
    else if (empty($_FILES['voucher_picture']['name'])) {
        echo "<script>alert('Voucher image is required.'); window.location.href='voucher_view.php';</script>";
        exit();
    }

    // Insert new voucher
    $insert_query = "INSERT INTO voucher (VoucherCode, VoucherPicture, VoucherDesc, DiscountValue, MinPurchase, ExpireDate, AdminID) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssddsi", $voucher_code, $image_name, $voucher_desc, $discount_value, $min_purchase, $expire_date, $admin_id);

    if ($stmt->execute()) {
        echo "<script>alert('Voucher added successfully!'); window.location.href='voucher_view.php';</script>";
    } else {
        if (!empty($image_name) && file_exists($target_file)) {
            unlink($target_file);
        }
        echo "<script>alert('Error adding voucher.'); window.location.href='voucher_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['edit_voucher'])) {
    $voucher_id = intval($_POST['voucher_id']);
    $voucher_code = trim($_POST['voucher_code']);
    $voucher_desc = trim($_POST['voucher_desc']);
    $discount_value = trim($_POST['discount_value']);
    $min_purchase = trim($_POST['min_purchase']);
    $expire_date = $_POST['expire_date'] ?: null;

    // Validate inputs
    $code_error = validateVoucherCode($voucher_code);
    $value_error = validateDiscountValue($discount_value);
    $min_error = validateMinPurchase($min_purchase);
    $desc_error = validateDescription($voucher_desc);
    
    if ($code_error !== '' || $value_error !== '' || $min_error !== '' || $desc_error !== '') {
        $error_message = implode("\n", array_filter([$code_error, $value_error, $min_error, $desc_error]));
        echo "<script>alert('$error_message'); window.location.href='voucher_view.php';</script>";
        exit();
    }

    // Check if voucher code exists (excluding current voucher)
    $stmt = $conn->prepare("SELECT VoucherID FROM voucher WHERE VoucherCode = ? AND VoucherID != ?");
    $stmt->bind_param("si", $voucher_code, $voucher_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Voucher code already exists.'); window.location.href='voucher_view.php';</script>";
        exit();
    }
    $stmt->close();

    // Get current image
    $stmt = $conn->prepare("SELECT VoucherPicture FROM voucher WHERE VoucherID = ?");
    $stmt->bind_param("i", $voucher_id);
    $stmt->execute();
    $stmt->bind_result($old_image);
    $stmt->fetch();
    $stmt->close();

    // Handle image upload
    $target_dir = "../image/voucher/";
    $new_image_uploaded = !empty($_FILES['voucher_picture']['name']);
    
    $clean_name = strtolower(str_replace([' ', '/'], '_', $voucher_code));
    $clean_name = preg_replace("/[^a-z0-9_]/", "", $clean_name);

    if ($new_image_uploaded) {
        $ext = strtolower(pathinfo($_FILES['voucher_picture']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='voucher_view.php';</script>";
            exit();
        }
    } elseif (!empty($old_image)) {
        $ext = pathinfo($old_image, PATHINFO_EXTENSION);
    }

    $image_name_to_store = $clean_name . '.' . $ext;
    $new_path = $target_dir . $image_name_to_store;

    if ($new_image_uploaded) {
        if (!empty($old_image) && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
        }

        if (!move_uploaded_file($_FILES['voucher_picture']['tmp_name'], $new_path)) {
            echo "<script>alert('Failed to upload new image.'); window.location.href='voucher_view.php';</script>";
            exit();
        }
    } elseif (!empty($old_image) && $old_image !== $image_name_to_store) {
        if (file_exists($target_dir . $old_image)) {
            if (!rename($target_dir . $old_image, $new_path)) {
                echo "<script>alert('Failed to rename image file.'); window.location.href='voucher_view.php';</script>";
                exit();
            }
        }
    }

    // Update voucher
    $stmt = $conn->prepare("UPDATE voucher SET 
                            VoucherCode = ?, 
                            VoucherPicture = ?,
                            VoucherDesc = ?, 
                            DiscountValue = ?, 
                            MinPurchase = ?, 
                            ExpireDate = ? 
                            WHERE VoucherID = ?");
    $stmt->bind_param("sssddsi", $voucher_code, $image_name_to_store, $voucher_desc, $discount_value, $min_purchase, $expire_date, $voucher_id);
    if ($stmt->execute()) {
        echo "<script>alert('Voucher updated successfully!'); window.location.href='voucher_view.php';</script>";
    } else {
        if (!empty($image_name_to_store) && file_exists($new_path) && (!empty($old_image) && $old_image !== $image_name_to_store)) {
            rename($new_path, $target_dir . $old_image);
        }
        echo "<script>alert('Failed to update voucher.'); window.location.href='voucher_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $voucher_id = (int)$_POST['voucher_id'];
    $currentStatus = strtolower($_POST['current_status']);
    
    $newStatus = ($currentStatus == 'active') ? 'Inactive' : 'Active';

    $stmt = $conn->prepare("UPDATE voucher SET VorcherStatus = ? WHERE VoucherID = ?");
    $stmt->bind_param("si", $newStatus, $voucher_id);
    
    if ($stmt->execute()) {
        header("Location: voucher_view.php");
        exit();
    } else {
        echo "<script>alert('Failed to update status.'); window.location.href='voucher_view.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Management</title>
    <link rel="stylesheet" href="voucher_view.css">
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
            <h2>Voucher Management</h2>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search voucher code" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>

            <div class="add-voucher-form">
                <button type="button" onclick="openAddModal()" class="add_btn">Add Voucher</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">ID</th>
                        <th style="text-align: center;">Image</th>
                        <th>Voucher Code</th>
                        <th>Description</th>
                        <th style="text-align: center;">Min Purchase</th>
                        <th style="text-align: center;">Discount Value</th>
                        <th style="text-align: center;">Expire Date</th>
                        <th style="text-align: center;">Status</th>
                        <th class="action"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($voucher_result->num_rows > 0): ?>
                        <?php while ($voucher = $voucher_result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $voucher['VoucherID']; ?></td>
                                <td style="text-align: center;">
                                    <?php
                                        $imageSrc = $voucher['VoucherPicture'] ? '../image/voucher/' . $voucher['VoucherPicture'] : '../image/voucher/default.png';
                                    ?>
                                    <img src="<?= $imageSrc ?>" alt="<?= $voucher['VoucherPicture'] ?>" style="width: 120px; height: 80px;">
                                </td>
                                <td><?php echo $voucher['VoucherCode']; ?></td>
                                <td><?php echo $voucher['VoucherDesc']; ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                        if ($voucher['MinPurchase'] == 0) {
                                            echo 'No min. spend';
                                        } else {
                                            echo 'RM ' . number_format($voucher['MinPurchase'], 2);
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">RM <?php echo number_format($voucher['DiscountValue'], 2); ?></td>
                                <td style="text-align: center;"><?php echo $voucher['ExpireDate'] ? date('d/m/Y', strtotime($voucher['ExpireDate'])) : 'No expiry'; ?></td>
                                <td style="text-align: center;" class="<?php echo ($voucher['VorcherStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo ucfirst($voucher['VorcherStatus']); ?>
                                </td>
                                <td>
                                    <button name="edit_voucher" onclick='editVoucher(<?php echo json_encode($voucher); ?>)'>Edit</button>
                                    <form method="post" action="" style="display: inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="voucher_id" value="<?php echo $voucher['VoucherID']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $voucher['VorcherStatus']; ?>">
                                        <button type="submit" class="btn-status <?php echo ($voucher['VorcherStatus'] == 'Active') ? 'btn-inactive' : 'btn-active'; ?>">
                                            <?php echo ($voucher['VorcherStatus'] == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;color:red;"><b>No voucher found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add Voucher</h2>
            <form method="POST" action="" enctype="multipart/form-data" id="addVoucherForm">
                <input type="hidden" name="add_voucher" value="1">
                
                <label>Voucher Image:<span class="required">*</span> (.jpg, .jpeg or .png only)</label>
                <input class="img" type="file" name="voucher_picture" id="add-voucher-picture" accept=".jpg,.jpeg,.png" required>
                <div id="add-image-error" class="error"></div>
                
                <label>Voucher Code:<span class="required">*</span></label>
                <input type="text" name="voucher_code" id="add-code" required 
                       oninput="validateCodeInRealTime(this.value, 'add')"
                       onblur="checkAvailability('code', this.value, 0, true)">
                <div id="add-code-error" class="error"></div>
                
                <label>Description:<span class="required">*</span></label>
                <textarea name="voucher_desc" id="add-desc"></textarea>
                <div id="add-desc-error" class="error"></div>
                
                <label>Minimum Purchase (RM):<span class="required">*</span></label>
                <input type="number" name="min_purchase" id="add-min-purchase" min="0" step="0.01"
                       oninput="validateMinPurchaseInRealTime(this.value, 'add')">
                <div id="add-min-purchase-error" class="error"></div>
                
                <label>Discount Value (RM):<span class="required">*</span></label>
                <input type="number" name="discount_value" id="add-discount" min="0.01" step="0.01" required
                       oninput="validateDiscountInRealTime(this.value, 'add')">
                <div id="add-discount-error" class="error"></div>
                
                <label>Expire Date:</label>
                <input type="date" name="expire_date" id="add-expire-date">
                
                <div class="submit_btn">
                    <button type="submit" id="add-submit-btn" disabled>Add</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Voucher</h2>
            <form method="POST" action="" enctype="multipart/form-data" id="editVoucherForm">
                <input type="hidden" name="voucher_id" id="editVoucherID">
                <input type="hidden" name="edit_voucher" value="1">
                
                <label>Category Image:<span class="required">*</span><span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="voucher_picture" id="edit-voucher-picture" accept=".jpg,.jpeg,.png">
                
                <label>Voucher Code:<span class="required">*</span></label>
                <input type="text" name="voucher_code" id="edit-code" required 
                       oninput="validateCodeInRealTime(this.value, 'edit')"
                       onblur="checkAvailability('code', this.value, document.getElementById('editVoucherID').value, false)">
                <div id="edit-code-error" class="error"></div>
                
                <label>Description:</label>
                <textarea name="voucher_desc" id="edit-desc"></textarea>
                <div id="edit-desc-error" class="error"></div>
                
                <label>Minimum Purchase (RM):</label>
                <input type="number" name="min_purchase" id="edit-min-purchase" min="0" step="0.01"
                       oninput="validateMinPurchaseInRealTime(this.value, 'edit')">
                <div id="edit-min-purchase-error" class="error"></div>

                <label>Discount Value (RM):<span class="required">*</span></label>
                <input type="number" name="discount_value" id="edit-discount" min="0.01" step="0.01" required
                       oninput="validateDiscountInRealTime(this.value, 'edit')">
                <div id="edit-discount-error" class="error"></div>

                <label>Expire Date:</label>
                <input type="date" name="expire_date" id="edit-expire-date">
                
                <div class="submit_btn">
                    <button type="submit" id="edit-submit-btn" disabled>Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let isAddCodeValid = false;
    let isAddDiscountValid = false;
    let isAddDescValid = false;
    let isAddMinPurchaseValid = false;
    let isAddImageValid = false;
    let isEditDescValid = false;
    let isEditCodeValid = false;
    let isEditDiscountValid = false;
    let originalEditCode = '';

    // ================== SHARED FUNCTIONS ==================
    function validateCodeInRealTime(value, prefix) {
        const errorElement = document.getElementById(`${prefix}-code-error`);
        const inputField = document.getElementById(`${prefix}-code`);

        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputField.classList.remove('error-field', 'valid-field');

        const error = validateVoucherCode(value);
        if (error) {
            errorElement.textContent = error;
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            if (prefix === 'add') isAddCodeValid = false;
            else isEditCodeValid = false;
        } else {
            inputField.classList.add('valid-field');
            checkAvailability('code', value,
                prefix === 'edit' ? document.getElementById('editVoucherID').value : 0,
                prefix === 'add'
            ).then(isAvailable => {
                if (prefix === 'add') isAddCodeValid = isAvailable;
                else isEditCodeValid = isAvailable;
                updateSubmitButton(prefix);
            });
        }

        updateSubmitButton(prefix);
    }

    function validateDescInRealTime(value, prefix) {
        const errorElement = document.getElementById(`${prefix}-desc-error`);
        const inputField = document.getElementById(`${prefix}-desc`);

        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputField.classList.remove('error-field', 'valid-field');

        if (value.trim() === '') {
            errorElement.textContent = 'Description is required';
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            if (prefix === 'add') isAddDescValid = false;
            else isEditDescValid = false;
        } else if (value.length > 255) {
            errorElement.textContent = 'Description must be 255 characters or less';
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            if (prefix === 'add') isAddDescValid = false;
            else isEditDescValid = false;
        } else {
            inputField.classList.add('valid-field');
            if (prefix === 'add') isAddDescValid = true;
            else isEditDescValid = true;
        }

        updateSubmitButton(prefix);
    }

    function validateImageInRealTime(input, prefix) {
        const errorElement = document.getElementById(`${prefix}-image-error`);
        const inputField = document.getElementById(`${prefix}-voucher-picture`);

        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputField.classList.remove('error-field', 'valid-field');

        if (input.files.length === 0) {
            errorElement.textContent = 'Voucher image is required';
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            isAddImageValid = false;
        } else {
            const file = input.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            
            if (!validTypes.includes(file.type)) {
                errorElement.textContent = 'Only JPG, JPEG, and PNG files are allowed';
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                isAddImageValid = false;
            } else {
                inputField.classList.add('valid-field');
                isAddImageValid = true;
            }
        }

        updateSubmitButton(prefix);
    }

    function validateDiscountInRealTime(value, prefix) {
        const errorElement = document.getElementById(`${prefix}-discount-error`);
        const inputField = document.getElementById(`${prefix}-discount`);

        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputField.classList.remove('error-field', 'valid-field');

        const error = validateDiscountValue(value);
        if (error) {
            errorElement.textContent = error;
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            if (prefix === 'add') isAddDiscountValid = false;
            else isEditDiscountValid = false;
        } else {
            inputField.classList.add('valid-field');
            if (prefix === 'add') isAddDiscountValid = true;
            else isEditDiscountValid = true;
        }

        updateSubmitButton(prefix);
    }

    function validateMinPurchaseInRealTime(value, prefix) {
        const errorElement = document.getElementById(`${prefix}-min-purchase-error`);
        const inputField = document.getElementById(`${prefix}-min-purchase`);

        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputField.classList.remove('error-field', 'valid-field');

        if (value.trim() === '') {
            errorElement.textContent = 'Minimum purchase is required';
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            isAddMinPurchaseValid = false;
        } else {
            const error = validateMinPurchase(value);
            if (error) {
                errorElement.textContent = error;
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                isAddMinPurchaseValid = false;
            } else {
                inputField.classList.add('valid-field');
                isAddMinPurchaseValid = true;
            }
        }

        updateSubmitButton(prefix);
    }

    function updateSubmitButton(prefix) {
        const submitButton = document.getElementById(`${prefix}-submit-btn`);
        if (prefix === 'add') {
            submitButton.disabled = !(isAddCodeValid && isAddDiscountValid && isAddDescValid && isAddMinPurchaseValid && isAddImageValid);
        } else {
            submitButton.disabled = !(isEditCodeValid && isEditDiscountValid && isEditDescValid);
        }
    }

    function checkAvailability(type, value, voucherId = 0, isAddModal = false) {
        const prefix = isAddModal ? 'add-' : 'edit-';
        const errorElement = document.getElementById(`${prefix}${type}-error`);
        const inputField = document.getElementById(`${prefix}${type}`);

        return new Promise((resolve) => {
            const error = type === 'code' ? validateVoucherCode(value) : '';
            if (error) {
                errorElement.textContent = error;
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                resolve(false);
                return;
            }

            const formData = new FormData();
            formData.append('check_availability', 'true');
            formData.append('type', type);
            formData.append('value', value);
            formData.append('voucher_id', voucherId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorElement.textContent = 'Voucher code already exists';
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                    resolve(false);
                } else if (!data.valid_format) {
                    errorElement.textContent = data.message;
                    errorElement.style.display = 'block';
                    inputField.classList.add('error-field');
                    resolve(false);
                } else {
                    inputField.classList.add('valid-field');
                    resolve(true);
                }
            })
            .catch(error => {
                console.error('Error checking availability:', error);
                resolve(false);
            });
        });
    }

    function validateVoucherCode(code) {
        if (code.trim() === '') return 'Voucher code is required';
        if (code.length > 50) return 'Voucher code must be 50 characters or less';
        if (!/^[a-zA-Z0-9]+$/.test(code)) return 'Only letters and numbers allowed (no spaces or special characters)';
        return '';
    }

    function validateDiscountValue(value) {
        if (!value) return 'Discount value is required';
        if (isNaN(value)) return 'Discount value must be a number';
        if (value <= 0) return 'Discount value must be greater than 0';
        if (value > 99999999.99) return 'Discount value is too large';
        return '';
    }

    function validateMinPurchase(value) {
        if (isNaN(value)) return 'Minimum purchase must be a number';
        if (value < 0) return 'Minimum purchase cannot be negative';
        if (value > 99999999.99) return 'Minimum purchase is too large';
        return '';
    }

    function clearErrors(prefix) {
        const elements = ['code', 'discount', 'min-purchase', 'desc', 'image'];
        elements.forEach(element => {
            const errorElement = document.getElementById(`${prefix}-${element}-error`);
            const inputElement = document.getElementById(`${prefix}-${element === 'image' ? 'voucher-picture' : element}`);
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
            if (inputElement) {
                inputElement.classList.remove('error-field', 'valid-field');
            }
        });
    }

    // ================== EDIT VOUCHER SPECIFIC ==================
    function validateEditForm() {
        const codeInput = document.getElementById('edit-code');
        const code = codeInput.value.trim();
        const discountInput = document.getElementById('edit-discount');
        const discount = discountInput.value.trim();
        let isValid = true;

        // Validate code
        const codeError = validateVoucherCode(code);
        if (codeError) {
            document.getElementById('edit-code-error').textContent = codeError;
            codeInput.classList.add('error-field');
            isValid = false;
        }

        // Validate discount
        const discountError = validateDiscountValue(discount);
        if (discountError) {
            document.getElementById('edit-discount-error').textContent = discountError;
            discountInput.classList.add('error-field');
            isValid = false;
        }

        return isValid;
    }

    function editVoucher(voucher) {
        document.getElementById("editModal").style.display = "block";
        document.getElementById("editVoucherID").value = voucher.VoucherID;
        document.getElementById("edit-code").value = voucher.VoucherCode;
        document.getElementById("edit-desc").value = voucher.VoucherDesc;
        document.getElementById("edit-discount").value = voucher.DiscountValue;
        document.getElementById("edit-min-purchase").value = voucher.MinPurchase;
        document.getElementById("edit-expire-date").value = voucher.ExpireDate ? voucher.ExpireDate.split(' ')[0] : '';
        
        isEditDescValid = true;
        updateSubmitButton('edit');
        
        document.getElementById('edit-desc').addEventListener('input', function() {
            validateDescInRealTime(this.value, 'edit');
        });

        originalEditCode = voucher.VoucherCode;
        clearErrors('edit');
        isEditCodeValid = true;
        isEditDiscountValid = true;
        updateSubmitButton('edit');
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    // ================== ADD VOUCHER SPECIFIC ==================
    function openAddModal() {
        document.getElementById("addModal").style.display = "block";
        isAddCodeValid = false;
        isAddDiscountValid = false;
        isAddDescValid = false;
        isAddMinPurchaseValid = false;
        isAddImageValid = false;
        
        // Trigger initial validation
        validateCodeInRealTime('', 'add');
        validateDescInRealTime('', 'add');
        validateDiscountInRealTime('', 'add');
        validateMinPurchaseInRealTime('', 'add');
        validateImageInRealTime(document.getElementById('add-voucher-picture'), 'add');
        
        updateSubmitButton('add');
        document.getElementById('addVoucherForm').reset();
        clearErrors('add');
    }

    function closeAddModal() {
        document.getElementById("addModal").style.display = "none";
        document.getElementById("addVoucherForm").reset();
        clearErrors('add');
    }

    // ================== EVENT LISTENERS ==================
    document.addEventListener('DOMContentLoaded', function () {
        // Add event listeners for required fields
        document.getElementById('add-voucher-picture').addEventListener('change', function() {
            validateImageInRealTime(this, 'add');
        });

        document.getElementById('add-desc').addEventListener('input', function() {
            validateDescInRealTime(this.value, 'add');
        });

        document.getElementById('add-min-purchase').addEventListener('input', function() {
            validateMinPurchaseInRealTime(this.value, 'add');
        });

        document.getElementById('addVoucherForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            // Validate all required fields
            validateCodeInRealTime(document.getElementById('add-code').value, 'add');
            validateDescInRealTime(document.getElementById('add-desc').value, 'add');
            validateDiscountInRealTime(document.getElementById('add-discount').value, 'add');
            validateMinPurchaseInRealTime(document.getElementById('add-min-purchase').value, 'add');
            validateImageInRealTime(document.getElementById('add-voucher-picture'), 'add');
            
            if (!(isAddCodeValid && isAddDiscountValid && isAddDescValid && isAddMinPurchaseValid && isAddImageValid)) {
                alert('Please fix all errors before submitting.');
                return;
            }

            this.submit();
        });

        document.getElementById('editVoucherForm')?.addEventListener('submit', function (e) {
            validateDescInRealTime(document.getElementById('edit-desc').value, 'edit');
            
            if (!(isEditCodeValid && isEditDiscountValid && isEditDescValid)) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
                return;
            }
        });

        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        const minDate = tomorrow.toISOString().split('T')[0];

        document.getElementById('edit-expire-date').setAttribute('min', minDate);
        document.getElementById('add-expire-date').setAttribute('min', minDate);

        const searchInput = document.querySelector('input[name="search"]');
        const searchForm = document.querySelector('.search');

        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function () {
                if (this.value.trim() === '') {
                    searchForm.submit();
                }
            });
        }
    });
</script>
</body>
</html>