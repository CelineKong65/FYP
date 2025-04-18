<?php
session_start();
ob_start();
include 'config.php'; 
include 'header.php';

// Check for messages in session
if (isset($_SESSION['wishlist_message'])) {
    $wishlistMessage = $_SESSION['wishlist_message'];
    unset($_SESSION['wishlist_message']);
}
if (isset($_SESSION['cart_message'])) {
    $cartMessage = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}

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

// Fetch available sizes with stock information
$stmt = $conn->prepare("SELECT Size, Stock FROM product_size WHERE ProductID = :productID ORDER BY Size");
$stmt->execute(['productID' => $productID]);
$sizeStocks = $stmt->fetchAll();

// Check total stock
$totalStock = 0;
foreach ($sizeStocks as $sizeStock) {
    $totalStock += $sizeStock['Stock'];
}

// Handle Add to Wishlist Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_wishlist"])) {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }

    $userID = $_SESSION["user_id"];

    try {
        $checkQuery = $conn->prepare("SELECT * FROM wishlist WHERE CustID = ? AND ProductID = ?");
        $checkQuery->execute([$userID, $productID]);

        if ($checkQuery->rowCount() > 0) {
            $_SESSION['wishlist_message'] = "This product is already in your wishlist.";
        } else {
            $insertQuery = $conn->prepare("INSERT INTO wishlist (CustID, ProductID) VALUES (?, ?)");
            if ($insertQuery->execute([$userID, $productID])) {
                $_SESSION['wishlist_message'] = "Product added to wishlist successfully!";
            } else {
                $_SESSION['wishlist_message'] = "Failed to add to wishlist.";
            }
        }
        header("Location: product_details.php?id=" . $productID);
        exit();
    } catch (PDOException $e) {
        $_SESSION['wishlist_message'] = "Database error: " . $e->getMessage();
        header("Location: product_details.php?id=" . $productID);
        exit();
    }
}

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }

    if (!isset($_POST["size"]) || empty($_POST["size"])) {
        $_SESSION['cart_message'] = "Please select a size.";
        header("Location: product_details.php?id=" . $productID);
        exit();
    } elseif (!isset($_POST["qty"]) || $_POST["qty"] < 1) {
        $_SESSION['cart_message'] = "Quantity must be at least 1.";
        header("Location: product_details.php?id=" . $productID);
        exit();
    }

    $userID = $_SESSION["user_id"];
    $size = trim($_POST["size"]);
    $quantity = (int)$_POST["qty"];

    // Validate quantity
    if ($quantity < 1) {
        $_SESSION['cart_message'] = "Quantity must be at least 1.";
        header("Location: product_details.php?id=" . $productID);
        exit();
    }

    $stockAvailable = false;
    $selectedStock = 0;
    foreach ($sizeStocks as $sizeStock) {
        $currentSize = $sizeStock['Size'] === null ? 'Standard Only' : $sizeStock['Size'];
        if ($currentSize == $size) {
            $selectedStock = $sizeStock['Stock'];
            $stockAvailable = true;
            break;
        }
    }

    if (!$stockAvailable) {
        $_SESSION['cart_message'] = "Selected size is not available.";
        header("Location: product_details.php?id=" . $productID);
        exit();
    }

    if ($quantity > $selectedStock) {
        $_SESSION['cart_message'] = "Selected quantity exceeds available stock for this size.";
        header("Location: product_details.php?id=" . $productID);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM cart WHERE CustID = ? AND ProductID = ? AND Size = ?");
        $stmt->execute([$userID, $productID, $size]);
        $existingItem = $stmt->fetch();

        if ($existingItem) {
            $stmt = $conn->prepare("UPDATE cart SET Quantity = ? WHERE CustID = ? AND ProductID = ? AND Size = ?");
            $stmt->execute([$quantity, $userID, $productID, $size]);
            $_SESSION['cart_message'] = "Cart quantity updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (CustID, ProductID, Quantity, Size, ProductName, ProductPrice) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userID, 
                $productID, 
                $quantity, 
                $size, 
                $product['ProductName'], 
                $product['ProductPrice']
            ]);
            $_SESSION['cart_message'] = "Product added to cart successfully!";
        }

        header("Location: product_details.php?id=" . $productID);
        exit();
    } catch (PDOException $e) {
        $_SESSION['cart_message'] = "Database error: " . $e->getMessage();
        header("Location: product_details.php?id=" . $productID);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ProductName']) ?> - Product Details</title>
    <link rel="stylesheet" href="product_details.css">
    <style>

    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
    background-color: #f9f9f9;
}

