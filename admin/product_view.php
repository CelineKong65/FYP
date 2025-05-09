<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

function validateProductName($name) {
    if (trim($name) === '') {
        return 'Product name is required';
    }
    if (strlen($name) > 100) {
        return 'Product name must be 100 characters or less';
    }
    if (!preg_match('/^[a-zA-Z0-9\s\-.,\'"()&]+$/', $name)) {
        return 'Only letters, numbers, spaces, and basic punctuation allowed';
    }
    return '';
}

$category_query = "SELECT * FROM category";
$category_result = $conn->query($category_query);

$brand_query = "SELECT * FROM brand";
$brand_result = $conn->query($brand_query);

$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination logic
$productsPerPage = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Base query for counting total products
$count_query = "
    SELECT COUNT(DISTINCT p.ProductID) 
    FROM product p
    LEFT JOIN product_size ps_S ON p.ProductID = ps_S.ProductID AND ps_S.Size = 'S'
    LEFT JOIN product_size ps_M ON p.ProductID = ps_M.ProductID AND ps_M.Size = 'M'
    LEFT JOIN product_size ps_L ON p.ProductID = ps_L.ProductID AND ps_L.Size = 'L'
    LEFT JOIN product_size ps_XL ON p.ProductID = ps_XL.ProductID AND ps_XL.Size = 'XL'
";

// Base query for fetching products
$product_query = "
    SELECT p.*, b.BrandName, c.CategoryName,
            COALESCE(ps_S.Stock, 0) as stock_S,
            COALESCE(ps_M.Stock, 0) as stock_M,
            COALESCE(ps_L.Stock, 0) as stock_L,
            COALESCE(ps_XL.Stock, 0) as stock_XL,
            CASE WHEN ps_S.ProductID IS NULL THEN 0 ELSE 1 END as has_S,
            CASE WHEN ps_M.ProductID IS NULL THEN 0 ELSE 1 END as has_M,
            CASE WHEN ps_L.ProductID IS NULL THEN 0 ELSE 1 END as has_L,
            CASE WHEN ps_XL.ProductID IS NULL THEN 0 ELSE 1 END as has_XL,
            EXISTS (SELECT 1 FROM product_size WHERE ProductID = p.ProductID AND Size IS NULL) as has_no_size,
            (SELECT Stock FROM product_size WHERE ProductID = p.ProductID AND Size IS NULL LIMIT 1) as no_size_stock
    FROM product p
    LEFT JOIN brand b ON p.BrandID = b.BrandID
    LEFT JOIN category c ON p.CategoryID = c.CategoryID
    LEFT JOIN product_size ps_S ON p.ProductID = ps_S.ProductID AND ps_S.Size = 'S'
    LEFT JOIN product_size ps_M ON p.ProductID = ps_M.ProductID AND ps_M.Size = 'M'
    LEFT JOIN product_size ps_L ON p.ProductID = ps_L.ProductID AND ps_L.Size = 'L'
    LEFT JOIN product_size ps_XL ON p.ProductID = ps_XL.ProductID AND ps_XL.Size = 'XL'
";

$where_clauses = [];
$params = [];
$types = '';

// Category filter
if (!empty($selected_category)) {
    $where_clauses[] = "p.CategoryID = ?";
    $params[] = $selected_category;
    $types .= 'i';
}

// Search filter
if (!empty($search_query)) {
    $where_clauses[] = "p.ProductName LIKE ?";
    $params[] = "%$search_query%";
    $types .= 's';
}

