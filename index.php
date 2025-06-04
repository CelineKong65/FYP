<?php 
include 'header.php';

// Database fetches before HTML
try {
    // Fetch active categories
    $categories_stmt = $conn->prepare("SELECT * FROM category WHERE CategoryStatus = 'Active'"); 
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch active brands
    $brands_stmt = $conn->prepare("SELECT * FROM brand WHERE BrandStatus = 'Active'");
    $brands_stmt->execute();
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active vouchers
    $voucher_stmt = $conn->prepare("SELECT * FROM voucher WHERE VorcherStatus = 'Active'");
    $voucher_stmt->execute();
    $vouchers = $voucher_stmt->fetchAll(PDO::FETCH_ASSOC);

    
    // Fetch top selling products with status checks
    $top_products_stmt = $conn->prepare("
        SELECT 
            p.ProductID,
            p.ProductName,
            p.ProductPicture,
            p.ProductStatus,
            p.CategoryID,
            p.BrandID,
            c.CategoryStatus,
            b.BrandStatus,
            SUM(od.Quantity) AS total_quantity_sold,
            SUM(od.Quantity * od.ProductPrice) AS total_sales_value
        FROM orderdetails od
        JOIN orderpayment op ON od.OrderID = op.OrderID
        JOIN product p ON od.ProductName = p.ProductName
        JOIN category c ON p.CategoryID = c.CategoryID
        JOIN brand b ON p.BrandID = b.BrandID
        GROUP BY p.ProductID, p.ProductName
        ORDER BY total_quantity_sold DESC
        LIMIT 5
    ");
    $top_products_stmt->execute();
    $top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log error and set empty arrays to prevent page break
    error_log("Database error: " . $e->getMessage());
    $categories = [];
    $brands = [];
    $top_products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watersport Equipment Shop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .advertisement-box {
            background: rgba(0, 0, 0, 0.34);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            color: white;
            transform: translate(50%, 5%);
        }
        .hero {
            background: url('image/image.png') no-repeat center center/cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .brands-section {
            padding: 30px 0;
            text-align: center;
            background-color: #f8f9fa;
        }
        .brands-section h2, .top-selling-section h2{
            font-size: 28px;
            margin-bottom: 30px;
            color: #333;
        }
        .brands-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        .brand-item {
            border-radius: 5px;
            padding: 15px;
            transition: transform 0.3s ease;
            text-align: center;
        }
        .brand-item:hover {
            transform: translateY(-5px);
        }
        .brand-item img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid #ccc;
        }
        .brand-item p {
            margin: 0;
            font-weight: 500;
            color: #333;
        }
        .top-selling-section {
            padding: 30px 0;
            text-align: center;
        }
        .top-selling-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 40px;
            padding: 20px;
            
        }
        .product-item {
            width: 250px;
            text-align: center;
            transition: transform 0.3s;
            background-color: #f8f9fa;
            position: relative;
            padding-bottom: 50px;
            border-radius: 5px;
            overflow: hidden;
        }
        .product-item:hover {
            transform: scale(1.05);
        }
        .product-item img {
            margin-top: 20px;
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 5px;
        }
        .view-details {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 123, 255, 0.8);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            width: 100%;
        }

        .view-details.disabled {
            background-color: rgba(169, 169, 169, 0.8);
            cursor: not-allowed;
        }

        .product-item:hover .view-details {
            opacity: 1;          
            visibility: visible;
        }

    </style>