.container-product-details {
    display: flex;
    max-width: 1200px;
    width: 100%;
    padding: 20px;
    gap: 20px;
    justify-content: center;
    align-items: stretch;
    margin: auto; 
    position: absolute;
    top: 55%;
    left: 50%;
    transform: translate(-50%, -50%); 
}

.button {
    display: flex;
    align-items: center;
    gap: 10px; 
}

.wishlist-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    margin-left: 10px;
    background: #0066cc;
    color: white;
    font-size: 16px;
    margin-top: 30px;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.wishlist-btn:hover {
    background: darkblue;
}

.heart-button {
    width: 25px; 
    height: 25px;
}

.quantity {
    margin: 15px 0;
}
.quantity button {
    padding: 5px 10px;
    cursor: pointer;
}
.quantity input {
    width: 40px;
    text-align: center;
}
.sizes {
    margin: 15px 0;
    margin-top: 30px; 
}
.size {
    padding: 5px 17px;
    margin-right: 5px;
    cursor: pointer;
    background: #f0f0f0;
    border: 1px solid #000;
    position: relative;
    transition: 0.3s;
}
.size.active {
    background: #333;
    color: white;
    border-color: #333;
}

.size.out-of-stock {
    color: #999;
    background: #f5f5f5;
    border-color: #ddd;
    cursor: not-allowed;
}
.size .stock-info {
    position: absolute;
    bottom: -30px;
    left: 0;
    font-size: 10px;
    color: #666;
    width: 100%;
    text-align: center;
}
.out-of-stock-message {
    color: #d9534f;
    font-weight: bold;
    margin: 15px 0;
}
.in-stock-message {
    color: #5cb85c;
    font-weight: bold;
    margin: 15px 0;
}
.add-to-cart-btn {
    background: #0066cc;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 30px;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.add-to-cart-btn:hover {
    background: darkblue;
}


.categories {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 400px; 
    min-height: 440px; 
}

.categories h2 {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 15px;
    transform: translate(32%,8%);
}

.categories h2 {
    font-size: 24px;
    margin-bottom: 15px;
}

.categories ul {
    list-style: none;
    padding: 0;
    margin: 0;
    transform: translate(-50%,20%);
}

.categories ul li {
    margin-bottom: 15px;
    text-align: left; /* Align items to the left */
}

.categories ul li a {
    text-decoration: none;
    color: #333;
    font-size: 18px;
    display: block; /* Make the entire link clickable */
    padding: 5px 0;
}

.categories ul li a:hover {
    color: #007BFF;
}

.product-details {
    display: flex;
    gap: 20px;
    background-color: #fff;
    padding: 50px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 700px; /* Adjust width as needed */
}

.product-images {
    text-align: center;
}

.main-image {
    position: relative;
    width: 250px;
}

.main-image img {
    width: 100%;
    border-radius: 10px;
}

.thumbnails {
    display: flex;
    gap: 5px;
    margin-top: 10px;
    justify-content: center;
}

.thumbnails img {
    width: 60px;
    border: 2px solid transparent;
    cursor: pointer;
    border-radius: 5px;
}

.thumbnails img.active {
    border: 2px solid red;
}

.info {
    max-width: 300px;
}

.info h2 {
    font-size: 24px;
    margin-bottom: 20px; 
}

.info p {
    margin-top: 20px; 
}


.price {
    color: red;
    font-size: 22px;
    font-weight: bold;
}

.quantity {
    display: flex;
    align-items: center;
    margin: 10px 0;
    margin-top: 20px; 
}

.quantity button {
    width: 30px;
    height: 30px;
    border: none;
    background: #ddd;
    cursor: pointer;
}

.quantity input {
    width: 40px;
    text-align: center;
    border: none;
    font-size: 16px;
}

.error-message { color: red; margin: 10px 0; }
.success-message { color: green; margin: 10px 0; }

    </style>
