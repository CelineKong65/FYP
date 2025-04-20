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
    <link rel="stylesheet" href="category.css">
    <style>
      body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

.container-shop {
    display: flex;
    max-width: 1200px;
    margin: 0 auto;
    padding: 150px 20px 20px;
    gap: 20px;
    align-items: stretch; /* 强制子元素高度一致 */
    min-height: 80vh; /* 设定一个最小高度（可调整） */
}

.categories {
    flex: 1;
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
}

.categories h2 {
    margin-top: 10px;
    font-size: 24px;
    margin-bottom: 5px;
    padding: 5px 25px;
}

.categories ul {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto; /* 超出时滚动 */
    flex-grow: 1; /* 占据剩余空间 */
}

.categories ul li {
    margin-bottom: 10px;
}

.categories ul li a {
    text-decoration: none;
    color: #333;
    font-size: 18px;
    display: block;
    padding: 5px 25px;
}

.categories ul li a:hover {
    color: #007BFF;
}

/* Products Section */
.products-wrapper {
    flex: 3;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.products {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    align-content: start;
    flex: 1;
}

.product {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 40px;
    text-align: center;
    height: auto;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
}

.product h3 {
    margin-top: 10px; 
    margin-bottom: 5px; 
    font-size: 18px; 
    font-weight: bold; 
}

.product p {
    color: red; 
    font-size: 16px; 
    font-weight: bold; 
    margin: 0; 
}

.product-image {
    position: relative;
    overflow: hidden;
    border-radius: 5px;
}

.product-image img {
    width: 100%;
    height: auto;
    transition: transform 0.3s ease;
}

/* View Details Button */
.view-details {
    position: absolute;
    bottom: -50px;
    left: 0;
    right: 0;
    background-color: rgba(0, 123, 255, 0.8);
    color: white;
    text-align: center;
    padding: 10px;
    font-size: 16px;
    font-weight: bold;
    transition: bottom 0.3s ease;
    cursor: pointer;
}

/* Hover Effect */
.product:hover .view-details {
    bottom: 0;
}

.product:hover img {
    transform: scale(1.1);
}

/* Pagination Section */
.pagination {
    text-align: center;
    margin-top: 30px;
    padding: 20px;
}

.pagination .page {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 5px;
    text-decoration: none;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.pagination .page:hover {
    background-color: #007BFF;
    color: #fff;
    border-color: #007BFF;
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
    <div class="products-wrapper">
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
