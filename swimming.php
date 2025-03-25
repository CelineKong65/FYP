<?php 
include 'config.php';
include 'header.php';

// Pagination logic
$productsPerPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Fetch total number of products in category 1
$stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE CategoryID = 1");
$stmt->execute();
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Fetch products for the current page in category 1
$stmt = $conn->prepare("SELECT * FROM product WHERE CategoryID = 1 LIMIT :limit OFFSET :offset");
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
    <title>Product Page</title>
    <link rel="stylesheet" href="product.css">
    <style>
        .categories ul {
            list-style: none;
            padding: 0;
            margin: 0; 
            transform: translateY(-55%);
        }
        .color-options {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #ccc;
            cursor: pointer;
        }
        .color-circle.active {
            border-color: #000;
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
                        // Fetch the first color image for the product
                        $stmt = $conn->prepare("SELECT Picture FROM product_color WHERE ProductID = :productId LIMIT 1");
                        $stmt->execute(['productId' => $product['ProductID']]);
                        $image = $stmt->fetch();
                        $imageSrc = $image ? 'image/' . $image['Picture'] : 'image/default-image.png';
                        ?>
                        <img id="product-image-<?= $product['ProductID'] ?>" src="<?= $imageSrc ?>" alt="<?= $product['ProductName'] ?>">
                        <a href="product_details.php?id=<?= $product['ProductID'] ?>"><button class="view-details">View Details</button></a>
                    </div>
                    <div class="color-options">
                        <?php
                        // Fetch all color options for the product
                        $stmt = $conn->prepare("SELECT Color, Picture FROM product_color WHERE ProductID = :productId");
                        $stmt->execute(['productId' => $product['ProductID']]);
                        $colors = $stmt->fetchAll();
                        foreach ($colors as $color): ?>
                            <div class="color-circle" 
                                style="background-color: <?= htmlspecialchars($color['Color']) ?>;" 
                                data-image="image/<?= htmlspecialchars($color['Picture']) ?>" 
                                onclick="changeImage('product-image-<?= $product['ProductID'] ?>', 'image/<?= htmlspecialchars($color['Picture']) ?>')">
                            </div>              
                        <?php endforeach; ?>
                    </div>
                    <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                    <p>RM <?= number_format($product['ProductPrice'], 2) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pagination Section -->
    <div class="pagination">
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
        // JavaScript function to change the product image
        function changeImage(imageId, newImageSrc) {
            document.getElementById(imageId).src = newImageSrc;
        }

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