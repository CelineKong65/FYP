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
    $brand_query = "SELECT * FROM brand WHERE BrandName LIKE ? 
                      ORDER BY 
                          BrandStatus = 'Active' DESC,
                          BrandName ASC";
    $stmt = $conn->prepare($brand_query);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $brand_result = $stmt->get_result();
} else {
    $brand_query = "SELECT * FROM brand 
                      ORDER BY 
                          BrandStatus = 'Active' DESC,
                          BrandName ASC";
    $brand_result = $conn->query($brand_query);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'name') {
        $error = validateBrandName($value);
        if ($error !== '') {
            $is_valid_format = false;
            $error_message = $error;
        } else {
            $sql = "SELECT BrandID FROM brand WHERE BrandName = ? AND BrandID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $brand_id);
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

function validateBrandName($name) {
    if (trim($name) === '') {
        return 'Brand name is required';
    }
    if (strlen($name) > 100) {
        return 'Brand name must be 100 characters or less';
    }
    if (!preg_match('/^[a-zA-Z0-9\s\/\-]+$/', $name)) {
        return 'Only letters, numbers, spaces, slashes and hyphens allowed';
    }
    return '';
}

if (isset($_POST['add_brand'])) {
    $brand_name = trim($_POST['brand_name']);
    $admin_id = $_SESSION['AdminID'];

    $error = validateBrandName($brand_name);
    if ($error !== '') {
        echo "<script>alert('$error'); window.location.href='brand_view.php';</script>";
        exit();
    }

    $check_query = "SELECT BrandID FROM brand WHERE BrandName = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $brand_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Brand name already exists.'); window.location.href='brand_view.php';</script>";
        exit();
    }
    $stmt->close();

    $profile_picture = $_FILES['profile_picture']['name'] ?? null;
    $profile_picture_tmp = $_FILES['profile_picture']['tmp_name'] ?? null;
    $image_name = null;

    if (!empty($profile_picture)) {
        $image_extension = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];

        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='brand_view.php';</script>";
            exit();
        }
        $clean_name = strtolower(str_replace([' ', '/'], '_', $brand_name));
        $clean_name = preg_replace("/[^a-z0-9_]/", "", $clean_name);
        $image_name = $clean_name . '.' . $image_extension;

        $target_dir = "../image/brand/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($profile_picture_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='brand_view.php';</script>";
            exit();
        }
    }

    $insert_query = "INSERT INTO brand (BrandName, BrandPicture, AdminID) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssi", $brand_name, $image_name, $admin_id);

    if ($stmt->execute()) {
        echo "<script>alert('Brand added successfully!'); window.location.href='brand_view.php';</script>";
    } else {
        if (!empty($image_name) && file_exists($target_file)) {
            unlink($target_file);
        }
        echo "<script>alert('Error adding brand.'); window.location.href='brand_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['edit_brand'])) {
    $brand_id = intval($_POST['brand_id']);
    $new_name = trim($_POST['new_brand_name']);

    $error = validateBrandName($new_name);
    if ($error !== '') {
        echo "<script>alert('$error'); window.location.href='brand_view.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT BrandID FROM brand WHERE BrandName = ? AND BrandID != ?");
    $stmt->bind_param("si", $new_name, $brand_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Brand name already exists.'); window.location.href='brand_view.php';</script>";
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT BrandPicture FROM brand WHERE BrandID = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $stmt->bind_result($old_image);
    $stmt->fetch();
    $stmt->close();

    $target_dir = "../image/brand/";
    $new_image_uploaded = !empty($_FILES['profile_picture']['name']);
    
    $clean_name = strtolower(str_replace([' ', '/'], '_', $new_name));
    $clean_name = preg_replace("/[^a-z0-9_]/", "", $clean_name);
    
    if ($new_image_uploaded) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='brand_view.php';</script>";
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

        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $new_path)) {
            echo "<script>alert('Failed to upload new image.'); window.location.href='brand_view.php';</script>";
            exit();
        }
    } elseif (!empty($old_image) && $old_image !== $image_name_to_store) {
        if (file_exists($target_dir . $old_image)) {
            if (!rename($target_dir . $old_image, $new_path)) {
                echo "<script>alert('Failed to rename image file.'); window.location.href='brand_view.php';</script>";
                exit();
            }
        }
    }

    $stmt = $conn->prepare("UPDATE brand SET BrandName = ?, BrandPicture = ? WHERE BrandID = ?");
    $stmt->bind_param("ssi", $new_name, $image_name_to_store, $brand_id);
    if ($stmt->execute()) {
        echo "<script>alert('Brand updated successfully!'); window.location.href='brand_view.php';</script>";
    } else {
        if (!empty($image_name_to_store) && file_exists($new_path) && (!empty($old_image) && $old_image !== $image_name_to_store)) {
            rename($new_path, $target_dir . $old_image);
        }
        echo "<script>alert('Failed to update brand.'); window.location.href='brand_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $brand_id = (int)$_POST['brand_id'];
    $currentStatus = strtolower($_POST['current_status']);
    
    $newStatus = ($currentStatus == 'active') ? 'Inactive' : 'Active';

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE brand SET BrandStatus = ? WHERE BrandID = ?");
        $stmt->bind_param("si", $newStatus, $brand_id);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE product SET ProductStatus = ? WHERE BrandID = ?");
        $stmt2->bind_param("si", $newStatus, $brand_id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();

        header("Location: brand_view.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Failed to update status.'); window.location.href='brand_view.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Brands</title>
    <link rel="stylesheet" href="brand_view.css">
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
        <h2>Product Brands</h2>

        <form method="GET" action="" class="search">
            <input type="text" name="search" placeholder="Search brand" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="search">Search</button>
        </form>

        <div class="add-brand-form">
            <button type="button" onclick="openAddModal()" class="add_btn">Add Brand</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">ID</th>
                    <th>Image</th>
                    <th>Brand Name</th>
                    <th style="text-align: center;">Status</th>
                    <th class="action"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($brand_result->num_rows > 0): ?>
                    <?php while ($brand = $brand_result->fetch_assoc()): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $brand['BrandID']; ?></td>
                            <?php
                                $brand_image_name = $brand['BrandPicture'];
                                $image_path = !empty($brand_image_name) ? "../image/brand/" . $brand_image_name : "";
                            ?>
                            <td style="text-align: center;">
                                <?php if (!empty($image_path) && file_exists($image_path)): ?>
                                    <img src="<?php echo $image_path; ?>" alt="Brand Image" style="width: 120px; height: 80px;">
                                <?php else: ?>
                                    <span>No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $brand['BrandName']; ?></td>
                            <td class="<?php echo ($brand['BrandStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>" style="text-align: center;">
                                <?php echo ucfirst($brand['BrandStatus']); ?>
                            </td>
                            <td>
                                <button name="edit_brand" onclick='editBrand(<?php echo json_encode($brand["BrandID"]); ?>, <?php echo json_encode($brand["BrandName"]); ?>)'>Edit</button>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="brand_id" value="<?php echo $brand['BrandID']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $brand['BrandStatus']; ?>">
                                    <button type="submit" class="btn-status <?php echo ($brand['BrandStatus'] == 'Active') ? 'btn-inactive' : 'btn-active'; ?>">
                                        <?php echo ($brand['BrandStatus'] == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                 </form>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No brand found.</b></td>
                        </tr>
                    <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add Brands</h2>
            <form method="POST" action="" enctype="multipart/form-data" id="addBrandForm">
                <input type="hidden" name="add_brand" value="1">
                <label>Brand Image:<span class="required">*</span><span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="profile_picture" id="add-profile-picture" accept=".jpg,.jpeg,.png" required>
                
                <label>Brand Name:<span class="required">*</span></label>
                <input type="text" name="brand_name" id="add-name" required 
                       oninput="validateNameInRealTime(this.value, 'add')"
                       onblur="checkAvailability('name', this.value, 0, true)">
                <div id="add-name-error" class="error"></div>
                
                <div class="submit_btn">
                    <button type="submit" id="add-submit-btn" disabled>Add</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Brand</h2>
            <form method="POST" action="" enctype="multipart/form-data" id="editBrandForm">
                <label>Brand Image:<span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="profile_picture" id="edit-profile-picture" accept=".jpg,.jpeg,.png">
                
                <label>New name:<span class="required">*</span></label>
                <input type="hidden" name="brand_id" id="editBrandID">
                <input type="text" name="new_brand_name" id="edit-name" required 
                       oninput="validateNameInRealTime(this.value, 'edit')"
                       onblur="checkAvailability('name', this.value, document.getElementById('editBrandID').value, false)">
                <div id="edit-name-error" class="error"></div>
                
                <div class="submit_btn">
                    <button type="submit" name="edit_brand" id="edit-submit-btn" disabled>Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let isAddNameValid = false;
    let isEditNameValid = false;
    let originalEditName = '';

    // ================== SHARED FUNCTIONS ==================
    async function validateNameInRealTime(value, prefix) {
        const errorElement = document.getElementById(`${prefix}-name-error`);
        const inputField = document.getElementById(`${prefix}-name`);

        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputField.classList.remove('error-field', 'valid-field');

        const error = validateBrandName(value);
        if (error) {
            errorElement.textContent = error;
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            if (prefix === 'add') isAddNameValid = false;
            else isEditNameValid = false;
        } else {
            inputField.classList.add('valid-field');
            const isAvailable = await checkAvailability('name', value,
                prefix === 'edit' ? document.getElementById('editBrandID').value : 0,
                prefix === 'add'
            );

            if (prefix === 'add') isAddNameValid = isAvailable;
            else isEditNameValid = isAvailable;
        }

        updateSubmitButton(prefix);
    }

    function updateSubmitButton(prefix) {
        const submitButton = document.getElementById(`${prefix}-submit-btn`);
        if (prefix === 'add') {
            submitButton.disabled = !isAddNameValid;
        } else {
            submitButton.disabled = !isEditNameValid;
        }
    }

    function checkAvailability(type, value, brandId = 0, isAddModal = false) {
        const prefix = isAddModal ? 'add-' : 'edit-';
        const errorElement = document.getElementById(`${prefix}${type}-error`);
        const inputField = document.getElementById(`${prefix}${type}`);

        return new Promise((resolve) => {
            const error = validateBrandName(value);
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
            formData.append('brand_id', brandId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorElement.textContent = 'Brand name already exists';
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

    function validateBrandName(name) {
        if (name.trim() === '') return 'Brand name is required';
        if (name.length > 100) return 'Brand name must be 100 characters or less';
        if (!/^[a-zA-Z0-9\s\/\-]+$/.test(name)) return 'Only letters, numbers, spaces, slashes and hyphens allowed';
        return '';
    }

    function clearErrors(prefix) {
        const elements = ['name', 'image'];
        elements.forEach(element => {
            const errorElement = document.getElementById(`${prefix}-${element}-error`);
            const inputElement = document.getElementById(`${prefix}-${element === 'name' ? 'name' : 'profile-picture'}`);
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
            if (inputElement) {
                inputElement.classList.remove('error-field', 'valid-field');
            }
        });
    }

    // ================== EDIT BRAND SPECIFIC ==================
    function validateEditForm() {
        const nameInput = document.getElementById('edit-name');
        const name = nameInput.value.trim();
        const imageInput = document.getElementById('edit-profile-picture');
        let isValid = true;

        // Clear previous errors
        document.getElementById('edit-name-error').textContent = '';
        nameInput.classList.remove('error-field');

        // Validate name
        const nameError = validateBrandName(name);
        if (nameError) {
            document.getElementById('edit-name-error').textContent = nameError;
            nameInput.classList.add('error-field');
            isValid = false;
        }

        // Validate image if changed
        if (imageInput.files.length > 0) {
            const file = imageInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            
            if (!validTypes.includes(file.type)) {
                alert('Please upload only JPG, JPEG, or PNG files');
                isValid = false;
            }
        }

        return isValid;
    }

    function editBrand(id, name) {
        document.getElementById("editModal").style.display = "block";
        document.getElementById("editBrandID").value = id;
        document.getElementById("edit-name").value = name;
        originalEditName = name;
        clearErrors('edit');
        isEditNameValid = true;
        updateSubmitButton('edit');
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    // ================== ADD BRAND SPECIFIC ==================
    function openAddModal() {
        document.getElementById("addModal").style.display = "block";
        isAddNameValid = false;
        updateSubmitButton('add');
        document.getElementById('add-profile-picture').value = '';
    }

    function closeAddModal() {
        document.getElementById("addModal").style.display = "none";
        document.getElementById("addBrandForm").reset();
        clearErrors('add');
    }

    // ================== EVENT LISTENERS ==================
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('add-profile-picture').addEventListener('change', function () {
            updateSubmitButton('add');
        });

        document.getElementById('addBrandForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const nameInput = document.getElementById('add-name');
            await validateNameInRealTime(nameInput.value, 'add');
            console.log('isAddNameValid:', isAddNameValid);

            if (!isAddNameValid) {
                alert('Please fix all errors before submitting.');
                return;
            }

            this.submit();
        });

        document.getElementById('editBrandForm')?.addEventListener('submit', function (e) {
            if (!validateEditForm()) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
                return;
            }
            // If valid, form will submit normally
        });

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