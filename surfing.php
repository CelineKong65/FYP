<?php 
include 'config.php';
include 'header.php';

// Pagination logic
$productsPerPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Fetch total number of active products in category 2 
$stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE CategoryID = 2 AND ProductStatus = 'active'");
$stmt->execute();
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Fetch active products for the current page in category 2 
$stmt = $conn->prepare("SELECT * FROM product WHERE CategoryID = 2 AND ProductStatus = 'active' LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swimming Products</title>
    <link rel="stylesheet" href="product.css">
    <style>
        .categories ul {
            list-style: none;
            padding: 0;
            margin: 0; 
            transform: translateY(-55%);
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
       
        .categories {
            width: 200px;
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
                <li><a href="swimming.php">Swimming</a></li>
                <li><a href="surfing.php">Surfing and beach sports</a></li>
                <li><a href="snorkeling.php">Snorkeling / Scuba diving</a></li>
                <li><a href="kayaking.php">Kayaking</a></li>
            </ul>
        </div>

        <div class="products">
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
        </div>
    </div>

    <!-- Pagination Section -->
    <div class="pagination" style="text-align: center; margin: 20px 0;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="page">Next</a>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const pages = document.querySelectorAll(".pagination .page");

            pages.forEach(page => {
                page.addEventListener("click", function () {
                    // Remove 'active' class from all pages
                    pages.forEach(p => p.classList.remove("active"));

                    // Add 'active' class to the clicked page
                    this.classList.add("active");
                });
            });
        });
    </script>
</body>
</html>

<?php
include 'footer.php';
?>