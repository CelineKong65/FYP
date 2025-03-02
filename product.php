<?php
    include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
    <link rel="stylesheet" href="product.css">
    <style>
        /* Additional CSS for color circles */
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
            <div class="product">
                <div class="product-image">
                    <img id="goggle-image" src="Swimming/goggle2.png" alt="Goggle">
                    <a href="product_details.php"><button class="view-details">View Details</button></a>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: lightgreen;" data-image="Swimming/goggle.png" onclick="changeImage('goggle-image', 'Swimming/goggle.png')"></div>
                    <div class="color-circle" style="background-color: pink;" data-image="Swimming/goggle2.png" onclick="changeImage('goggle-image', 'Swimming/goggle2.png')"></div>
                    <div class="color-circle" style="background-color: darkblue;" data-image="Swimming/goggle3.png" onclick="changeImage('goggle-image', 'Swimming/goggle3.png')"></div>
                </div>
                <h3>Goggles</h3>
                <p>RM 19.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="kayak-paddle-image" src="Kayaking/kayak-paddle1.jpg" alt="Kayak Paddle">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: black;" data-image="Kayaking/kayak-paddle1.jpg" onclick="changeImage('kayak-paddle-image', 'Kayaking/kayak-paddle1.jpg')"></div>
                    <div class="color-circle" style="background-color: skyblue;" data-image="Kayaking/kayak-paddle2.jpg" onclick="changeImage('kayak-paddle-image', 'Kayaking/kayak-paddle2.jpg')"></div>
                    <div class="color-circle" style="background-color: yellow;" data-image="Kayaking/kayak-paddle3.jpgg" onclick="changeImage('kayak-paddle-image', 'Kayaking/kayak-paddle3.jpg')"></div>
                </div>
                <h3>Kayak Paddle</h3>
                <p>RM 120.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="bikini-image" src="Swimming/women-swimsuit-bikini3.png" alt="Bikini">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: pink;" data-image="Swimming/women-swimsuit-bikini3.png" onclick="changeImage('bikini-image', 'Swimming/women-swimsuit-bikini3.png')"></div>
                    <div class="color-circle" style="background-color: black;" data-image="Swimming/women-swimsuit-bikini2.png" onclick="changeImage('bikini-image', 'Swimming/women-swimsuit-bikini2.png')"></div>
                </div>
                <h3>Women Swimsuit Bikini</h3>
                <p>RM 50.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="mask-image" src="Snorkeling/Masks2.png" alt="Mask">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: black;" data-image="Snorkeling/Masks2.png" onclick="changeImage('mask-image', 'Snorkeling/Masks2.png')"></div>
                </div>
                <h3>Diving Mask</h3>
                <p>RM 70.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="snorkels-image" src="Snorkeling/Snorkels2.png" alt="Snorkels">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: black;" data-image="Snorkeling/Snorkels1.png" onclick="changeImage('snorkels-image', 'Snorkeling/Snorkels1.png')"></div>
                    <div class="color-circle" style="background-color: blue;" data-image="Snorkeling/Snorkels2.png" onclick="changeImage('snorkels-image', 'Snorkeling/Snorkels2.png')"></div>
                    <div class="color-circle" style="background-color: red;" data-image="Snorkeling/Snorkels3.png" onclick="changeImage('snorkels-image', 'Snorkeling/Snorkels3.png')"></div>
                </div>
                <h3>Snorkels</h3>
                <p>RM 40.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="bodyboards-image" src="Surfing and beach sports/bodyboards1.jpg" alt="Bodyboards">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: green;" data-image="Surfing and beach sports/bodyboards1.jpg" onclick="changeImage('bodyboards-image', 'Surfing and beach sports/bodyboards1.jpg')"></div>
                    <div class="color-circle" style="background-color: darkblue;" data-image="Surfing and beach sports/bodyboards2.jpg" onclick="changeImage('bodyboards-image', 'Surfing and beach sports/bodyboards2.jpg')"></div>
                    <div class="color-circle" style="background-color: yellow;" data-image="Surfing and beach sports/bodyboards3.jpg" onclick="changeImage('bodyboards-image', 'Surfing and beach sports/bodyboards3.jpg')"></div>
                </div>
                <h3>Bodyboard</h3>
                <p>RM 200.00</p>
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

    <script>
        // JavaScript function to change the product image
        function changeImage(imageId, newImageSrc) {
            document.getElementById(imageId).src = newImageSrc;
        }
    </script>
</body>
</html>

<?php
include 'footer.php'; 
?>