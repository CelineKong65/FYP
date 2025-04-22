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
    $category_query = "SELECT * FROM category WHERE CategoryName LIKE ?";
    $stmt = $conn->prepare($category_query);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $category_result = $stmt->get_result();
} else {
    $category_query = "SELECT * FROM category";
    $category_result = $conn->query($category_query);
}

if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $admin_id = $_SESSION['AdminID'];

    if (!empty($category_name)) {
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

        if (!empty($profile_picture)) {
            $image_extension = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png'];

            if (!in_array($image_extension, $allowed_types)) {
                echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='category_view.php';</script>";
                exit();
            }

            $base_name = preg_split("/[\s\/]+/", $category_name)[0];
            $base_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $base_name); // remove special characters
            $image_name = strtolower($base_name) . '.' . $image_extension;
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
            if ($image_name !== "default.png" && file_exists($target_file)) {
                unlink($target_file);
            }
            echo "<script>alert('Error adding category.'); window.location.href='category_view.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Category name is required.'); window.location.href='category_view.php';</script>";
    }
    exit();
}

if (isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $new_name = trim($_POST['new_category_name']);

    if (empty($new_name)) {
        echo "<script>alert('Category name cannot be empty.'); window.location.href='category_view.php';</script>";
        exit();
    }

    // Check for duplicate category names
    $stmt = $conn->prepare("SELECT CategoryID FROM category WHERE CategoryName = ? AND CategoryID != ?");
    $stmt->bind_param("si", $new_name, $category_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Category name already exists.'); window.location.href='category_view.php';</script>";
        exit();
    }
    $stmt->close();

    // Get current image name
    $stmt = $conn->prepare("SELECT CategoryPicture FROM category WHERE CategoryID = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->bind_result($old_image);
    $stmt->fetch();
    $stmt->close();

    $target_dir = "../image/categories/";
    
    // Generate new filename based on new category name
    $name_parts = preg_split("/[\s\/]+/", $new_name);
    $base_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $name_parts[0]);
    $clean_name = strtolower($base_name);

    // Determine file extension (use from new upload if exists, otherwise from old image)
    $new_image_uploaded = !empty($_FILES['profile_picture']['name']);
    $ext = '';
    
    if ($new_image_uploaded) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
    } elseif (!empty($old_image)) {
        $ext = pathinfo($old_image, PATHINFO_EXTENSION);
    } else {
        $ext = 'png'; // default extension if no image exists
    }

    $image_name_to_store = $clean_name . '.' . $ext;
    $new_path = $target_dir . $image_name_to_store;

    // Handle file operations
    if ($new_image_uploaded) {
        // Delete old image if it exists and isn't default
        if (!empty($old_image) && $old_image !== 'default.png' && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
        }

        // Move new uploaded file
        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $new_path)) {
            echo "<script>alert('Failed to upload new image.'); window.location.href='category_view.php';</script>";
            exit();
        }
    } else {
        // No new image uploaded - just rename existing file
        if (!empty($old_image) && $old_image !== 'default.png' && file_exists($target_dir . $old_image)) {
            // Rename the existing file
            if (!rename($target_dir . $old_image, $new_path)) {
                echo "<script>alert('Failed to rename image file.'); window.location.href='category_view.php';</script>";
                exit();
            }
        } elseif (empty($old_image) || $old_image === 'default.png') {
            // If no image or default image, copy default to new name
            if (!copy($target_dir . 'default.png', $new_path)) {
                echo "<script>alert('Failed to create new image file.'); window.location.href='category_view.php';</script>";
                exit();
            }
        }
    }

    // Update database
    $stmt = $conn->prepare("UPDATE category SET CategoryName = ?, CategoryPicture = ? WHERE CategoryID = ?");
    $stmt->bind_param("ssi", $new_name, $image_name_to_store, $category_id);
    if ($stmt->execute()) {
        echo "<script>alert('Category updated successfully!'); window.location.href='category_view.php';</script>";
    } else {
        // If update failed, try to revert file changes
        if (file_exists($new_path)) {
            unlink($new_path);
        }
        echo "<script>alert('Failed to update category.'); window.location.href='category_view.php';</script>";
    }
    $stmt->close();
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $category_id = (int)$_POST['category_id'];
    $currentStatus = strtolower($_POST['current_status']);
    
    $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';

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
    <link rel='stylesheet' href='category_view.css'>
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
                                $category_image_name = $category['CategoryPicture']; // e.g., electronics.png
                                $image_path = "../image/categories/" . $category_image_name;

                                if (!file_exists($image_path) || empty($category_image_name)) {
                                    $image_path = "../image/categories/default.png"; // fallback image
                                }
                            ?>
                            <td style="text-align: center;">
                                <img src="<?php echo $image_path; ?>" alt="Category Image" style="width: 120px; height: 80px;;">
                            </td>
                            <td><?php echo $category['CategoryName']; ?></td>
                            <td class="<?php echo ($category['CategoryStatus'] === 'active') ? 'status-active' : 'status-inactive'; ?>" style="text-align: center;">
                                <?php echo $category['CategoryStatus']; ?>
                            </td>
                            <td>
                                <button name="edit_category" onclick='editCategory(<?php echo json_encode($category["CategoryID"]); ?>, <?php echo json_encode($category["CategoryName"]); ?>)'>Edit</button>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="category_id" value="<?php echo $category['CategoryID']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $category['CategoryStatus']; ?>">
                                    <button type="submit" class="btn-status <?php echo ($category['CategoryStatus'] == 'active') ? 'btn-inactive' : 'btn-active'; ?>">
                                        <?php echo ($category['CategoryStatus'] == 'active') ? 'Deactivate' : 'Activate'; ?>
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
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="add_category" value="1">
                <label>Category Image:<span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png" required>
                <label>Category Name:</label>
                <input type="text" name="category_name" id="add-name" required>
                <div class="add_div">
                    <button type="submit">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="edit">
        <div class="edit-content">
            <span class="close" onclick="closeEdit()">&times;</span>
            <h2>Edit Category</h2>
            <form method="POST" action="" onsubmit="return validateEditForm()" enctype="multipart/form-data">
                <label>Category Image:<span> (.jpg, .jpeg or .png only)</span></label>
                <input class="img" type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png">
                <label>New name:</label>
                <input type="hidden" name="category_id" id="editCategoryID">
                <input type="text" name="new_category_name" id="editCategoryName" required>
                <div class="submit_btn">
                    <button type="submit" name="edit_category">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById("addModal").style.display = "block";
        }
        
        function closeAddModal() {
            document.getElementById("addModal").style.display = "none";
        }

        function editCategory(id, name) {
            document.getElementById("editCategoryID").value = id;
            document.getElementById("editCategoryName").value = name;
            document.getElementById("editModal").style.display = "block";
        }

        function closeEdit() {
            document.getElementById("editModal").style.display = "none";
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

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById("addModal")) {
                closeAddModal();
            }
            if (event.target == document.getElementById("editModal")) {
                closeEdit();
            }
        }
    </script>
</body>
</html>