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
    <link rel='stylesheet' href='category_view.css'>
</head>
<body>
    <div class="container">
        <h1>Product Categories</h1>

        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search categories..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                <button type="submit" name="search_category">Search</button>
            </form>
        </div>

        <div class="add-category-form">
            <form method="POST" action="">
                <input type="text" name="category_name" placeholder="Enter category name" required />
                <button type="submit" name="add_category" class="add-btn">Add Category</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Actions</th>
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
                                    <button onclick='openEdit({$row['CategoryID']}, \"{$row['CategoryName']}\")' class='edit-btn'>Edit</button>
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

    <div id="edit" class="edit">
        <div class="edit-content">
            <span class="close" onclick="closeEdit()">&times;</span>
            <h2>Edit Category</h2>
            <form method="POST" action="" onsubmit="return validateEditForm()">
                <input type="hidden" name="category_id" id="editCategoryID">
                <input type="text" name="new_category_name" id="editCategoryName" class="text-box"required>
                <button type="submit" name="edit_category" class="update-btn">Update</button>
            </form>
        </div>
    </div>

    <script>
        let currentCategoryName = "";

        function openEdit(id, name) {
            document.getElementById("editCategoryID").value = id;
            document.getElementById("editCategoryName").value = name;
            currentCategoryName = name;
            document.getElementById("edit").style.display = "block";
        }

        function closeEdit() {
            document.getElementById("edit").style.display = "none";
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
