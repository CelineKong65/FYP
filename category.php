<?php 
include 'config.php';
include 'header.php';

// Get category ID from the URL
$categoryID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If category ID is invalid (0 or less), show error and stop
if ($categoryID <= 0) {
    echo "<p style='text-align:center;color:red;'>Invalid category selected.</p>";
    include 'footer.php';
    exit;
}

// Pagination logic
// Set number of products per page for pagination
$productsPerPage = 6;
// Get current page number from URL, default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// Calculate the offset for SQL LIMIT clause
$offset = ($page - 1) * $productsPerPage;

// Get total products in selected category with active brands
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    WHERE p.CategoryID = :categoryID 
    AND p.ProductStatus = 'Active'
    AND b.BrandStatus = 'Active'
");
$stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
$stmt->execute();
$totalProducts = $stmt->fetchColumn();  // Total product count
$totalPages = ceil($totalProducts / $productsPerPage);  // Calculate total pages

// Get category name
// Default category name if not found
$categoryName = "Products";
$catStmt = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = :categoryID");
$catStmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
$catStmt->execute();
if ($row = $catStmt->fetch()) {
    $categoryName = $row['CategoryName'];
}

// Fetch products in the selected category with active brands
$stmt = $conn->prepare("
    SELECT p.* 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    WHERE p.CategoryID = :categoryID 
    AND p.ProductStatus = 'Active'
    AND b.BrandStatus = 'Active'
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();  // Get all matching products
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($categoryName) ?> Products</title>
    <link rel="stylesheet" href="category.css">
</head>
<body>

<!-- Main container -->
<div class="container-shop">
    <!-- Sidebar for categories and brands -->
    <div class="categories">
        <!-- Category Toggle -->
        <div class="category-header" onclick="toggleList('category-list', 'category-arrow')">
            <h2>Categories</h2>
            <img src="image/arrow-down-sign-to-navigate.png" alt="arrow" class="arrow-icon" id="category-arrow">
        </div>
        <ul id="category-list" class="hidden"> 
            <?php
                // Query all active categories
                $catQuery = $conn->prepare("SELECT CategoryID, CategoryName FROM category WHERE CategoryStatus = 'active'");
                $catQuery->execute();
                $categories = $catQuery->fetchAll();
                // Display list of categories or message if none found
                if ($categories) {
                    foreach ($categories as $cat) {
                        echo "<li><a href='category.php?id=" . htmlspecialchars($cat['CategoryID']) . "'>" . htmlspecialchars($cat['CategoryName']) . "</a></li>";
                    }
                } else {
                    echo "<li>No active categories found.</li>";
                }
            ?>
        </ul>

        <!-- Brand Toggle -->
        <div class="brand-header" onclick="toggleList('brand-list', 'brand-arrow')">
            <h2>Brands</h2>
            <img src="image/arrow-down-sign-to-navigate.png" alt="arrow" class="arrow-icon" id="brand-arrow">
        </div>
        <ul id="brand-list" class="hidden"> 
            <?php
                // Query all active brands
                $brandQuery = $conn->prepare("SELECT BrandID, BrandName FROM brand WHERE BrandStatus = 'Active'");
                $brandQuery->execute();
                $brands = $brandQuery->fetchAll();
                // Display list of brands or message if none found
                if ($brands) {
                    foreach ($brands as $brand) {
                        echo "<li><a href='brand.php?id=" . htmlspecialchars($brand['BrandID']) . "'>" . htmlspecialchars($brand['BrandName']) . "</a></li>";
                    }
                } else {
                    echo "<li>No active brands found.</li>";
                }
            ?>
        </ul>
    </div>
    <!-- Main content area for product listings -->
    <div class="products-wrapper">
        <div class="products">
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product">
                        <div class="product-image">
                            <?php
                                // Determine image source to default
                                $imageSrc = $product['ProductPicture'] ? 'image/' . $product['ProductPicture'] : 'image/default-image.png';
                            ?>
                            <!-- Product image and details button -->
                            <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                            <a href="product_details.php?id=<?= $product['ProductID'] ?>"><button class="view-details">View Details</button></a>
                        </div>
                        <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                        <p>RM <?= number_format($product['ProductPrice'], 2) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Message when no products found -->
                <p style="text-align:center;">No products available in this category.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="pagination" style="text-align: center; margin: 20px 0;">
    <?php if ($page > 1): ?>
        <a href="?id=<?= $categoryID ?>&page=<?= $page - 1 ?>" class="page">Previous</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?id=<?= $categoryID ?>&page=<?= $i ?>" class="page <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?id=<?= $categoryID ?>&page=<?= $page + 1 ?>" class="page">Next</a>
    <?php endif; ?>
</div>

<script>
    function toggleList(listId, arrowId) {
        const list = document.getElementById(listId);   // Target list (UL)
        const arrow = document.getElementById(arrowId); // Target list (UL)
        list.classList.toggle("hidden");                // Toggle hidden class
        arrow.classList.toggle("rotate");                // Toggle rotate class
    }
</script>

</body>
</html>

<?php include 'footer.php'; ?>
