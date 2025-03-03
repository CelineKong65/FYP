<?php
include 'header.php';

// Database connection
$host = '127.0.0.1';
$db = 'fyp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if ProductID is set in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID is missing.");
}

$productID = (int) $_GET['id'];

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM product WHERE ProductID = :productID");
$stmt->execute(['productID' => $productID]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch available colors and images
$stmt = $pdo->prepare("SELECT Color, Picture FROM product_color WHERE ProductID = :productID");
$stmt->execute(['productID' => $productID]);
$colors = $stmt->fetchAll();

// Fetch available sizes
$stmt = $pdo->prepare("SELECT DISTINCT Size FROM product_size WHERE ProductID = :productID");
$stmt->execute(['productID' => $productID]);
$sizes = $stmt->fetchAll();
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
            align-items: center;
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
                    <button onclick="decreaseQty()">-</button>
                    <input type="text" id="qty" value="1">
                    <button onclick="increaseQty()">+</button>
                </div>

                <!-- Display Sizes Only If Available -->
                <?php if (!empty($sizes)): ?>
                    <div class="sizes">
                        <?php foreach ($sizes as $size): ?>
                            <button class="size"><?= htmlspecialchars($size['Size']) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart Button -->
                <form action="cart.php" method="POST">
                    <input type="hidden" name="productID" value="<?= $productID ?>">
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
