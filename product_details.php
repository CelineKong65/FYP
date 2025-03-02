<?php
    include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details Page</title>
    <link rel="stylesheet" href="product_details.css">
    <style>
        .container-product-details {
            display: flex;
            max-width: 1200px;
            width: 100%;
            padding: 20px;
            gap: 20px;
            justify-content: center;
            align-items: center;
            position: relative;
            transform: translateX(-10%);
        }
    </style>
</head>
<body>
    <div class="container-product-details">
        <div class="categories">
            <h2>Categories</h2>
            <ul>
                <li><a href="swimming.php">Swimming</a></li>
                <li><a href="surfing.php">Surfing and beach sports</a></li>
                <li><a href="snorkeling.php">Snorkeling / Scuba diving</a></li>
                <li><a href="kayaking.php">Kayaking</a></li>
            </ul>
        </div>

        <div class="product-details">
            <div class="product-images">
                <div class="main-image">
                    <img id="mainImg" src="Swimming/goggle.png" alt="Goggle">
                </div>
                <div class="thumbnails">
                    <img src="Swimming/goggle.png" alt="Thumbnail" class="active" onclick="changeImage(this)">
                    <img src="Swimming/goggle2.png" alt="Thumbnail" onclick="changeImage(this)">
                    <img src="Swimming/goggle3.png" alt="Thumbnail" onclick="changeImage(this)">
                </div>
            </div>

            <div class="info">
                <h2>Goggles</h2>
                <p>Essential eyewear for swimmers, providing clear vision and eye protection while swimming.</p>
                <p class="price">RM 19.00</p>
                <p><strong>Category:</strong> Swimming</p>

                <div class="quantity">
                    <button onclick="decreaseQty()">-</button>
                    <input type="text" id="qty" value="1">
                    <button onclick="increaseQty()">+</button>
                </div>

                <div class="sizes">
                    <button class="size">S</button>
                    <button class="size">M</button>
                    <button class="size">L</button>
                    <button class="size">XL</button>
                </div>

                <button class="add-to-cart">ADD TO CART</button>
            </div>
        </div>
    </div>

    <script>
        function changeImage(element) {
            document.getElementById('mainImg').src = element.src;
            document.querySelectorAll('.thumbnails img').forEach(img => img.classList.remove('active'));
            element.classList.add('active');
        }

        function decreaseQty() {
            let qty = document.getElementById('qty');
            if (qty.value > 1) qty.value--;
        }

        function increaseQty() {
            let qty = document.getElementById('qty');
            qty.value++;
        }

        document.querySelectorAll('.size').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelectorAll('.size').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });

    </script>
</body>
</html>
