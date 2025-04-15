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

$product_query = "
    SELECT p.*, 
           COALESCE(ps_S.Stock, 0) as stock_S,
           COALESCE(ps_M.Stock, 0) as stock_M,
           COALESCE(ps_L.Stock, 0) as stock_L,
           COALESCE(ps_XL.Stock, 0) as stock_XL,
           CASE WHEN ps_S.ProductID IS NULL THEN 0 ELSE 1 END as has_S,
           CASE WHEN ps_M.ProductID IS NULL THEN 0 ELSE 1 END as has_M,
           CASE WHEN ps_L.ProductID IS NULL THEN 0 ELSE 1 END as has_L,
           CASE WHEN ps_XL.ProductID IS NULL THEN 0 ELSE 1 END as has_XL
    FROM product p
    LEFT JOIN product_size ps_S ON p.ProductID = ps_S.ProductID AND ps_S.Size = 'S'
    LEFT JOIN product_size ps_M ON p.ProductID = ps_M.ProductID AND ps_M.Size = 'M'
    LEFT JOIN product_size ps_L ON p.ProductID = ps_L.ProductID AND ps_L.Size = 'L'
    LEFT JOIN product_size ps_XL ON p.ProductID = ps_XL.ProductID AND ps_XL.Size = 'XL'
";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($selected_category)) {
    $where_clauses[] = "CategoryID = ?";
    $params[] = $selected_category;
    $types .= 'i';
}

if (!empty($search_query)) {
    $where_clauses[] = "ProductName LIKE ?";
    $params[] = "%$search_query%";
    $types .= 's';
}

