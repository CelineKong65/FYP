<?php
session_start();
include("config.php");

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
        header {
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
            margin-left: 55%;
        }
    
        nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
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

        .user-auth {
            position: relative;
        }

        .user-icon {
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
        .shopping-cart {
            margin-right: 10px;
        }

        .cart-icon {
            width: 30px;
            height: 30px;
            cursor: pointer;
        }
        .cart-count {
            position: absolute;
            top: 30px;
            right: 70px;
            background-color: blue; /* Changed background color to blue */
            color: white;
            font-size: 14px;
            font-weight: bold;
            width: 20px;
            height: 20px;
            border-radius: 50%; /* Keeps it circular */
            display: flex;
            align-items: center;
            justify-content: center;
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
                <li><a href="about.php">ABOUT</a></li>
                <li><a href="product.php">PRODUCTS</a></li>
                <li><a href="contact.php">CONTACT</a></li>
                <li><a href="wishlist.php">WISHLIST</a></li>
            </ul>
        </nav>

        <?php if ($isLoggedIn): ?>
        <div class="shopping-cart">
            <a href="shopping_cart.php">
                <img src="image/shopping-cart.png" alt="Cart" class="cart-icon">
                <span id="cartCount" class="cart-count"><?= $cartCount ?></span>
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
                <?php endif; ?>
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
</html>