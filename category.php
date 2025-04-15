<?php 
include 'config.php';
include 'header.php';

// Get category ID from the URL
$categoryID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid category is selected, redirect or show a message
if ($categoryID <= 0) {
    echo "<p style='text-align:center;color:red;'>Invalid category selected.</p>";
    include 'footer.php';
    exit;
}

// Pagination logic
$productsPerPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Get total products in selected category
$stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE CategoryID = :categoryID AND ProductStatus = 'active'");
$stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
$stmt->execute();
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Get category name (optional)
$categoryName = "Products";
$catStmt = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = :categoryID");
$catStmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
$catStmt->execute();
if ($row = $catStmt->fetch()) {
    $categoryName = $row['CategoryName'];
}

// Fetch products in the selected category
$stmt = $conn->prepare("SELECT * FROM product WHERE CategoryID = :categoryID AND ProductStatus = 'active' LIMIT :limit OFFSET :offset");
$stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($categoryName) ?> Products</title>
    <link rel="stylesheet" href="product.css">
    <style>
        .categories {
            width: 200px;
            padding: 10px 20px;
        }

        .categories h2 {
            margin-bottom: 10px;
        }

        .categories ul {
            margin-top: 0;
            padding-left: 0;
            list-style: none;
        }

        .categories li {
            margin-bottom: 8px;
        }

        .pagination .page {
            padding: 8px 12px;
            margin: 2px;
            text-decoration: none;
            border: 1px solid #ccc;
            color: #333;
            border-radius: 4px;
        }
        .pagination .page.active {
            background-color: #333;
            color: #fff;
            border-color: #333;
        }
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        .view-details {
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .view-details:hover {
            background-color: #0055aa;
        }
    </style>
</head>
<body>

<div class="container-shop">
    <div class="categories">
        <h2>Categories</h2>
        <ul>
            <?php
                $catQuery = $conn->prepare("SELECT CategoryID, CategoryName FROM category WHERE CategoryStatus = 'active'");
                $catQuery->execute();
                $categories = $catQuery->fetchAll();

                if ($categories) {
                    foreach ($categories as $cat) {
                        echo "<li><a href='category.php?id=" . htmlspecialchars($cat['CategoryID']) . "'>" . htmlspecialchars($cat['CategoryName']) . "</a></li>";
                    }
                } else {
                    echo "<li>No active categories found.</li>";
                }
            ?>
        </ul>
    </div>

    <div class="products">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <div class="product-image">
                        <?php
                        $imageSrc = $product['ProductPicture'] ? 'image/' . $product['ProductPicture'] : 'image/default-image.png';
                        ?>
                        <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                        <a href="product_details.php?id=<?= $product['ProductID'] ?>"><button class="view-details">View Details</button></a>
                    </div>
                    <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                    <p>RM <?= number_format($product['ProductPrice'], 2) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">No products available in this category.</p>
        <?php endif; ?>
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

</body>
</html>

<?php include 'footer.php'; ?>