if (!empty($where_clauses)) {
    $product_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$product_query .= " GROUP BY p.ProductID";

$stmt = $conn->prepare($product_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}

$product_result = $stmt->get_result();

if ($product_result === false) {
    die("Error getting results: " . $conn->error);
}

if (isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    
    $stock_S = intval($_POST['stock_S']);
    $stock_M = intval($_POST['stock_M']);
    $stock_L = intval($_POST['stock_L']);
    $stock_XL = intval($_POST['stock_XL']);

    if ($stock_S < 0 || $stock_M < 0 || $stock_L < 0 || $stock_XL < 0) {
        echo "<script>alert('Stock quantities must be 0 or greater.'); window.location.href='product_view.php';</script>";
        exit();
    }

    $check_query = "SELECT COUNT(*) FROM product WHERE ProductName = ? AND ProductID != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $name, $product_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Product name already exists. Please use a different name.'); window.location.href='product_view.php';</script>";
        exit();
    }

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
    } else {
        $image_name = $_POST['existing_image'];
    }

    $conn->begin_transaction();

    try {
        $update_query = "UPDATE product SET 
                ProductName = ?, 
                ProductPrice = ?, 
                ProductDesc = ?, 
                CategoryID = ?, 
                ProductPicture = ?
                WHERE ProductID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sdsisi", $name, $price, $description, $category_id, $image_name, $product_id);
        $stmt->execute();
        $stmt->close();

        $sizes = [
            'S' => $stock_S,
            'M' => $stock_M,
            'L' => $stock_L,
            'XL' => $stock_XL
        ];

        $update_size = $conn->prepare("UPDATE product_size SET Stock = ? WHERE ProductID = ? AND Size = ?");
        foreach ($sizes as $size => $quantity) {
            $update_size->bind_param("iis", $quantity, $product_id, $size);
            $update_size->execute();
        }
        $update_size->close();

        $conn->commit();
        
        echo "<script>alert('Product updated successfully!'); window.location.href='product_view.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Failed to update product: " . addslashes($e->getMessage()) . "'); window.location.href='product_view.php';</script>";
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
    $category_id = intval($_POST['category_id']);
    $admin_id = $_SESSION['AdminID'];
    
    $stock_S = intval($_POST['stock_S']);
    $stock_M = intval($_POST['stock_M']);
    $stock_L = intval($_POST['stock_L']);
    $stock_XL = intval($_POST['stock_XL']);

    if ($stock_S < 0 || $stock_M < 0 || $stock_L < 0 || $stock_XL < 0) {
        echo "<script>alert('Stock quantities must be 0 or greater.'); window.location.href='product_view.php';</script>";
        exit();
    }

    $check_query = "SELECT COUNT(*) FROM product WHERE ProductName = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Product name already exists. Please use a different name.'); window.location.href='product_view.php';</script>";
        exit();
    }

    $image_name = strtolower(str_replace(' ', '-', $name)) . "." . $image_extension;
    $target_dir = "../image/";
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($image_tmp, $target_file)) {
        $conn->begin_transaction();
        
        try {
            $insert_query = "INSERT INTO product (ProductName, ProductPrice, ProductDesc, CategoryID, AdminID, ProductPicture) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sdsiss", $name, $price, $description, $category_id, $admin_id, $image_name);
            $stmt->execute();
            $product_id = $conn->insert_id;
            $stmt->close();

            $sizes = [
                'S' => $stock_S,
                'M' => $stock_M,
                'L' => $stock_L,
                'XL' => $stock_XL
            ];

            foreach ($sizes as $size => $quantity) {
                $insert_size = $conn->prepare("INSERT INTO product_size (ProductID, Size, Stock) VALUES (?, ?, ?)");
                $insert_size->bind_param("isi", $product_id, $size, $quantity);
                $insert_size->execute();
                $insert_size->close();
            }            

            $conn->commit();
            echo "<script>alert('Product added successfully!'); window.location.href='product_view.php';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Failed to add product: " . addslashes($e->getMessage()) . "'); window.location.href='product_view.php';</script>";
        }
    } else {
        echo "<script>alert('Failed to upload image.'); window.location.href='product_view.php';</script>";
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $productID = (int)$_POST['product_id'];
    $currentStatus = strtolower($_POST['current_status']);
    
    $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE product SET ProductStatus = ? WHERE productID = ?");
    $stmt->bind_param("si", $newStatus, $productID);
    
    if ($stmt->execute()) {
        header("Location: product_view.php");
        exit();
    } else {
        $error = "Status update failed: " . $conn->error;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link rel='stylesheet' href='product_view.css'>
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
            <h2>Product List</h2>

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
                    <th style="text-align: center;">Status</th>
                    <th class="action"></th>
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
                            <td style="text-align: center; line-height: 1.5;">
                                S: <?php echo $product['stock_S'] ; ?><br>
                                M: <?php echo $product['stock_M'] ; ?><br>
                                L: <?php echo $product['stock_L'] ; ?><br>
                                XL: <?php echo $product['stock_XL'] ; ?>
                            </td>
                            <td style="text-align: center;" class="<?php echo ($product['ProductStatus'] === 'active') ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $product['ProductStatus']; ?>
                            </td>
                            <td>
                                <button name="edit_product" onclick='editProduct(
                                    <?php echo json_encode($product["ProductID"]); ?>, 
                                    <?php echo json_encode($product["ProductName"]); ?>, 
                                    <?php echo json_encode($product["ProductPrice"]); ?>, 
                                    <?php echo json_encode($product["ProductDesc"]); ?>, 
                                    <?php echo json_encode($product["stock_S"] ?? 0); ?>, 
                                    <?php echo json_encode($product["stock_M"] ?? 0); ?>, 
                                    <?php echo json_encode($product["stock_L"] ?? 0); ?>, 
                                    <?php echo json_encode($product["stock_XL"] ?? 0); ?>, 
                                    <?php echo json_encode($product["CategoryID"]); ?>
                                )'>Edit</button>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $product['ProductStatus']; ?>">
                                    <button type="submit" class="btn-status <?php echo ($product['ProductStatus'] === 'active') ? 'btn-inactive' : 'btn-active'; ?>">
                                        <?php echo ($product['ProductStatus'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                                    </button>
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
                <div class="edit-form">
                    <div class="left">
                        <label>Image:</label>
                        <input type="file" name="image">
                        <label>Name:</label>
                        <input type="text" name="name" id="name" required>
                        <label>Price:</label>
                        <input type="number" min="1.00" step="1.00" name="price" id="price" required>
                        <label>Category:</label>
                        <select name="category_id" id="category_id" required>
                            <?php $category_result->data_seek(0); ?>
                            <?php while ($row = $category_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['CategoryID']; ?>">
                                    <?php echo $row['CategoryName']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="right">
                        <label for="stock_S">S:</label>
                        <input type="number" name="stock_S" id="stock_S" min="0" step="1.00" required>
                        <label for="stock_M">M:</label>
                        <input type="number" name="stock_M" id="stock_M" min="0" step="1.00" required>
                        <label for="stock_L">L:</label>
                        <input type="number" name="stock_L" id="stock_L" min="0" step="1.00" required>
                        <label for="stock_XL">XL:</label>
                        <input type="number" name="stock_XL" id="stock_XL" min="0" step="1.00" required>
                    </div>
                </div>
                <div class="description-section">
                        <label>Description:</label>
                        <textarea name="description" id="description" required></textarea>
                    </div>
                <div class="upd_button">
                    <button type="submit" name="update_product">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add New Product</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="product_id">
                <div class="add-form">
                    <div class="left">
                        <label>Image:</label>
                        <input type="file" name="image">
                        <label>Name:</label>
                        <input type="text" name="name" id="name" required>
                        <label>Price:</label>
                        <input type="number" min="1.00" step="1.00" name="price" id="price" required>
                        <label>Category:</label>
                        <select name="category_id" id="category_id" required>
                            <?php $category_result->data_seek(0); ?>
                            <?php while ($row = $category_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['CategoryID']; ?>">
                                    <?php echo $row['CategoryName']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="right">
                        <label for="stock_S">S:</label>
                        <input type="number" name="stock_S" id="stock_S" min="0" step="1.00" required>
                        <label for="stock_M">M:</label>
                        <input type="number" name="stock_M" id="stock_M" min="0" step="1.00" required>
                        <label for="stock_L">L:</label>
                        <input type="number" name="stock_L" id="stock_L" min="0" step="1.00" required>
                        <label for="stock_XL">XL:</label>
                        <input type="number" name="stock_XL" id="stock_XL" min="0" step="1.00" required>
                    </div>
                </div>
                <div class="description-section">
                        <label>Description:</label>
                        <textarea name="description" id="description" required></textarea>
                    </div>
                <div class="add_button">
                    <button type="submit" name="add_product">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editProduct(id, name, price, description, stock_S, stock_M, stock_L, stock_XL, category_id) {
            document.getElementById('product_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('price').value = price;
            document.getElementById('description').value = description;
            document.getElementById('stock_S').value = stock_S;
            document.getElementById('stock_M').value = stock_M;
            document.getElementById('stock_L').value = stock_L;
            document.getElementById('stock_XL').value = stock_XL;
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