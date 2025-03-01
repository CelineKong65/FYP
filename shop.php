<?php
    include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
    <link rel="stylesheet" href="shop.css">
</head>
<body>
    <div class="container-shop">
        <!-- Categories Section -->
        <div class="categories">
            <h2>Categories</h2>
            <ul>
                <li><a href="#">Swimming</a></li>
                <li><a href="#">Surfing and beach sports</a></li>
                <li><a href="#">Snorkeling / Scuba diving</a></li>
                <li><a href="#">Kayaking</a></li>
            </ul>
        </div>

        <!-- Products Section -->
        <div class="products">
            <div class="product">
                <div class="product-image">
                    <img src="Swimming/goggle2.png" alt="Goggle">
                    <button class="view-details">View Details</button>
                </div>
                <h3>Goggle</h3>
                <p>PKR 1300</p>
            </div>
            <div class="product">
                <div class="product-image">
                    <img src="Kayaking/kayak-paddle1.jpg" alt="Kayak Paddle">
                    <button class="view-details">View Details</button>
                </div>
                <h3>Kayak Paddle</h3>
                <p>PKR 2300</p>
            </div>
            <div class="product">
                <div class="product-image">
                    <img src="Swimming/women-swimsuit-bikini3.png" alt="Bikini">
                    <button class="view-details">View Details</button>
                </div>
                <h3>Women Swimsuit Bikini</h3>
                <p>PKR 1900</p>
            </div>
            <div class="product">
                <div class="product-image">
                    <img src="Snorkeling/Masks2.png" alt="Mask">
                    <button class="view-details">View Details</button>
                </div>
                <h3>Mask</h3>
                <p>PKR 3100</p>
            </div>
            <div class="product">
                <div class="product-image">
                    <img src="Snorkeling/Snorkels2.png" alt="Snorkels">
                    <button class="view-details">View Details</button>
                </div>
                <h3>Snorkels</h3>
                <p>PKR 2300</p>
            </div>
            <div class="product">
                <div class="product-image">
                    <img src="Surfing and beach sports/bodyboards1.jpg" alt="Bodyboards">
                    <button class="view-details">View Details</button>
                </div>
                <h3>Bodyboards</h3>
                <p>PKR 1800</p>
            </div>
        </div>
    </div>

    <!-- Pagination Section -->
    <div class="pagination">
        <a href="#" class="page">1</a>
        <a href="#" class="page">2</a>
        <a href="#" class="page">3</a>
        <span class="page">...</span>
        <a href="#" class="page">Last</a>
    </div>
</body>
</html>

<?php
include 'footer.php'; 
?>