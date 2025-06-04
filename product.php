<?php
include 'config.php';
include 'header.php';

// Pagination logic
$productsPerPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Fetch total number of active products with active brands
$stmt = $conn->query("
    SELECT COUNT(*) 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    WHERE p.ProductStatus = 'active' 
    AND b.BrandStatus = 'Active'
");
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Fetch products with active brands for the current page
$stmt = $conn->prepare("
    SELECT p.* 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    WHERE p.ProductStatus = 'active'
    AND b.BrandStatus = 'Active'
    LIMIT :limit OFFSET :offset
");
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
            align-items: stretch; /* Force equal height for children */
            min-height: 80vh; /* Set a minimum height */
        }

        /* Categories Section */
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
            transform: translateX(0%)
        }

        .categories ul {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto; /* Scroll if content overflows */
            flex-grow: 1; /* Take up remaining space */
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
            <h2>Brands</h2>
            <ul>
                <?php
                    $brandQuery = $conn->prepare("SELECT BrandID, BrandName FROM brand WHERE BrandStatus = 'Active'");
                    $brandQuery->execute();
                    $brands = $brandQuery->fetchAll();

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

        <div class="products-wrapper">
            <div class="products">
                <?php foreach ($products as $product): ?>
                    <div class="product">
                        <div class="product-image">
                            <?php
                            $imageSrc = $product['ProductPicture'] ? 'image/' . $product['ProductPicture'] : 'image/default-image.png';
                            ?>
                            <img src="<?= $imageSrc ?>" alt="<?= $product['ProductName'] ?>">
                            <a href="product_details.php?id=<?= $product['ProductID'] ?>"><button class="view-details">View Details</button></a>
                        </div>
                        <h3><?= $product['ProductName'] ?></h3>
                        <p>RM <?= number_format($product['ProductPrice'], 2) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Pagination Section -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
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