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
    $category_query = "SELECT * FROM category WHERE CategoryName LIKE ? 
                      ORDER BY 
                          CategoryStatus = 'Active' DESC,
                          CategoryName ASC";
    $stmt = $conn->prepare($category_query);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $category_result = $stmt->get_result();
} else {
    $category_query = "SELECT * FROM category 
                      ORDER BY 
                          CategoryStatus = 'Active' DESC,
                          CategoryName ASC";
    $category_result = $conn->query($category_query);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'name') {
        $error = validateCategoryName($value);
        if ($error !== '') {
            $is_valid_format = false;
            $error_message = $error;
        } else {
            $sql = "SELECT CategoryID FROM category WHERE CategoryName = ? AND CategoryID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $category_id);
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

function validateCategoryName($name) {
    if (trim($name) === '') {
        return 'Category name is required';
    }
    if (strlen($name) > 50) {
        return 'Category name must be 50 characters or less';
    }
    if (!preg_match('/^[a-zA-Z0-9\s\/\-]+$/', $name)) {
        return 'Only letters, numbers, spaces, slashes and hyphens allowed';
    }
    return '';
}

if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $admin_id = $_SESSION['AdminID'];

    $error = validateCategoryName($category_name);
    if ($error !== '') {
        echo "<script>alert('$error'); window.location.href='category_view.php';</script>";
        exit();
    }

    $check_query = "SELECT CategoryID FROM category WHERE CategoryName = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Category name already exists.'); window.location.href='category_view.php';</script>";
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
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='category_view.php';</script>";
            exit();
        }

        $image_name = strtolower(str_replace(' ', '_', $category_name)) . '.' . $image_extension;
        $image_name = preg_replace("/[^a-z0-9_.]/", "", $image_name);
        
        $target_dir = "../image/categories/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($profile_picture_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='category_view.php';</script>";
            exit();
        }
    }

    $insert_query = "INSERT INTO category (CategoryName, CategoryPicture, AdminID) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssi", $category_name, $image_name, $admin_id);

    if ($stmt->execute()) {
        echo "<script>alert('Category added successfully!'); window.location.href='category_view.php';</script>";
    } else {
        if (!empty($image_name) && file_exists($target_file)) {
            unlink($target_file);
        }
        echo "<script>alert('Error adding category.'); window.location.href='category_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $new_name = trim($_POST['new_category_name']);

    $error = validateCategoryName($new_name);
    if ($error !== '') {
        echo "<script>alert('$error'); window.location.href='category_view.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT CategoryID FROM category WHERE CategoryName = ? AND CategoryID != ?");
    $stmt->bind_param("si", $new_name, $category_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Category name already exists.'); window.location.href='category_view.php';</script>";
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT CategoryPicture FROM category WHERE CategoryID = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->bind_result($old_image);
    $stmt->fetch();
    $stmt->close();

    $target_dir = "../image/categories/";
    $new_image_uploaded = !empty($_FILES['profile_picture']['name']);
    
    $clean_name = strtolower(str_replace(' ', '_', $new_name));
    $clean_name = preg_replace("/[^a-z0-9_]/", "", $clean_name);
    
    if ($new_image_uploaded) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='category_view.php';</script>";
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
            echo "<script>alert('Failed to upload new image.'); window.location.href='category_view.php';</script>";
            exit();
        }
    } elseif (!empty($old_image) && $old_image !== $image_name_to_store) {
        if (file_exists($target_dir . $old_image)) {
            if (!rename($target_dir . $old_image, $new_path)) {
                echo "<script>alert('Failed to rename image file.'); window.location.href='category_view.php';</script>";
                exit();
            }
        }
    }

    $stmt = $conn->prepare("UPDATE category SET CategoryName = ?, CategoryPicture = ? WHERE CategoryID = ?");
    $stmt->bind_param("ssi", $new_name, $image_name_to_store, $category_id);
    if ($stmt->execute()) {
        echo "<script>alert('Category updated successfully!'); window.location.href='category_view.php';</script>";
    } else {
        if (!empty($image_name_to_store) && file_exists($new_path) && (!empty($old_image) && $old_image !== $image_name_to_store)) {
            rename($new_path, $target_dir . $old_image);
        }
        echo "<script>alert('Failed to update category.'); window.location.href='category_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $category_id = (int)$_POST['category_id'];
    $currentStatus = strtolower($_POST['current_status']);
    
    $newStatus = ($currentStatus == 'active') ? 'Inactive' : 'Active';

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE category SET CategoryStatus = ? WHERE CategoryID = ?");
        $stmt->bind_param("si", $newStatus, $category_id);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE product SET ProductStatus = ? WHERE CategoryID = ?");
        $stmt2->bind_param("si", $newStatus, $category_id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();

        header("Location: category_view.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Failed to update status.'); window.location.href='category_view.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories</title>
    <link rel="stylesheet" href="category_view.css">
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
        <h2>Product Categories</h2>

        <form method="GET" action="" class="search">
            <input type="text" name="search" placeholder="Search category" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="search">Search</button>
        </form>

        <div class="add-category-form">
            <button type="button" onclick="openAddModal()" class="add_btn">Add Category</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">ID</th>
                    <th>Image</th>
                    <th>Category Name</th>
                    <th style="text-align: center;">Status</th>
                    <th class="action"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($category_result->num_rows > 0): ?>
                    <?php while ($category = $category_result->fetch_assoc()): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $category['CategoryID']; ?></td>
                            <?php
                                $category_image_name = $category['CategoryPicture'];
                                $image_path = !empty($category_image_name) ? "../image/categories/" . $category_image_name : "";
                            ?>
                            <td style="text-align: center;">
                                <?php if (!empty($image_path) && file_exists($image_path)): ?>
                                    <img src="<?php echo $image_path; ?>" alt="Category Image" style="width: 120px; height: 80px;">
                                <?php else: ?>
                                    <span>No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $category['CategoryName']; ?></td>
                            <td class="<?php echo ($category['CategoryStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>" style="text-align: center;">
                                <?php echo ucfirst($category['CategoryStatus']); ?>
                            </td>
                            <td>
                                <button name="edit_category" onclick='editCategory(<?php echo json_encode($category["CategoryID"]); ?>, <?php echo json_encode($category["CategoryName"]); ?>)'>Edit</button>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="category_id" value="<?php echo $category['CategoryID']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $category['CategoryStatus']; ?>">
                                    <button type="submit" class="btn-status <?php echo ($category['CategoryStatus'] == 'Active') ? 'btn-inactive' : 'btn-active'; ?>">
                                        <?php echo ($category['CategoryStatus'] == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                 </form>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No category found.</b></td>
                        </tr>
                    <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add Categories</h2>
            <form method="POST" action="" enctype="multipart/form-data" id="addCategoryForm">
                <input type="hidden" name="add_category" value="1">
                <label>Category Image:<span class="required">*</span><span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="profile_picture" id="add-profile-picture" accept=".jpg,.jpeg,.png" required>
                
                <label>Category Name:<span class="required">*</span></label>
                <input type="text" name="category_name" id="add-name" required 
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
            <h2>Edit Category</h2>
            <form method="POST" action="" enctype="multipart/form-data" id="editCategoryForm">
                <label>Category Image:<span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="profile_picture" id="edit-profile-picture" accept=".jpg,.jpeg,.png">
                
                <label>New name:<span class="required">*</span></label>
                <input type="hidden" name="category_id" id="editCategoryID">
                <input type="text" name="new_category_name" id="edit-name" required 
                       oninput="validateNameInRealTime(this.value, 'edit')"
                       onblur="checkAvailability('name', this.value, document.getElementById('editCategoryID').value, false)">
                <div id="edit-name-error" class="error"></div>
                
                <div class="submit_btn">
                    <button type="submit" name="edit_category" id="edit-submit-btn" disabled>Update</button>
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

        const error = validateCategoryName(value);
        if (error) {
            errorElement.textContent = error;
            errorElement.style.display = 'block';
            inputField.classList.add('error-field');
            if (prefix === 'add') isAddNameValid = false;
            else isEditNameValid = false;
        } else {
            inputField.classList.add('valid-field');
            const isAvailable = await checkAvailability('name', value,
                prefix === 'edit' ? document.getElementById('editCategoryID').value : 0,
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

    function checkAvailability(type, value, categoryId = 0, isAddModal = false) {
        const prefix = isAddModal ? 'add-' : 'edit-';
        const errorElement = document.getElementById(`${prefix}${type}-error`);
        const inputField = document.getElementById(`${prefix}${type}`);

        return new Promise((resolve) => {
            const error = validateCategoryName(value);
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
            formData.append('category_id', categoryId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorElement.textContent = 'Category name already exists';
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

    function validateCategoryName(name) {
        if (name.trim() === '') return 'Category name is required';
        if (name.length > 50) return 'Category name must be 50 characters or less';
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

    // ================== EDIT CATEGORY SPECIFIC ==================
    function validateEditForm() {
        const nameInput = document.getElementById('edit-name');
        const name = nameInput.value.trim();
        const imageInput = document.getElementById('edit-profile-picture');
        let isValid = true;

        // Clear previous errors
        document.getElementById('edit-name-error').textContent = '';
        nameInput.classList.remove('error-field');

        // Validate name
        const nameError = validateCategoryName(name);
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

    function editCategory(id, name) {
        document.getElementById("editModal").style.display = "block";
        document.getElementById("editCategoryID").value = id;
        document.getElementById("edit-name").value = name;
        originalEditName = name;
        clearErrors('edit');
        isEditNameValid = true;
        updateSubmitButton('edit');
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    // ================== ADD CATEGORY SPECIFIC ==================
    function openAddModal() {
        document.getElementById("addModal").style.display = "block";
        isAddNameValid = false;
        updateSubmitButton('add');
        document.getElementById('add-profile-picture').value = '';
    }

    function closeAddModal() {
        document.getElementById("addModal").style.display = "none";
        document.getElementById("addCategoryForm").reset();
        clearErrors('add');
    }

    // ================== EVENT LISTENERS ==================
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('add-profile-picture').addEventListener('change', function () {
            updateSubmitButton('add');
        });

        document.getElementById('addCategoryForm').addEventListener('submit', async function (e) {
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

        document.getElementById('editCategoryForm')?.addEventListener('submit', function (e) {
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

    window.onclick = function (event) {
        if (event.target == document.getElementById("addModal")) {
            closeAddModal();
        }
        if (event.target == document.getElementById("editModal")) {
            closeEditModal();
        }
    }
</script>

</body>
</html>