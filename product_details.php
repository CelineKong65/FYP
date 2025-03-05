<?php
session_start();
include 'config.php'; 
include 'header.php';

// Check if ProductID is set in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID is missing.");
}

$productID = (int) $_GET['id'];

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM product WHERE ProductID = :productID");
$stmt->execute(['productID' => $productID]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

$stmt = $conn->prepare("SELECT Color, Picture FROM product_color WHERE ProductID = :productID");
$stmt->execute(['productID' => $productID]);
$colors = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT DISTINCT Size FROM product_size WHERE ProductID = :productID");
$stmt->execute(['productID' => $productID]);
$sizes = $stmt->fetchAll();

// Handle Add to Cart form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["productID"])) {
    // Ensure the user is logged in before adding to cart
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }

    // Add to cart logic here
    $productID = (int) $_POST["productID"];
    $quantity = isset($_POST["qty"]) ? (int) $_POST["qty"] : 1;
    $size = $_POST["size"] ?? null;
    $color = $_POST["color"] ?? null;

    // Insert into cart table (example)
    $stmt = $conn->prepare("INSERT INTO cart (CustID, ProductID, Quantity, Size, Color, ProductName, ProductPrice) 
    VALUES (:custID, :productID, :quantity, :size, :color, :productName, :productPrice)");
    $stmt->execute([
        'custID' => $_SESSION["user_id"],
        'productID' => $productID,
        'quantity' => $quantity,
        'size' => $size,
        'color' => $color,
        'productName' => $product['ProductName'],
        'productPrice' => $product['ProductPrice']
    ]);

    // Redirect to cart page
    header("Location: cart.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['ProductName']) ?> - Product Details</title>
    <link rel="stylesheet" href="product_details.css">
    <style>
        .container-product-details {
            display: flex;
            max-width: 1200px;
            width: 100%;
            padding: 20px;
            gap: 20px;
            justify-content: center;
            align-items: stretch;
            margin: auto; /* Centers the container */
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%); /* Centers the div perfectly */
        }

        .color-options {
            display: flex;
            justify-content: start;
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
                <?php
                // Default image
                $imageSrc = !empty($colors) ? 'image/' . $colors[0]['Picture'] : 'image/default-image.png';
                ?>
                <div class="main-image">
                    <img id="mainProductImage" src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                </div>
                <div class="thumbnails">
                    <?php foreach ($colors as $color): ?>
                        <img src="image/<?= $color['Picture'] ?>" alt="<?= $color['Color'] ?>" onclick="changeImage(this)">
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="info">
                <h2><?= htmlspecialchars($product['ProductName']) ?></h2>
                <p class="price">RM <?= number_format($product['ProductPrice'], 2) ?></p>
                <p><?= nl2br(htmlspecialchars($product['ProductDesc'])) ?></p>
                
                <!-- Color Selection -->
                <div class="color-options">
                    <?php foreach ($colors as $color): ?>
                        <div class="color-circle" style="background-color: <?= $color['Color'] ?>;" onclick="changeImageByColor('image/<?= $color['Picture'] ?>')"></div>
                    <?php endforeach; ?>
                </div>

                <div class="quantity">
                    <button type="button" onclick="decreaseQty()">-</button>
                    <input type="text" id="qty" name="qty" value="1">
                    <button type="button" onclick="increaseQty()">+</button>
                </div>


                <!-- Display Sizes Only If Available -->
                <?php if (!empty($sizes)): ?>
                    <div class="sizes">
                        <?php foreach ($sizes as $size): ?>
                            <?php if ($size['Size'] === null): ?>
                                <button class="size">Standard Only</button>
                            <?php else: ?>
                                <button class="size"><?= htmlspecialchars($size['Size']) ?></button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart Button -->
                <form action="" method="POST" onsubmit="return validateSelection()">
                    <input type="hidden" name="productID" value="<?= $productID ?>">
                    <input type="hidden" name="size" id="selectedSize" value="">
                    <input type="hidden" name="color" id="selectedColor" value="">
                    <input type="hidden" id="hiddenQty" name="qty" value="1">
                    <button type="submit">Add to Cart</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function changeImage(img) {
        document.getElementById("mainProductImage").src = img.src;
        }

        function changeImageByColor(imageSrc) {
            document.getElementById("mainProductImage").src = imageSrc;
        }

        function decreaseQty() {
            let qtyInput = document.getElementById('qty'); 
            let hiddenQty = document.getElementById('hiddenQty'); 
            if (qtyInput.value > 1) {
                qtyInput.value--;
                hiddenQty.value = qtyInput.value; 
            }
        }

        function increaseQty() {
            let qtyInput = document.getElementById('qty');
            let hiddenQty = document.getElementById('hiddenQty'); 
            qtyInput.value++;
            hiddenQty.value = qtyInput.value; 
        }


        document.querySelectorAll('.size').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelectorAll('.size').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedSize').value = this.textContent.trim();
            });
        });

        document.querySelectorAll('.color-circle').forEach(circle => {
            circle.addEventListener('click', function () {
                document.querySelectorAll('.color-circle').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedColor').value = this.style.backgroundColor;
            });
        });
        
        function validateSelection() {
            let selectedColor = document.getElementById('selectedColor').value;
            let selectedSize = document.getElementById('selectedSize').value;

            if (selectedColor === "" || selectedSize === "") {
                alert("Please select a color and size before adding to cart.");
                return false;
            }
            return true; 
        }
    </script>
</body>
</html>
