<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$category_query = "SELECT * FROM category";
$category_result = $conn->query($category_query);

$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$product_query = "SELECT * FROM product";
if (!empty($selected_category)) {
    $product_query .= " WHERE CategoryID = ?";
}
if (!empty($search_query)) {
    $product_query .= empty($selected_category) ? " WHERE" : " AND";
    $product_query .= " ProductName LIKE ?";
}
$stmt = $conn->prepare($product_query);
if (!empty($selected_category) && !empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("is", $selected_category, $search_param);
} elseif (!empty($selected_category)) {
    $stmt->bind_param("i", $selected_category);
} elseif (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("s", $search_param);
}
$stmt->execute();
$product_result = $stmt->get_result();

if (isset($_POST['update_product'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $image = isset($_FILES['image']['name']) ? $_FILES['image']['name'] : null;
    $image_tmp = isset($_FILES['image']['tmp_name']) ? $_FILES['image']['tmp_name'] : null;

    $check_query = "SELECT ProductID FROM product WHERE ProductID = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($existing_image);
    $stmt->fetch();
    $stmt->close();

    if (!empty($image)) {
        $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='product_view.php';</script>";
            exit();
        }

        $image_name = strtolower(str_replace(' ', '-', $name)) . "." . $image_extension;
        $target_dir = "../image/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($image_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='product_view.php';</script>";
            exit();
        }

        if (!empty($existing_image) && file_exists($target_dir . $existing_image)) {
            unlink($target_dir . $existing_image); // Delete the old image file
        }
    } else {
        $image_name = $existing_image;
    }

    $category_id = intval($_POST['category_id']);

    $update_query = "UPDATE product SET ProductName = ?, ProductPrice = ?, ProductDesc = ?, ProductStock = ?, CategoryID = ? WHERE ProductID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdsiii", $name, $price, $description, $stock, $category_id, $product_id);
    
    if ($stock <= 0) {
        echo "<script>alert('Stock quantity must be greater than 0.'); window.location.href='product_view.php';</script>";
        exit();
    }

    if ($stmt->execute()) {
        echo "<script>alert('Product updated successfully!'); window.location.href='product_view.php';</script>";
    } else {
        echo "<script>alert('Failed to update product.'); window.location.href='product_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);

    $delete_query = "DELETE FROM product WHERE ProductID = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo "<script>alert('Product deleted successfully!'); window.location.href='product_view.php';</script>";
    } else {
        echo "<script>alert('Failed to delete product.'); window.location.href='product_view.php';</script>";
    }
    exit();
}

if (isset($_POST['add_product'])) {
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));

    if (!in_array($image_extension, $allowed_types)) {
        echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='product_view.php';</script>";
        exit();
    }

    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);
    $admin_id = $_SESSION['AdminID']; // Assuming AdminID is stored in the session

    $image_name = strtolower(str_replace(' ', '-', $name)) . "." . $image_extension;
    $target_dir = "../image/";
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($image_tmp, $target_file)) {
        $insert_query = "INSERT INTO product (ProductName, ProductPrice, ProductDesc, ProductStock, CategoryID, AdminID) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sdsiii", $name, $price, $description, $stock, $category_id, $admin_id);

        if ($stock <= 0) {
            echo "<script>alert('Stock quantity must be greater than 0.'); window.location.href='product_view.php';</script>";
            exit();
        }
        if ($stmt->execute()) {
            echo "<script>alert('Product added successfully!'); window.location.href='product_view.php';</script>";
        } else {
            echo "<script>alert('Failed to add product.'); window.location.href='product_view.php';</script>";
        }
    } else {
        echo "<script>alert('Failed to upload image.'); window.location.href='product_view.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
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

.message {
    padding: 10px;
    color: #1e3a8a;
    border-radius: 4px;
    margin-bottom: 20px;
}

form.search {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

form.editForm, .addForm {
    margin-bottom: 20px;
}

label{
    font-weight: bold;
}

.search label{
    color: #0A2F4F;
    transform: translateY(8px);
    font-size: 14px;
}

select, input{
    padding: 8px;
    font-size: 12px;
    margin-bottom: 10px;
    border: 1px solid #0A2F4F;
    border-radius: 4px;
}

.search input {
    width: 200px;
}

.add-content select, .edit-content select {
    border: 1px solid #93c5fd;
    width: 210px;
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
    width: 105px;
}

button:hover {
    background-color: #1d4ed8;
}

button[name="edit_product"] {
    color: black;
    background-color: #ffc107;
}

button[name="edit_product"]:hover {
    background-color: #e0a800d1;
}

button[name="delete_product"] {
    background-color: red;
}

button[name="delete_product"]:hover {
    background-color: #c82333;
}

.add {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
}

.add_btn{
    background-color: #28a745;
}

.add_btn:hover {
    background-color: #218838;
}

.submit{
    display: flex;
    align-items: center;
    justify-content: space-between;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
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

.price {
    text-align: center;
}

img {
    max-width: 100%;
    height: auto;
}

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}

.close:hover {
    color: red;
}

#editModal, #addModal {
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

.edit-content, .add-content {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    margin: auto;
    margin-top: 200px;
}

#editModal h3, #addModal h3 {
    margin-top: 0;
    color: #1e3a8a;
    font-size: 25px;
    text-align: center;
}

#editModal label, #addModal label {
    display: block;
    margin-bottom: 5px;
    color: #1e3a8a;
    font-size: 15px;
}

