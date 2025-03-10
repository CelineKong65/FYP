<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_category'])) {
        $categoryName = trim($_POST['category_name']);
        $adminID = $_SESSION['AdminID'];

        if (!empty($categoryName)) {
            $checkSql = "SELECT * FROM Category WHERE CategoryName = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $categoryName);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                echo "<script>alert('Category name already exists. Please enter a different name.');</script>";
            } else {
                $sql = "INSERT INTO Category (CategoryName, AdminID) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $categoryName, $adminID);
                if ($stmt->execute()) {
                    echo "<script>alert('Category added successfully!'); window.location.href='category_view.php';</script>";
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif (isset($_POST['delete_category'])) {
        $categoryID = $_POST['category_id'];
        $sql = "DELETE FROM Category WHERE CategoryID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $categoryID);
        if ($stmt->execute()) {
            echo "<script>alert('Category deleted successfully!'); window.location.href='category_view.php';</script>";
        }
        $stmt->close();
    } elseif (isset($_POST['edit_category'])) {
        $categoryID = $_POST['category_id'];
        $newName = trim($_POST['new_category_name']);

        // Get the current category name
        $currentSql = "SELECT CategoryName FROM Category WHERE CategoryID = ?";
        $currentStmt = $conn->prepare($currentSql);
        $currentStmt->bind_param("i", $categoryID);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result();
        $currentRow = $currentResult->fetch_assoc();
        $currentName = $currentRow['CategoryName'];
        $currentStmt->close();

        if ($newName === $currentName) {
            echo "<script>alert('The new name is the same as the current name. Please enter a different name.');</script>";
        } else {
            $checkSql = "SELECT * FROM Category WHERE CategoryName = ? AND CategoryID != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $newName, $categoryID);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                echo "<script>alert('Category name already exists. Please enter a different name.');</script>";
            } else {
                $sql = "UPDATE Category SET CategoryName = ? WHERE CategoryID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newName, $categoryID);
                if ($stmt->execute()) {
                    echo "<script>alert('Category updated successfully!'); window.location.href='category_view.php';</script>";
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    }
}

$searchQuery = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . trim($_GET['search']) . "%";
    $sql = "SELECT * FROM Category WHERE CategoryName LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $sql = "SELECT * FROM Category";
    $result = $conn->query($sql);
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

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    text-align: center;
    width: 200px;
    margin: auto;
    border-radius: 10px;
}

.sidebar ul li a {
    color: white;
    text-decoration: none;
    display: block;
    font-size: 18px;
    transition: 0.3s;
    padding: 15px 20px;
}

.sidebar ul li a:hover {
    background-color: #1e3a8a;
    border-radius: 5px;
    font-weight: bold;
}

.main-content {
    flex-grow: 1;
    padding: 20px;
    margin: 10px;
    background-color: #ffffff;
    border-radius: 10px;
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
    background-color: #f0f4ff;
}

table tr:hover {
    background-color:rgb(237, 236, 236);
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

button[name="delete_category"] {
    background-color: red;
}

button[name="delete_category"]:hover {
    background-color: #c82333;
}

.actions {
    display: flex;
    gap: 10px;
}

.edit {
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

.edit-content {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    width: 350px;
    margin: auto;
    margin-top: 200px;
}

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}
.close:hover{
    color: red;
}

#editModal{
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

.edit-content{
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    margin: auto;
    margin-top: 300px;
}

#editModal h2{
    margin-top: 0;
    color: #1e3a8a;
    font-size: 25px;
    text-align: center;
    margin-bottom: 30px;
}

#editModal label{
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
    color: #1e3a8a;
    text-align: left;
}

#editModal input{
    width: 100%;
    padding: 8px;
    margin-bottom: 13px;
    border: 1px solid #93c5fd;
    border-radius: 4px;
}

#editModal button[type="submit"], #addModal button[type="submit"] {
    background-color: #1e3a8a;
}

#editModal button[type="submit"]:hover, #addModal button[type="submit"]:hover {
    background-color: #1d4ed8;
}
.submit_btn{
    display: flex;
    justify-content: flex-end;
}
    </style>
</head>
<body>
<div class="header">
        <?php include 'header.php'; ?>
    </div>

    <div class="container">
        <div class="sidebar">
            <ul>
                <li><a href="report.php">Report</a></li>
                <li><a href="customer_view.php">Customer List</a></li>
                <li><a href="admin_view.php">Admin List</a></li>
                <li><a href="category_view.php">Category List</a></li>
                <li><a href="product_view.php">Product List</a></li>
                <li><a href="order_view.php">Order List</a></li>
                <li><a href="profile.php">My Profile</a></li>
            </ul>
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
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['CategoryID']}</td>
                                <td>{$row['CategoryName']}</td>
                                <td class='actions'>
                                    <button name='edit_category' onclick='openEdit({$row['CategoryID']}, \"{$row['CategoryName']}\")'>Edit</button>
                                    <form method='POST' action='' style='display:inline;'>
                                        <input type='hidden' name='category_id' value='{$row['CategoryID']}'>
                                        <button type='submit' name='delete_category' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this category?\")'>Delete</button>
                                    </form>           
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='no_category'>No categories found.</td></tr>";
                }
                ?>
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
                    <button type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentCategoryName = "";

        function openEdit(id, name) {
            document.getElementById("editCategoryID").value = id;
            document.getElementById("editCategoryName").value = name;
            currentCategoryName = name;
            document.getElementById("editModal").style.display = "block";
        }

        function closeEdit() {
            document.getElementById("editModal").style.display = "none";
        }

        function validateEditForm() {
            let newName = document.getElementById("editCategoryName").value.trim();
            if (newName === currentCategoryName) {
                alert("The new name is the same as the current name. Please enter a different name.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
