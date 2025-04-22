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

    $check_query = "SELECT CategoryID FROM category WHERE CategoryName = ? AND CategoryID != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $new_name, $category_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Category name already exists.'); window.location.href='category_view.php';</script>";
        exit();
    }
    $stmt->close();

    $update_query = "UPDATE category SET CategoryName = ? WHERE CategoryID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_name, $category_id);
    if ($stmt->execute()) {
        echo "<script>alert('Category updated successfully!'); window.location.href='category_view.php';</script>";
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
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.header {
    margin-bottom: 50px;
}

.container {
    margin-top: 50px;
    display: flex;
    flex: 1;
    margin-left: 250px;
}

.sidebar {
    width: 220px;
    background-color: #0077b6;
    padding-top: 30px;
    text-align: center;
    border-radius: 20px;
    margin: 30px;
    height: 650px;
    margin-top: 150px;
    position: fixed;
    left: 0;
    top: 0; 
}

.main-content {
    flex-grow: 1;
    padding: 20px;
    margin: 10px;
    background-color: #ffffff;
    border-radius: 10px;
    position: relative;
}

h2 {
    color: #1e3a8a;
    font-size: 40px;
    text-align: center;
    margin-bottom: 50px;
}

.add-category-form, .search-bar{
    display: flex;
    justify-content: flex-end;
}

.add_btn{
    margin-top: 12px;
    background-color: #28a745;
}

.add_btn:hover {
    background-color: #218838;
}

input{
    width: 200px;
    padding: 8px;
    font-size: 12px;
    margin-bottom: 10px;
    border: 1px solid #0A2F4F;
    border-radius: 4px;
    margin-right: 10px;
}

table {
    width: 60%;
    border-collapse: collapse;
    margin: 0 auto;
    margin-top: 10px;
}

table, th, td {
    font-size: 15px;
    border: 1px solid #1e3a8a;
}

th, td {
    padding: 12px;
    text-align: left;
}

th {
    background-color: #1e3a8a;
    color: white;
}

tr:nth-child(even) {
    background-color: #e3f2fd;
}

table tr:hover {
    background-color:rgb(237, 236, 236);
}

th.action{
    width: 180px;
}

button {
    padding: 10px 16px;
    background-color: #1e3a8a;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 10px;
    font-size: 13px;
}

button.search {
    width: 112px;
}

button:hover {
    background-color: #1d4ed8;
}

button[name="edit_category"] {
    color: black;
    background-color: #ffc107;
}

button[name="edit_category"]:hover {
    background-color: #e0a800d1;
}

.status-inactive{
    color: red;
}

.status-active{
    color: #05ac2c;
}

.btn-inactive{
    background-color:red;
}

.btn-inactive:hover{
    background-color: #c82333;
}

.btn-active{
    background-color: #05ac2c;
}

.btn-active:hover {
    background-color: #218838;
}

.actions {
    display: flex;
    gap: 10px;
}

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}
.close:hover{
    color: red;
}

#editModal, #addModal{
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}

.edit-content, .add-content{
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    margin: auto;
    margin-top: 300px;
}

.add-content{
    margin-top: 250px;
}

#editModal h2, #addModal h2{
    margin-top: 0;
    color: #1e3a8a;
    font-size: 25px;
    text-align: center;
    margin-bottom: 30px;
}

#editModal label, #addModal label{
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
    color: #1e3a8a;
    text-align: left;
    font-size: 15px;
}

#editModal input, #addModal input{
    width: 95%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #93c5fd;
    border-radius: 4px;
    font-size: 13px;
}

#editModal button[type="submit"], #addModal button[type="submit"]{
    background-color: #1e3a8a;
    color: white;
}

#editModal button[type="submit"]:hover, #addModal button[type="submit"]:hover{
    background-color: #1d4ed8;
}

.submit_btn{
    display: flex;
    justify-content: flex-end;
}

input::placeholder{
    font-size: 12px;
    font-style: italic;
}

span{
    color:gray;
    font-size: 12px;
}
    </style>
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

        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search category" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                <button type="submit" name="search_category" class="search">Search</button>
            </form>
        </div>

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
            <form method="POST" action="" onsubmit="return validateEditForm()">
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