#editModal input, #editModal textarea, #addModal input, #addModal textarea {
    width: 95%;
    padding: 8px;
    margin-bottom: 13px;
    border: 1px solid #93c5fd;
    border-radius: 4px;
    font-size: 13px;
}

#editModal button[type="submit"], #addModal button[type="submit"] {
    background-color: #1e3a8a;
}

#editModal button[type="submit"]:hover, #addModal button[type="submit"]:hover {
    background-color: #1d4ed8;
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
            <h2>Product List</h2>

            <?php if (isset($_SESSION['message'])): ?>
                <p class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
            <?php endif; ?>

            <form method="GET" action="" class="search">
                <label for="category">Filter by Category:</label>
                <select name="category" id="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php while ($row = $category_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['CategoryID']; ?>" <?php echo ($selected_category == $row['CategoryID']) ? 'selected' : ''; ?>>
                            <?php echo $row['CategoryName']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="search" placeholder="Search by product name" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>
                    
            <div class="add">
                <button onclick="openAddModal()" class="add_btn">Add Product</button>
            </div>

            <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th style="text-align: center;">Price (RM)</th>
                    <th style="width: 350px;">Description</th>
                    <th style="text-align: center;">Stock</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($product_result->num_rows > 0): ?>
                    <?php while ($product = $product_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['ProductID']; ?></td>
                            <td style="display: grid; place-items: center;">
                                <?php
                                $imageName = strtolower(str_replace(' ', '-', $product['ProductName']));
                                $jpgPath = "../image/{$imageName}.jpg";
                                $pngPath = "../image/{$imageName}.png";

                                if (file_exists($jpgPath)) {
                                    echo "<img src='{$jpgPath}' alt='{$product['ProductName']}' width='150'>";
                                } elseif (file_exists($pngPath)) {
                                    echo "<img src='{$pngPath}' alt='{$product['ProductName']}' width='150'>";
                                } else {
                                    echo "<img src='../image/placeholder.jpg' alt='Image not available' width='150'>";
                                }
                                ?>
                            </td>
                            <td><?php echo $product['ProductName']; ?></td>
                            <td style="text-align: center;"><?php echo number_format($product['ProductPrice'], 2); ?></td>
                            <td><?php echo $product['ProductDesc']; ?></td>
                            <td style="text-align: center;"><?php echo $product['ProductStock']; ?></td>
                            <td>
                            <button name="edit_product" onclick='editProduct(<?php echo json_encode($product["ProductID"]); ?>, <?php echo json_encode($product["ProductName"]); ?>, <?php echo json_encode($product["ProductPrice"]); ?>, <?php echo json_encode($product["ProductDesc"]); ?>, <?php echo json_encode($product["ProductStock"]); ?>, <?php echo json_encode($product["CategoryID"]); ?>)'>Edit</button>
                            <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                    <button type="submit" name="delete_product" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;color:red;"><b>No products found for this category.</b></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Product</h3>
            <form method="POST" action="" enctype="multipart/form-data" class="edit">
                <input type="hidden" name="product_id" id="product_id">
                <label>Image:</label>
                <input type="file" name="image">
                <label>Name:</label>
                <input type="text" name="name" id="name" required>
                <label>Price:</label>
                <input type="number" step="1.00" name="price" id="price" required>
                <label>Description:</label>
                <textarea name="description" id="description" required></textarea>
                <label>Stock:</label>
                <input type="number" step="10.00" name="stock" id="stock" required>
                <label>Category:</label>
                <div class="submit">
                    <select name="category_id" id="category_id" required>
                        <?php $category_result->data_seek(0); ?>
                        <?php while ($row = $category_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['CategoryID']; ?>" <?php echo ($row['CategoryID'] == $selected_category) ? 'selected' : ''; ?>>
                                <?php echo $row['CategoryName']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="update_product">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Product</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <label>Image:</label>
                <input type="file" name="image" required>
                <label>Name:</label>
                <input type="text" name="name" required>
                <label>Price:</label>
                <input type="number" step="5.00" name="price" required>
                <label>Description:</label>
                <textarea name="description" required></textarea>
                <label>Stock:</label>
                <input type="number" step="10.00" name="stock" required>
                <label>Category:</label>
                <div class="submit">
                    <select name="category_id" required>
                        <?php $category_result->data_seek(0); ?>
                        <?php while ($row = $category_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['CategoryID']; ?>"><?php echo $row['CategoryName']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="add_product" class="add_sbm">Add</button>                    
                </div>

            </form>
        </div>
    </div>

    <script>
        function editProduct(id, name, price, description, stock, category_id) {
            document.getElementById('product_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('price').value = price;
            document.getElementById('description').value = description;
            document.getElementById('stock').value = stock;
            document.getElementById('category_id').value = category_id;
            document.getElementById('editModal').style.display = "block";
        }


        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
    </script>
</body>
</html>