</head>
<body>

    <section class="hero">
        <div class="hero-text">
            <h1>KPL Watersport</h1>
            <h1>Equipment Shop</h1>
            <p>Find the best equipment for all your watersport needs!</p>
            <a href="register.php"><button class="join-btn">JOIN US</button></a>
        </div>
        <div class="advertisement-box">
            <div class="slideshow-container">
                <?php if (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $index => $voucher): ?>
                        <div class="mySlides fade">
                            <img src="image/voucher/<?= htmlspecialchars($voucher['VoucherPicture']) ?>" alt="Voucher <?= $index + 1 ?>" style="width:100%;">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:white;">No active vouchers available</p>
                <?php endif; ?>

            </div>


            <!-- Dots -->
            <div class="dot-container">
                <?php if (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $i => $voucher): ?>
                        <span class="dot" onclick="currentSlide(<?= $i + 1 ?>)"></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <br>
            <a href="product.php">
                <button class="product-btn">Shop Now!</button>
            </a>
        </div>
    </section>

    <section class="categories">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $row): ?>
                <?php 
                $categoryId = $row['CategoryID'];
                $categoryName = htmlspecialchars($row['CategoryName']);
                $categoryImage = $row['CategoryPicture'] ? 
                    'image/categories/' . htmlspecialchars($row['CategoryPicture']) : 
                    'image/categories/default-category.jpg';
                ?>
                <div class="category">
                    <a href="category.php?id=<?= $categoryId ?>">
                        <img src="<?= $categoryImage ?>" alt="<?= $categoryName ?>">
                    </a>
                    <span><?= $categoryName ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No categories found</p>
        <?php endif; ?>
    </section>
    
    <section class="brands-section">
        <h2>Explore More Brands</h2>
        <div class="brands-container">
            <?php if (!empty($brands)): ?>
                <?php foreach ($brands as $row): ?>
                    <?php 
                    $brandId = $row['BrandID'];
                    $brandName = htmlspecialchars($row['BrandName']);
                    $brandImage = $row['BrandPicture'] ? 
                        'image/brand/' . htmlspecialchars($row['BrandPicture']) : 
                        'image/brand/default-brand.jpg';
                    ?>
                    <a href="brand.php?id=<?= $brandId ?>" class="brand-item">
                        <img src="<?= $brandImage ?>" alt="<?= $brandName ?>">
                        <p><?= $brandName ?></p>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No brands found</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="top-selling-section">
        <h2>Top Selling Products</h2>
        <div class="top-selling-container">
            <?php if (!empty($top_products)): ?>
                <?php foreach ($top_products as $product): ?>
                    <?php 
                    $productId = $product['ProductID'];
                    $productName = htmlspecialchars($product['ProductName']);
                    $productImage = $product['ProductPicture'] ? 
                        'image/' . htmlspecialchars($product['ProductPicture']) : 
                        'image/default-product.jpg';
                    $quantitySold = $product['total_quantity_sold'];
                    
                    // Check if product, category and brand are all active
                    $isActive = ($product['ProductStatus'] == 'Active' && 
                                $product['CategoryStatus'] == 'Active' && 
                                $product['BrandStatus'] == 'Active');
                    ?>
                    <div class="product-item">
                        <img src="<?= $productImage ?>" alt="<?= $productName ?>">
                        <p><?= $productName ?></p>
                        <p>Sold: <?= $quantitySold ?></p>
                        <?php if ($isActive): ?>
                            <a href="product_details.php?id=<?= $productId ?>">
                                <button class="view-details">View Details</button>
                            </a>
                        <?php else: ?>
                            <button class="view-details disabled" disabled>Currently Unavailable</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No top selling products found.</p>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Slideshow functionality
        let slideIndex = 0;
        showSlides();

        function showSlides() {
            const slides = document.getElementsByClassName("mySlides");
            const dots = document.getElementsByClassName("dot");
            
            // Hide all slides
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";  
            }
            
            // Increment index
            slideIndex++;
            if (slideIndex > slides.length) { 
                slideIndex = 1;
            }

            // Show current slide
            slides[slideIndex - 1].style.display = "block";  

            // Update dots
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active");
            }
            dots[slideIndex - 1].classList.add("active");

            // Change slide every 5 seconds
            setTimeout(showSlides, 5000); 
        }

        function plusSlides(n) {
            slideIndex += n - 1;
            showSlides();
        }

        function currentSlide(n) {
            slideIndex = n - 1;
            showSlides();
        }

        // Add hover effects to category images
        document.addEventListener("DOMContentLoaded", () => {
            const categoryImages = document.querySelectorAll(".category img");

            categoryImages.forEach((img) => {
                img.addEventListener("mouseover", () => {
                    img.style.transform = "scale(1.1)";
                    img.style.transition = "0.3s ease";
                    img.style.boxShadow = "0 4px 10px rgba(0, 0, 0, 0.3)";
                });

                img.addEventListener("mouseout", () => {
                    img.style.transform = "scale(1)";
                    img.style.boxShadow = "none";
                });
            });
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>