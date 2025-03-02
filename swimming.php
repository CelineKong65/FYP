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
                    <img id="boy-swimsuit-boxer-image" src="Swimming/boy-swimsuit-boxer2.png" alt="Boy Swimsuit Boxer">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: black;" data-image="Swimming/boy-swimsuit-boxer2.png" onclick="changeImage('boy-swimsuit-boxer-image', 'Swimming/boy-swimsuit-boxer2.png')"></div>
                    <div class="color-circle" style="background-color: blue;" data-image="Swimming/boy-swimsuit-boxer3.png" onclick="changeImage('boy-swimsuit-boxer-image', 'Swimming/boy-swimsuit-boxer3.png')"></div>
                </div>
                <h3>Boy Swimsuit Boxer</h3>
                <p>RM 35.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="boy-swimsuit-jammer-image" src="Swimming/boy-swimsuit-jammer.png" alt="Boy Swimsuit Jammer">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: blue;" data-image="Swimming/boy-swimsuit-jammer.png" onclick="changeImage('boy-swimsuit-jammer-image', 'Swimming/boy-swimsuit-jammer.png')"></div>
                    <div class="color-circle" style="background-color: black;" data-image="Swimming/boy-swimsuit-jammer4.png" onclick="changeImage('boy-swimsuit-jammer-image', 'Swimming/boy-swimsuit-jammer4.png')"></div>
                </div>
                <h3>Boy Swimsuit Jammer</h3>
                <p>RM 50.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="boy-swimsuit-long-image" src="Swimming/boy-swimsuit-long.png" alt="Boy Swimsuit Long Sleeved">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: green;" data-image="Swimming/women-swimsuit-bikini3.png" onclick="changeImage('boy-swimsuit-long-image', 'Swimming/boy-swimsuit-long.png')"></div>
                </div>
                <h3>Boy Swimsuit Long Sleeved</h3>
                <p>RM 89.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="boy-swimsuit-short-image" src="Swimming/boy-swimsuit-short2.png" alt="Boy Swimsuit Short Sleeved">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: darkblue;" data-image="Swimming/boy-swimsuit-short2.png" onclick="changeImage('boy-swimsuit-short-image', 'Swimming/boy-swimsuit-short2.png')"></div>
                </div>
                <h3>Boy Swimsuit Short Sleeved</h3>
                <p>RM 80.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="girl-swimsuit-one-piece1" src="Swimming/girl-swimsuit-onepiece2.png" alt="Girl Swimsuit One Piece 1">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: purple;" data-image="Swimming/girl-swimsuit-onepiece2.png" onclick="changeImage('girl-swimsuit-one-piece1', 'Swimming/girl-swimsuit-onepiece2.png')"></div>
                </div>
                <h3>Girl Swimsuit One Piece 1</h3>
                <p>RM 80.00</p>
            </div>

            <div class="product">
                <div class="product-image">
                    <img id="girl-swimsuit-one-piece2" src="Swimming/girl-swimsuit-onepiece4.png" alt="Girl Swimsuit One Piece 2">
                    <button class="view-details">View Details</button>
                </div>
                <div class="color-options">
                    <div class="color-circle" style="background-color: pink;" data-image="Swimming/girl-swimsuit-onepiece4.png" onclick="changeImage('girl-swimsuit-one-piece2', 'Swimming/girl-swimsuit-onepiece4.png')"></div>
                </div>
                <h3>Girl Swimsuit One Piece 2</h3>
                <p>RM 79.00</p>
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