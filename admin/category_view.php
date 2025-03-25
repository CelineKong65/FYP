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

        $insert_query = "INSERT INTO category (CategoryName, AdminID) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("si", $category_name, $admin_id);
        if ($stmt->execute()) {
            echo "<script>alert('Category added successfully!'); window.location.href='category_view.php';</script>";
        }
        $stmt->close();
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

if (isset($_POST['delete_category'])) {
    $category_id = intval($_POST['category_id']);

    $delete_query = "DELETE FROM category WHERE CategoryID = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        echo "<script>alert('Category deleted successfully!'); window.location.href='category_view.php';</script>";
    }
    $stmt->close();
    exit();
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

        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search category" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                <button type="submit" name="search_category" class="search">Search</button>
            </form>
        </div>

        <div class="add-category-form">
            <form method="POST" action="">
                <input type="text" name="category_name" placeholder="Enter category name" required />
                <button type="submit" name="add_category" class="add_btn">Add Category</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($category_result->num_rows > 0): ?>
                    <?php while ($category = $category_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $category['CategoryID']; ?></td>
                            <td><?php echo $category['CategoryName']; ?></td>
                            <td>
                                <button name="edit_category" onclick='editCategory(<?php echo json_encode($category["CategoryID"]); ?>, <?php echo json_encode($category["CategoryName"]); ?>)'>Edit</button>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="category_id" value="<?php echo $category["CategoryID"]; ?>">
                                    <button type="submit" name="delete_category" onclick="return confirm('Are you sure you want to delete this category?');">Delete</button>
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
    <div class="container">

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
        function editCategory(id, name) {
            document.getElementById("editCategoryID").value = id;
            document.getElementById("editCategoryName").value = name;
            document.getElementById("editModal").style.display = "block";
        }

        function closeEdit() {
            document.getElementById("editModal").style.display = "none";
        }
    </script>
</body>
</html>