</head>
<body>
    <div class="container-product-details">
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
        </div>

        <div class="product-details">
            <div class="product-images">
                <?php
                $imageSrc = $product['ProductPicture'] ? 'image/' . $product['ProductPicture'] : 'image/default-image.png';
                ?>
                <div class="main-image">
                    <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                </div>
            </div>
            <div class="info">
                <h2><?= htmlspecialchars($product['ProductName']) ?></h2>
                <p class="price">RM <?= number_format($product['ProductPrice'], 2) ?></p>
                <p><?= nl2br(htmlspecialchars($product['ProductDesc'])) ?></p>

                <?php if ($totalStock > 0): ?>
                    <p class="in-stock-message">In Stock</p>
                <?php else: ?>
                    <p class="out-of-stock-message">Out of Stock</p>
                <?php endif; ?>

                <!-- Messages Container -->
                <div class="message-container">
                    <?php if (isset($wishlistMessage)): ?>
                        <p class="<?= strpos($wishlistMessage, 'error') !== false ? 'error-message' : 'success-message' ?>">
                            <?= htmlspecialchars($wishlistMessage) ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (isset($cartMessage)): ?>
                        <p class="<?= strpos($cartMessage, 'error') !== false ? 'error-message' : 'success-message' ?>">
                            <?= htmlspecialchars($cartMessage) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Main form -->
                <form method="post" action="">
                    <input type="hidden" name="productID" value="<?= $productID ?>">

                    <div class="quantity">
                        <button type="button" onclick="decreaseQty()">-</button>
                        <input type="number" id="qty" name="qty" value="1" min="1" <?= $totalStock == 0 ? 'disabled' : '' ?>>
                        <button type="button" onclick="increaseQty()" <?= $totalStock == 0 ? 'disabled' : '' ?>>+</button>
                    </div>

                    <div class="sizes">
                        <?php foreach ($sizeStocks as $sizeStock): ?>
                            <?php 
                                $size = $sizeStock['Size'] === null ? 'Standard Only' : $sizeStock['Size'];
                                $stock = $sizeStock['Stock'];
                                $isOutOfStock = $stock <= 0;
                            ?>
                            <label class="size <?= $isOutOfStock ? 'out-of-stock' : '' ?>">
                                <input 
                                    type="radio" 
                                    name="size" 
                                    value="<?= htmlspecialchars($size) ?>" 
                                    <?= $isOutOfStock ? 'disabled' : '' ?>
                                    required
                                >
                                <?= htmlspecialchars($size) ?>
                                <span class="stock-info"><?= $stock ?> available</span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="button">
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn" <?= $totalStock == 0 ? 'disabled' : '' ?>>
                            <?= $totalStock == 0 ? 'Out of Stock' : 'Add to Cart' ?>
                        </button>
                        <button type="submit" name="add_to_wishlist" class="wishlist-btn">
                            <img src="image/circle-heart.png" alt="Wishlist" class="heart-button">
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function decreaseQty() {
            let qtyInput = document.getElementById('qty'); 
            if (qtyInput.value > 1) {
                qtyInput.value--;
            }
        }

        function increaseQty() {
            let qtyInput = document.getElementById('qty');
            let selectedSize = document.querySelector('input[name="size"]:checked');

            if (selectedSize) {
                let sizeLabel = selectedSize.closest('.size');
                let stockText = sizeLabel.querySelector('.stock-info').textContent;
                let maxStock = parseInt(stockText);
                if (parseInt(qtyInput.value) < maxStock) {
                    qtyInput.value++;
                } else {
                    alert(`Maximum available stock for this size is ${maxStock}`);
                }
            } else {
                qtyInput.value++;
            }
        }

        // Handle size selection
        document.querySelectorAll('.size:not(.out-of-stock)').forEach(sizeElement => {
            sizeElement.addEventListener('click', function() {
                // Remove active class from all sizes
                document.querySelectorAll('.size').forEach(el => {
                    el.classList.remove('active');
                });
                
                // Add active class to clicked size (if not out of stock)
                if (!this.classList.contains('out-of-stock')) {
                    this.classList.add('active');
                    // Also check the radio button
                    this.querySelector('input[type="radio"]').checked = true;
                }
            });
        });
    </script>
</body>
</html>