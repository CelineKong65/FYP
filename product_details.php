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
    
    // Get selected size or default to 'Standard Only'
    $size = isset($_POST["size"]) ? trim($_POST["size"]) : 'Standard Only';

    try {
        $checkQuery = $conn->prepare("SELECT * FROM wishlist WHERE CustID = ? AND ProductID = ? AND Size = ?");
        $checkQuery->execute([$userID, $productID, $size]);

        if ($checkQuery->rowCount() > 0) {
            $_SESSION['wishlist_message'] = "This product with the selected size is already in your wishlist.";
        } else {
            $insertQuery = $conn->prepare("INSERT INTO wishlist (CustID, ProductID, Size) VALUES (?, ?, ?)");
            if ($insertQuery->execute([$userID, $productID, $size])) {
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
                    <input type="hidden" name="size" id="selectedSize" value="">

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
                                    onchange="document.getElementById('selectedSize').value = this.value;"
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
                    // Update hidden size field
                    document.getElementById('selectedSize').value = this.querySelector('input[type="radio"]').value;
                }
            });
        });
    </script>
</body>
</html>

