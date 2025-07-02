<?php 
include 'config.php';
include 'header.php';

// Get brand ID from URL, default to 0 if not present
$brandID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If brand ID is invalid, display an error message and exit
if ($brandID <= 0) {
    echo "<p style='text-align:center;color:red;'>Invalid brand selected.</p>";
    include 'footer.php';
    exit;
}

// Pagination logic
// Set how many products to display per page
$productsPerPage = 6;
// Get current page number from URL, default to page 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// Calculate the starting product for SQL OFFSET
$offset = ($page - 1) * $productsPerPage;

// Get total products for selected brand
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    WHERE p.BrandID = :brandID 
    AND p.ProductStatus = 'Active'
    AND b.BrandStatus = 'Active'
");
$stmt->bindParam(':brandID', $brandID, PDO::PARAM_INT);
$stmt->execute();
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Get brand name
$brandName = "Products";
$brandStmt = $conn->prepare("SELECT BrandName FROM brand WHERE BrandID = :brandID");
$brandStmt->bindParam(':brandID', $brandID, PDO::PARAM_INT);
$brandStmt->execute();
if ($row = $brandStmt->fetch()) {
    $brandName = $row['BrandName'];
}

// Fetch products for current brand and page
$stmt = $conn->prepare("
    SELECT p.* 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    WHERE p.BrandID = :brandID 
    AND p.ProductStatus = 'Active'
    AND b.BrandStatus = 'Active'
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':brandID', $brandID, PDO::PARAM_INT);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(); // Fetch all matching products
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($brandName) ?> Products</title>
    <link rel="stylesheet" href="brand.css">
</head>
<body>
<!-- Main container -->
<div class="container-shop">
    <!-- Sidebar section for categories and brands -->
    <div class="brands">
        <!-- Category Toggle -->
        <div class="category-header" onclick="toggleList('category-list', 'category-arrow')">
            <h2>Categories</h2>
            <img src="image/arrow-down-sign-to-navigate.png" alt="arrow" class="arrow-icon" id="category-arrow">
        </div>
        <ul id="category-list" class="hidden"> 
            <?php
                // Fetch active categories 
                $catQuery = $conn->prepare("SELECT CategoryID, CategoryName FROM category WHERE CategoryStatus = 'active'");
                $catQuery->execute();
                $categories = $catQuery->fetchAll();
                // Display each category as a list item
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
                // Fetch active brands 
                $brandQuery = $conn->prepare("SELECT BrandID, BrandName FROM brand WHERE BrandStatus = 'Active'");
                $brandQuery->execute();
                $brands = $brandQuery->fetchAll();
                // Display each brand as a list item
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
    <!-- Product Display Area -->
    <div class="products-wrapper">
        <div class="products">
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product">
                        <div class="product-image">
                            <?php
                                // Check if product has an image, fallback to default if not
                                $imageSrc = $product['ProductPicture'] ? 'image/' . $product['ProductPicture'] : 'image/default-image.png';
                            ?>
                            <!-- Display product image -->
                            <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                            <a href="product_details.php?id=<?= $product['ProductID'] ?>"><button class="view-details">View Details</button></a>
                        </div>
                        <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                        <p>RM <?= number_format($product['ProductPrice'], 2) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Message when no products are available -->
                <p style="text-align:center; grid-column: 1 / -1;">No products available for this brand.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="pagination" style="text-align: center; margin: 20px 0;">
    <?php if ($page > 1): ?>
        <!-- Link to previous page -->
        <a href="?id=<?= $brandID ?>&page=<?= $page - 1 ?>" class="page">Previous</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?id=<?= $brandID ?>&page=<?= $i ?>" class="page <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <!-- Link to next page -->
        <a href="?id=<?= $brandID ?>&page=<?= $page + 1 ?>" class="page">Next</a>
    <?php endif; ?>
</div>

<script>
    function toggleList(listId, arrowId) {
        const list = document.getElementById(listId);   // Get list element
        const arrow = document.getElementById(arrowId); // Get arrow icon
        list.classList.toggle("hidden");                // Toggle visibility
        arrow.classList.toggle("rotate");               // Toggle arrow rotation class
    }
</script>

</body>
</html>

<?php include 'footer.php'; ?>