<?php
session_start();
ob_start();
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

// Fetch available sizes
$stmt = $conn->prepare("SELECT DISTINCT Size FROM product_size WHERE ProductID = :productID AND Stock > 0");
$stmt->execute(['productID' => $productID]);
$sizes = $stmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["productID"])) {
    // Ensure the user is logged in before adding to cart
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
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
        
        .button {
            display: flex;
            align-items: center;
            gap: 10px; /* Adjust the spacing between buttons */
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
            margin-top: 10px;
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
        }
        .size {
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
            background: #f0f0f0;
            border: 1px solid #ddd;
        }
        .size.active {
            background: #333;
            color: white;
            border-color: #333;
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
                
                <div class="quantity">
                    <button type="button" onclick="decreaseQty()">-</button>
                    <input type="text" id="qty" name="qty" value="1">
                    <button type="button" onclick="increaseQty()">+</button>
                </div>

                <!-- Display Sizes Only If Available -->
                <?php if (!empty($sizes)): ?>
                    <div class="sizes">
                        <p>Available Sizes:</p>
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
                    <input type="hidden" id="hiddenQty" name="qty" value="1">
                </form>

                <!-- Add the hidden input field to check login status -->
                <input type="hidden" id="isLoggedIn" value="<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>">

                <div class="button">
                    <button type="submit" onclick="addToCart()">Add to Cart</button>
                    <button type="button" class="wishlist-btn" onclick="addToWishlist(<?= $productID ?>)">
                        <img src="image/circle-heart.png" alt="Wishlist" class="heart-button">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
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
        
        function validateSelection() {
            let selectedSize = document.getElementById('selectedSize').value;

            if (selectedSize === "") {
                alert("Please select a size before adding to cart.");
                return false;
            }
            return true; 
        }

        function addToCart() {
            let isLoggedIn = document.getElementById("isLoggedIn").value; 
            if (isLoggedIn !== "true") {
                window.location.href = "login.php"; 
                return;
            }

            let productID = <?= $productID ?>;
            let qty = document.getElementById("qty").value;
            let size = document.getElementById("selectedSize").value;

            if (size === "") {
                alert("Please select a size before adding to cart.");
                return;
            }

            let formData = new FormData();
            formData.append("productID", productID);
            formData.append("qty", qty);
            formData.append("size", size);

            fetch("add_to_cart.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("cartCount").textContent = data.cartCount;
                    alert("Item added to cart!");
                } else {
                    alert("Failed to add item to cart.");
                }
            })
            .catch(error => console.error("Error:", error));
        }
        
        function addToWishlist(productID) {
            let isLoggedIn = document.getElementById("isLoggedIn").value; 
            if (isLoggedIn !== "true") {
                window.location.href = "login.php"; 
                return;
            }

            let formData = new FormData();
            formData.append("productID", productID);

            fetch("add_to_wishlist.php", {
                method: "POST",
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error("Network error");
                return response.text();
            })
            .then(message => {
                alert(message); 
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Operation failed, please try again.");
            });
        }
    </script>
</body>
</html>