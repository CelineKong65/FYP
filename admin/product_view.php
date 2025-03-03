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
    $product_id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);

    $check_query = "SELECT ProductID FROM product WHERE ProductID = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $update_query = "UPDATE product SET ProductName = ?, ProductPrice = ?, ProductDesc = ? WHERE ProductID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sdsi", $name, $price, $description, $product_id);

        if ($stmt->execute()) {
            echo "<script>alert('Product updated successfully!'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
        } else {
            echo "<script>alert('Failed to update product.'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
        }
    } else {
        echo "<script>alert('Product not found.'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
    }
    exit();
}

if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);

    $delete_query = "DELETE FROM product WHERE ProductID = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo "<script>alert('Product deleted successfully!'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
    } else {
        echo "<script>alert('Failed to delete product.'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
    }
    exit();
}

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);

    $check_query = "SELECT ProductID FROM product WHERE ProductName = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Product name already exists. Please enter a unique product name.'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
    } else {
        $insert_query = "INSERT INTO product (ProductName, ProductPrice, ProductDesc, CategoryID) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sdsi", $name, $price, $description, $category_id);

        if ($stmt->execute()) {
            echo "<script>alert('Product added successfully!'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
        } else {
            echo "<script>alert('Failed to add product.'); window.location.href='product_view.php?category=" . $selected_category . "&search=" . $search_query . "';</script>";
        }
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
    <link rel="stylesheet" href="product_view.css">
</head>
<body>
    <h2>Product List</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <p class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
    <?php endif; ?>

    <form method="GET" action="" class="header">
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
        <button onclick="openAddModal()" class="add">Add Product</button>
    </div>


    <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Picture</th>
            <th>Name</th>
            <th style="text-align: center;">Price (RM)</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($product_result->num_rows > 0): ?>
            <?php while ($product = $product_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['ProductID']; ?></td>
                    <td><img src="../image/<?php echo strtolower(str_replace(' ', '-', $product['ProductName'])); ?>.jpg" alt="<?php echo $product['ProductName']; ?>" width="50"></td>
                    <td><?php echo $product['ProductName']; ?></td>
                    <td style="text-align: center;"><?php echo number_format($product['ProductPrice'], 2); ?></td>
                    <td><?php echo $product['ProductDesc']; ?></td>
                    <td>
                        <button name="edit_product" onclick='editProduct(<?php echo json_encode($product["ProductID"]); ?>, <?php echo json_encode($product["ProductName"]); ?>, <?php echo json_encode($product["ProductPrice"]); ?>, <?php echo json_encode($product["ProductDesc"]); ?>)'>Edit</button>
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

    <div id="editModal">
        <div class="edit-content">
            <span class="close"onclick="closeModal()">&times</span>
            <h3>Edit Product</h3>
            <form method="POST" action="" class="edit">
                <input type="hidden" name="product_id" id="product_id">
                <label>Name:</label>
                <input type="text" name="name" id="name" required>
                <label>Price:</label>
                <input type="number" step="1.00" name="price" id="price" required>
                <label>Description:</label>
                <textarea name="description" id="description" required></textarea>
                <button type="submit" name="update_product class="upd_button">Update</button>
            </form>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close"onclick="closeAddModal()">&times</span>
            <h3>Add Product</h3>
                <form method="POST" action="" class="addForm">
                <label>Name:</label>
                <input type="text" name="name" required>
                <label>Price:</label>
                <input type="number" step="5.00" name="price" required>
                <label>Description:</label>
                <textarea name="description" required></textarea>
                <label>Category:</label>
                <select name="category_id" required>
                    <?php $category_result->data_seek(0); ?>
                    <?php while ($row = $category_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['CategoryID']; ?>"><?php echo $row['CategoryName']; ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_product" class="add_button">Add</button>
                </form>
        </div>
    </div>

    <script>
        function editProduct(id, name, price, description) {
            document.getElementById('product_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('price').value = price;
            document.getElementById('description').value = description;
            document.getElementById('editModal').style.display = 'block';
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