if (!empty($where_clauses)) {
    $product_query .= " WHERE " . implode(" AND ", $where_clauses);
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$product_query .= " ORDER BY CASE WHEN p.ProductStatus = 'Active' THEN 0 ELSE 1 END, p.ProductID";

// Then add your existing LIMIT clause
$product_query .= " LIMIT ? OFFSET ?";
$types .= 'ii'; // Add types for limit and offset
$params[] = $productsPerPage;
$params[] = $offset;

// Get total number of products for pagination
$stmt_count = $conn->prepare($count_query);
if (!empty($where_clauses)) {
    // Remove last 2 types (limit and offset) for count query
    $count_types = substr($types, 0, -2);
    // Remove last 2 params (limit and offset) for count query
    $count_params = array_slice($params, 0, -2);
    
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$totalProducts = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$totalPages = ceil($totalProducts / $productsPerPage);

// Prepare and execute the product query
$stmt = $conn->prepare($product_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
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

    $has_sizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] == 'on';
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $stock_S = isset($_POST['stock_S']) ? intval($_POST['stock_S']) : 0;
    $stock_M = isset($_POST['stock_M']) ? intval($_POST['stock_M']) : 0;
    $stock_L = isset($_POST['stock_L']) ? intval($_POST['stock_L']) : 0;
    $stock_XL = isset($_POST['stock_XL']) ? intval($_POST['stock_XL']) : 0;

    // Validate stock quantities
    if ($has_sizes) {
        if ($stock_S < 0 || $stock_M < 0 || $stock_L < 0 || $stock_XL < 0) {
            echo "<script>alert('Stock quantities must be 0 or greater.'); window.location.href='product_view.php';</script>";
            exit();
        }
    } else {
        if ($stock < 0) {
            echo "<script>alert('Stock quantity must be 0 or greater.'); window.location.href='product_view.php';</script>";
            exit();
        }
    }

    $name = trim($_POST['name']);
    $error = validateProductName($name);
    if ($error !== '') {
        echo "<script>alert('$error'); window.location.href='product_view.php';</script>";
        exit();
    }

    $existing_image = $_POST['existing_image'];

    if (!empty($image)) {
        // Handle new image upload
        $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
    
        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='product_view.php';</script>";
            exit();
        }
    
        // Create new image name based on updated product name
        $image_name = strtolower(str_replace(' ', '-', $name)) . "." . $image_extension;
        $target_dir = "../image/";
        $target_file = $target_dir . $image_name;
    
        // Delete the old image if it's different and exists
        if ($existing_image !== $image_name && file_exists($target_dir . $existing_image)) {
            unlink($target_dir . $existing_image);
        }
    
        // Move new uploaded image
        if (!move_uploaded_file($image_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='product_view.php';</script>";
            exit();
        }
    } else {
        // If no new image uploaded but name changed, rename old image
        $ext = pathinfo($existing_image, PATHINFO_EXTENSION);
        $new_image_name = strtolower(str_replace(' ', '-', $name)) . "." . $ext;
    
        if ($existing_image !== $new_image_name && file_exists("../image/" . $existing_image)) {
            rename("../image/" . $existing_image, "../image/" . $new_image_name);
        }
        $image_name = $new_image_name;
    }

    $conn->begin_transaction();

    try {
        $brand_id = intval($_POST['brand_id']);

        $update_query = "UPDATE product SET
                        ProductName = ?,
                        ProductPrice = ?,
                        ProductDesc = ?,
                        CategoryID = ?,
                        BrandID = ?,
                        ProductPicture = ?
                        WHERE ProductID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sdsissi", $name, $price, $description, $category_id, $brand_id, $image_name, $product_id);
        $stmt->execute();
        $stmt->close();

        $check_size_stmt = $conn->prepare("SELECT COUNT(*) FROM product_size WHERE ProductID = ? AND Size IS NOT NULL");
        $check_size_stmt->bind_param("i", $product_id);
        $check_size_stmt->execute();
        $check_size_stmt->bind_result($has_size_in_db);
        $check_size_stmt->fetch();
        $check_size_stmt->close();
        
        if ($has_sizes) {
            if ($has_size_in_db) {
                // Product already has sizes, just update each one
                $sizes = ['S' => $stock_S, 'M' => $stock_M, 'L' => $stock_L, 'XL' => $stock_XL];
                foreach ($sizes as $size => $quantity) {
                    $check_existing = $conn->prepare("SELECT COUNT(*) FROM product_size WHERE ProductID = ? AND Size = ?");
                    $check_existing->bind_param("is", $product_id, $size);
                    $check_existing->execute();
                    $check_existing->bind_result($exists);
                    $check_existing->fetch();
                    $check_existing->close();
        
                    if ($exists) {
                        $update_size = $conn->prepare("UPDATE product_size SET Stock = ? WHERE ProductID = ? AND Size = ?");
                        $update_size->bind_param("iis", $quantity, $product_id, $size);
                        $update_size->execute();
                        $update_size->close();
                    } else {
                        $insert_size = $conn->prepare("INSERT INTO product_size (ProductID, Size, Stock) VALUES (?, ?, ?)");
                        $insert_size->bind_param("isi", $product_id, $size, $quantity);
                        $insert_size->execute();
                        $insert_size->close();
                    }
                }
        
                // Also remove any NULL size entry if exists
                $delete_null_size = $conn->prepare("DELETE FROM product_size WHERE ProductID = ? AND Size IS NULL");
                $delete_null_size->bind_param("i", $product_id);
                $delete_null_size->execute();
                $delete_null_size->close();
        
            } else {
                // Previously had no size, delete NULL size row and insert new sizes
                $delete_null_size = $conn->prepare("DELETE FROM product_size WHERE ProductID = ? AND Size IS NULL");
                $delete_null_size->bind_param("i", $product_id);
                $delete_null_size->execute();
                $delete_null_size->close();
        
                $sizes = ['S' => $stock_S, 'M' => $stock_M, 'L' => $stock_L, 'XL' => $stock_XL];
                foreach ($sizes as $size => $quantity) {
                    $insert_size = $conn->prepare("INSERT INTO product_size (ProductID, Size, Stock) VALUES (?, ?, ?)");
                    $insert_size->bind_param("isi", $product_id, $size, $quantity);
                    $insert_size->execute();
                    $insert_size->close();
                }
            }
        
        } else {
            if ($has_size_in_db) {
                // Switching from size to no-size: delete all sized entries
                $delete_sizes = $conn->prepare("DELETE FROM product_size WHERE ProductID = ?");
                $delete_sizes->bind_param("i", $product_id);
                $delete_sizes->execute();
                $delete_sizes->close();
        
                // Insert no-size stock
                $insert_no_size = $conn->prepare("INSERT INTO product_size (ProductID, Size, Stock) VALUES (?, NULL, ?)");
                $insert_no_size->bind_param("ii", $product_id, $stock);
                $insert_no_size->execute();
                $insert_no_size->close();
            } else {
                // Product already has no size, just update the stock
                $update_no_size = $conn->prepare("UPDATE product_size SET Stock = ? WHERE ProductID = ? AND Size IS NULL");
                $update_no_size->bind_param("ii", $stock, $product_id);
                $update_no_size->execute();
                $update_no_size->close();
            }
        }

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

    $has_sizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] == 'on';
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $stock_S = isset($_POST['stock_S']) ? intval($_POST['stock_S']) : 0;
    $stock_M = isset($_POST['stock_M']) ? intval($_POST['stock_M']) : 0;
    $stock_L = isset($_POST['stock_L']) ? intval($_POST['stock_L']) : 0;
    $stock_XL = isset($_POST['stock_XL']) ? intval($_POST['stock_XL']) : 0;

    if ($has_sizes) {
        if ($stock_S < 0 || $stock_M < 0 || $stock_L < 0 || $stock_XL < 0) {
            echo "<script>alert('Stock quantities must be 0 or greater.'); window.location.href='product_view.php';</script>";
            exit();
        }
    } else {
        if ($stock < 0) {
            echo "<script>alert('Stock quantity must be 0 or greater.'); window.location.href='product_view.php';</script>";
            exit();
        }
    }

    $name = trim($_POST['name']);
    $error = validateProductName($name);
    if ($error !== '') {
        echo "<script>alert('$error'); window.location.href='product_view.php';</script>";
        exit();
    }

    $image_name = strtolower(str_replace(' ', '-', $name)) . "." . $image_extension;
    $target_dir = "../image/";
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($image_tmp, $target_file)) {
        $conn->begin_transaction();

        try {
            $brand_id = intval($_POST['brand_id']);

            $insert_query = "INSERT INTO product (ProductName, ProductPrice, ProductDesc, CategoryID, AdminID, ProductPicture, BrandID)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sdsissi", $name, $price, $description, $category_id, $admin_id, $image_name, $brand_id);
            $stmt->execute();
            $product_id = $conn->insert_id;
            $stmt->close();

            if ($has_sizes) {
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
            } else {
                $insert_size = $conn->prepare("INSERT INTO product_size (ProductID, Size, Stock) VALUES (?, NULL, ?)");
                $insert_size->bind_param("ii", $product_id, $stock);
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

    $newStatus = ($currentStatus == 'active') ? 'Inactive' : 'Active';

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_product_name'])) {
    $name = trim($_POST['name']);
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    $stmt = $conn->prepare("SELECT ProductID FROM product WHERE ProductName = ? AND ProductID != ?");
    $stmt->bind_param("si", $name, $product_id);
    $stmt->execute();
    $stmt->store_result();
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => $stmt->num_rows > 0]);
    exit();
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
                        <th style="text-align: center;">ID</th>
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
                                <td style="text-align: center;"><?php echo $product['ProductID']; ?></td>
                                <td style="display: grid; place-items: center;">
                                    <?php
                                    $imageName = $product['ProductPicture'] ?? '';
                                    $imagePath = "../image/" . $imageName;

                                    // Check if the file exists and the filename is not empty
                                    if (!empty($imageName) && file_exists($imagePath)) {
                                        echo "<img src='$imagePath' alt='Product Image' width='150'>";
                                    } else {
                                        echo "<img src='../image/placeholder.jpg' alt='Image not available' width='150'>";
                                    }
                                    ?>
                                </td>

                                <td style="line-height: 1.75;">
                                    <strong><?php echo $product['ProductName']; ?><br></strong>
                                    Brand: <?php echo $product['BrandName']; ?><br>
                                    Category: <?php echo $product['CategoryName']; ?>
                                </td>
                                <td style="text-align: center;"><?php echo number_format($product['ProductPrice'], 2); ?></td>
                                <td><?php echo $product['ProductDesc']; ?></td>
                                <td style="text-align: center; line-height: 1.5;">
                                    <?php
                                    if ($product['has_no_size']) {
                                        echo $product['no_size_stock'];
                                    } else {
                                        $sizes = [];
                                        if ($product['has_S']) $sizes[] = "S: " . $product['stock_S'];
                                        if ($product['has_M']) $sizes[] = "M: " . $product['stock_M'];
                                        if ($product['has_L']) $sizes[] = "L: " . $product['stock_L'];
                                        if ($product['has_XL']) $sizes[] = "XL: " . $product['stock_XL'];

                                        echo implode("<br>", $sizes);
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;" class="<?php echo ($product['ProductStatus'] === 'Active') ? 'status-active' : 'status-inactive'; ?>">
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
                                        <?php echo json_encode($product["CategoryID"]); ?>,
                                        <?php echo json_encode($product["has_no_size"]); ?>,
                                        <?php echo json_encode($product["no_size_stock"] ?? 0); ?>,
                                        <?php echo json_encode($product["ProductPicture"]); ?>,
                                        <?php echo json_encode($product["BrandID"]); ?>
                                    )'>Edit</button>
                                    <form method="post" action="" style="display: inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $product['ProductStatus']; ?>">
                                        <button type="submit" class="btn-status <?php echo ($product['ProductStatus'] === 'Active') ? 'btn-inactive' : 'btn-active'; ?>">
                                            <?php echo ($product['ProductStatus'] === 'Active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No products found for this category.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($selected_category) ? '&category=' . $selected_category : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($selected_category) ? '&category=' . $selected_category : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($selected_category) ? '&category=' . $selected_category : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Product</h3>
            <form id="edit_product_form" method="POST" action="" enctype="multipart/form-data" class="edit">
                <input type="hidden" name="product_id" id="product_id">
                <input type="hidden" name="existing_image" id="existing_image">
                <div class="edit-form">
                    <div class="left">
                        <label>Image:<span> (.jpg,.jpeg or .png only)</span></label>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png">
                        <label>Name:<span class="required">*</span></label>
                        <input type="text" name="name" id="name" required oninput="validateProductNameInRealTime(this.value, true)">
                        <div id="edit-name-error" class="error-message" style="display: none; color: red;"></div>
                        <label>Price:<span class="required">*</span></label>
                        <input type="number" min="1.00" step="1.00" name="price" id="price" required>
                        <label>Category:<span class="required">*</span></label>
                        <select name="category_id" id="category_id" required>
                            <?php $category_result->data_seek(0); ?>
                            <?php while ($row = $category_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['CategoryID']; ?>">
                                    <?php echo $row['CategoryName']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label>Brand:<span class="required">*</span></label>
                        <select name="brand_id" id="brand_id" required>
                            <?php $brand_result->data_seek(0); ?>
                            <?php while ($row = $brand_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['BrandID']; ?>">
                                    <?php echo $row['BrandName']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label>Description:<span class="required">*</span></label>
                        <textarea name="description" id="description" required></textarea>
                    </div>
                    <div class="right">
                        <div class="size-toggle">
                            <input type="checkbox" id="has_sizes" name="has_sizes" onchange="toggleSizeFields()">
                            <label for="has_sizes">This product has sizes</label>
                        </div>
                        <div id="size_fields">
                            <label for="stock_S">S:<span class="required">*</span></label>
                            <input type="number" name="stock_S" id="stock_S" min="0" step="1.00" value="0">
                            <label for="stock_M">M:<span class="required">*</span></label>
                            <input type="number" name="stock_M" id="stock_M" min="0" step="1.00" value="0">
                            <label for="stock_L">L:<span class="required">*</span></label>
                            <input type="number" name="stock_L" id="stock_L" min="0" step="1.00" value="0">
                            <label for="stock_XL">XL:<span class="required">*</span></label>
                            <input type="number" name="stock_XL" id="stock_XL" min="0" step="1.00" value="0">
                        </div>
                        <div id="no_size_field">
                            <label for="stock">Stock:<span class="required">*</span></label>
                            <input type="number" name="stock" id="stock" min="0" step="1.00" value="0">
                        </div>
                    </div>
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
        <form id="add_product_form" method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="product_id">
            <div class="add-form">
                <div class="left">
                    <label>Image:<span class="required">*</span><span> (.jpg,.jpeg or .png only)</span></label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png" required>
                    <label>Name:<span class="required">*</span></label>
                    <input type="text" name="name" id="add_name" required oninput="validateProductNameInRealTime(this.value, false)">
                    <div id="add-name-error" class="error-message" style="display: none; color: red;"></div>
                    <label>Price:<span class="required">*</span></label>
                    <input type="number" min="1.00" step="1.00" name="price" id="add_price" required>
                    <label>Category:<span class="required">*</span></label>
                    <select name="category_id" id="add_category_id" required>
                        <option value="" disabled selected>--- Select a category ---</option>
                        <?php $category_result->data_seek(0); ?>
                        <?php while ($row = $category_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['CategoryID']; ?>">
                                <?php echo $row['CategoryName']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label>Brand:<span class="required">*</span></label>
                    <select name="brand_id" id="brand_id" required>
                        <option value="" disabled selected>--- Select a brand ---</option>
                        <?php $brand_result->data_seek(0); ?>
                        <?php while ($row = $brand_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['BrandID']; ?>">
                                <?php echo $row['BrandName']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label>Description:<span class="required">*</span></label>
                    <textarea name="description" id="add_description" required></textarea>
                </div>
                <div class="right">
                    <div class="size-toggle">
                        <input type="checkbox" id="add_has_sizes" name="has_sizes" onchange="toggleAddSizeFields()">
                        <label for="add_has_sizes">This product has sizes</label>
                    </div>
                    <div id="add_size_fields" style="display:none;">
                        <label for="add_stock_S">S:<span class="required">*</span></label>
                        <input type="number" name="stock_S" id="add_stock_S" min="0" step="1.00" value="0">
                        <label for="add_stock_M">M:<span class="required">*</span></label>
                        <input type="number" name="stock_M" id="add_stock_M" min="0" step="1.00" value="0">
                        <label for="add_stock_L">L:<span class="required">*</span></label>
                        <input type="number" name="stock_L" id="add_stock_L" min="0" step="1.00" value="0">
                        <label for="add_stock_XL">XL:<span class="required">*</span></label>
                        <input type="number" name="stock_XL" id="add_stock_XL" min="0" step="1.00" value="0">
                    </div>
                    <div id="add_no_size_field">
                        <label for="add_stock">Stock:<span class="required">*</span></label>
                        <input type="number" name="stock" id="add_stock" min="0" step="1.00" value="0" required>
                    </div>
                </div>
            </div>
            <div class="add_button">
                <button type="submit" name="add_product">Add</button>
            </div>
        </form>
    </div>
</div>

<script>
        let isProductNameValid = false;

        function validateProductNameInRealTime(value, isEdit = false) {
            const prefix = isEdit ? 'edit-' : 'add-';
            const errorElement = document.getElementById(`${prefix}name-error`);
            const inputField = document.getElementById(isEdit ? 'name' : 'add_name');
            const submitButton = document.getElementById(isEdit ? 'update_product' : 'add_product');
            
            errorElement.textContent = '';
            errorElement.style.display = 'none';
            inputField.classList.remove('error-field', 'valid-field');
            
            if (value.trim() === '') {
                errorElement.textContent = 'Product name is required';
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                isProductNameValid = false;
                return;
            }
            
            if (value.length > 100) {
                errorElement.textContent = 'Product name must be 100 characters or less';
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                isProductNameValid = false;
                return;
            }
            
            if (!/^[a-zA-Z0-9\s\-.,'"()&]+$/.test(value)) {
                errorElement.textContent = 'Only letters, numbers, spaces, and basic punctuation allowed';
                errorElement.style.display = 'block';
                inputField.classList.add('error-field');
                isProductNameValid = false;
                return;
            }
            
            // Check for duplicate name via AJAX
            checkProductNameAvailability(value, isEdit ? document.getElementById('product_id').value : 0, isEdit)
                .then(isAvailable => {
                    isProductNameValid = isAvailable;
                    if (isAvailable) {
                        inputField.classList.add('valid-field');
                    } else {
                        errorElement.textContent = 'Product name already exists';
                        errorElement.style.display = 'block';
                        inputField.classList.add('error-field');
                    }
                    updateProductSubmitButton(isEdit);
                });
        }

        function checkProductNameAvailability(name, productId = 0, isEdit = false) {
            return new Promise((resolve) => {
                const formData = new FormData();
                formData.append('check_product_name', 'true');
                formData.append('name', name);
                formData.append('product_id', productId);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    resolve(!data.exists);
                })
                .catch(error => {
                    console.error('Error checking product name:', error);
                    resolve(false);
                });
            });
        }

        function updateProductSubmitButton(isEdit) {
            const submitButton = isEdit ? document.querySelector('button[name="update_product"]') : 
                                        document.querySelector('button[name="add_product"]');
            submitButton.disabled = !isProductNameValid;
        }

        // For add form
        document.getElementById('add_product_form').addEventListener('submit', function(e) {
            if (!isProductNameValid) {
                e.preventDefault();
                alert('Please fix the product name errors before submitting');
            }
        });

        // For edit form
        document.getElementById('edit_product_form').addEventListener('submit', function(e) {
            if (!isProductNameValid) {
                e.preventDefault();
                alert('Please fix the product name errors before submitting');
            }
        });

        function toggleSizeFields() {
            var hasSizes = document.getElementById('has_sizes').checked;
            document.getElementById('size_fields').style.display = hasSizes ? 'block' : 'none';
            document.getElementById('no_size_field').style.display = hasSizes ? 'none' : 'block';
        }

        function editProduct(id, name, price, description, stock_S, stock_M, stock_L, stock_XL, category_id, hasNoSize, noSizeStock, productPicture, brand_id) {
            clearAllErrors();
            
            document.getElementById('product_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('price').value = price;
            document.getElementById('description').value = description;
            document.getElementById('category_id').value = category_id;
            document.getElementById('brand_id').value = brand_id;
            document.getElementById('existing_image').value = productPicture;
            
            if (hasNoSize) {
                document.getElementById('has_sizes').checked = false;
                document.getElementById('stock').value = noSizeStock;
            } else {
                document.getElementById('has_sizes').checked = true;
                document.getElementById('stock_S').value = stock_S;
                document.getElementById('stock_M').value = stock_M;
                document.getElementById('stock_L').value = stock_L;
                document.getElementById('stock_XL').value = stock_XL;
            }
            
            toggleSizeFields();
            document.getElementById('editModal').style.display = "block";

            var categorySelect = document.getElementById('category_id');
            for (var i = 0; i < categorySelect.options.length; i++) {
                if (categorySelect.options[i].value == category_id) {
                    categorySelect.options[i].selected = true;
                    break;
                }
            }
            
            // Reset validation state
            isProductNameValid = true;
        }

        function closeModal() {
            clearAllErrors();
            document.getElementById('editModal').style.display = 'none';
            // Reset validation state
            isProductNameValid = false;
        }

        function toggleAddSizeFields() {
            var hasSizes = document.getElementById('add_has_sizes').checked;
            document.getElementById('add_size_fields').style.display = hasSizes ? 'block' : 'none';
            document.getElementById('add_no_size_field').style.display = hasSizes ? 'none' : 'block';

            // Toggle required attributes
            document.getElementById('add_stock').required = !hasSizes;
            document.getElementById('add_stock_S').required = hasSizes;
            document.getElementById('add_stock_M').required = hasSizes;
            document.getElementById('add_stock_L').required = hasSizes;
            document.getElementById('add_stock_XL').required = hasSizes;
        }

        function openAddModal() {
            // Clear any existing errors
            clearAllErrors();
            
            // Reset the form when opening
            document.getElementById('add_has_sizes').checked = false;
            toggleAddSizeFields();
            document.getElementById('addModal').style.display = 'block';
            
            // Reset validation state
            isProductNameValid = false;
        }

        function closeAddModal() {
            clearAllErrors();
            document.getElementById('addModal').style.display = 'none';
            // Reset validation state
            isProductNameValid = false;
        }

        function clearAllErrors() {
            // Clear edit modal errors
            const editError = document.getElementById('edit-name-error');
            if (editError) {
                editError.textContent = '';
                editError.style.display = 'none';
            }
            const editInput = document.getElementById('name');
            if (editInput) {
                editInput.classList.remove('error-field', 'valid-field');
            }
            
            // Clear add modal errors
            const addError = document.getElementById('add-name-error');
            if (addError) {
                addError.textContent = '';
                addError.style.display = 'none';
            }
            const addInput = document.getElementById('add_name');
            if (addInput) {
                addInput.classList.remove('error-field', 'valid-field');
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById("editModal")) {
                closeModal();
            }
            if (event.target == document.getElementById("addModal")) {
                closeAddModal();
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const searchForm = document.querySelector('.search');
            
            if (searchInput && searchForm) {
                searchInput.addEventListener('input', function() {
                    if (this.value.trim() === '') {
                        searchForm.submit();
                    }
                });
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            const pages = document.querySelectorAll(".pagination .page");

            pages.forEach(page => {
                page.addEventListener("click", function () {
                    pages.forEach(p => p.classList.remove("active"));
                    this.classList.add("active");
                });
            });
        });        
    </script>
</body>
</html>