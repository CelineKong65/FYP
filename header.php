<?php
session_start();
include 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch cart count if user is logged in
$cartCount = 0;
if ($isLoggedIn) {  
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cart WHERE CustID = ?");  
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $cartCount = $row['total'] ?? 0;
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
        html {
            scroll-padding-top: 80px;
        }

        body {
            margin: 0;
            padding-top: 80px; /* Match your header height */
        }

        header {
            height: 120px; /* Fixed height */
            box-sizing: border-box;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
            position: fixed;
            top: 0;
            width: 100%;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .logo img {
            width: 80px;
            height: auto;
        }

        nav {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }
        
        nav ul {
            list-style: none;
            display: flex;
            gap: 40px;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            display: inline;
        }

        nav ul li a {
            text-decoration: none;
            color: black;
            font-size: 18px;
            font-weight: bold;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: #007BFF;
        }

        /* New container for right-side icons */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-auth {
            position: relative;
        }

        .user-icon, .cart-icon, .love-icon {
            width: 30px;
            height: 30px;
            cursor: pointer;
        }

        .user-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            width: 150px;
            text-align: center;
            padding: 10px;
        }

        .user-menu a {
            display: block;
            text-decoration: none;
            color: black;
            font-size: 14px;
            padding: 8px;
            transition: 0.3s;
        }

        .user-menu a:hover {
            background-color: #f0f0f0;
        }

        /* Show dropdown on hover */
        .user-auth:hover .user-menu {
            display: block;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: blue; 
            color: white;
            font-size: 12px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-container, .wishlist-container {
            position: relative;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">HOME</a></li>
                <li><a href="about_us.php">ABOUT</a></li>
                <li><a href="product.php">PRODUCTS</a></li>
                <li><a href="contact.php">CONTACT</a></li>
                <li><a href="customer_shipping.php">TRACK</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="feedback.php">FEEDBACK</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="header-right">
            <?php if ($isLoggedIn): ?>
                <div class="wishlist-container">
                    <a href="wishlist.php">
                        <img src="image/wishlist.png" alt="Wishlist" class="love-icon">
                    </a>
                </div>
                <div class="cart-container">
                    <a href="shopping_cart.php">
                        <img src="image/shopping-cart.png" alt="Cart" class="cart-icon">
                        <span id="cartCount" class="cart-count"><?= htmlspecialchars($cartCount) ?></span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="user-auth">
                <img src="image/user.png" alt="User Account" class="user-icon">
                <div class="user-menu">
                    <?php if ($isLoggedIn): ?>
                        <a href="account.php">My Account</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Sign Up</a>
                        <a href="admin/admin_login.php">Admin Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const userIcon = document.querySelector(".user-icon");
            const userMenu = document.querySelector(".user-menu");

            userIcon.addEventListener("click", function () {
                userMenu.style.display = userMenu.style.display === "block" ? "none" : "block";
            });

            document.addEventListener("click", function (event) {
                if (!userIcon.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>