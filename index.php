<?php
    include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watersport Equipment Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">HOME</a></li>
                <li><a href="about.php">ABOUT</a></li>
                <li><a href="product.php">PRODUCTS</a></li>
                <li><a href="contact.php">CONTACT</a></li>
            </ul>
        </nav>
        <div class="user-auth">
            <img src="user.png" alt="User Account" class="user-icon">
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

    <section class="hero">
        <div class="hero-text">
            <h1>Watersport Equipment Shop</h1>
            <p>Find the best equipment for all your watersport needs!</p>
            <button class="join-btn">JOIN US</button>
        </div>
        <div class="advertisement-box">
            <h2>Advertisement</h2>
            <!-- Slideshow Advertisement -->
            <div class="slideshow-container">
                <div class="mySlides fade">
                    <img src="image/Goggles.png" alt="Goggles" style="width:55%;">
                </div>
                <div class="mySlides fade">
                    <img src="image/Shortboard.jpg" alt="Shortboard" style="width:55%;">
                </div>
                <div class="mySlides fade">
                    <img src="image/Life-Jacket.jpg" alt="Life Jacket" style="width:55%;">
                </div>
                <div class="mySlides fade">
                    <img src="image/Diving-Mask.png" alt="Snorkel Mask" style="width:55%;">
                </div>

                <!-- Navigation buttons -->
                <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                <a class="next" onclick="plusSlides(1)">&#10095;</a>
            </div>

            <!-- Dots -->
            <div class="dot-container">
                <span class="dot" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
                <span class="dot" onclick="currentSlide(4)"></span>
            </div>
            <br>
            <button class="product-btn">Shop Now!</button>
        </div>
    </section>

    <section class="categories">
        <div class="category">
            <a href="swimming.php">
                <img src="swimming.jpg" alt="Swimming">
            </a>
            <span>Swimming</span>
        </div>
        <div class="category">
            <a href="surfing.php">
                <img src="surfing.png" alt="Surfing and beach sports">
            </a>
            <span>Surfing and beach sports</span>
        </div>
        <div class="category">
            <a href="snorkeling.php">
                <img src="scuba.png" alt="Snorkeling / Scuba diving">
            </a>
            <span>Snorkeling / Scuba diving</span>
        </div>
        <div class="category">
            <a href="kayaking.php">
                <img src="kayaking.png" alt="Kayaking">
            </a>
            <span>Kayaking</span>
        </div>
    </section>
    
    <script>
        // Slideshow functionality
        var slideIndex = 0;
        showSlides();

        function showSlides() {
            var slides = document.getElementsByClassName("mySlides");
            var dots = document.getElementsByClassName("dot");
            
            for (var i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";  
            }
            slideIndex++;
            if (slideIndex > slides.length) { slideIndex = 1 } 

            slides[slideIndex - 1].style.display = "block";  

            for (var i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            dots[slideIndex - 1].className += " active";

            setTimeout(showSlides, 5000); // Change image every 5 seconds
        }

        function plusSlides(n) {
            slideIndex += n - 1;
            showSlides();
        }

        function currentSlide(n) {
            slideIndex = n - 1;
            showSlides();
        }

        // Hover effect for category images
        document.addEventListener("DOMContentLoaded", function () {
            const categoryImages = document.querySelectorAll(".category img");

            categoryImages.forEach((img) => {
                img.addEventListener("mouseover", function () {
                    img.style.transform = "scale(1.1)";
                    img.style.transition = "0.3s ease";
                    img.style.boxShadow = "0 4px 10px rgba(0, 0, 0, 0.3)";
                });

                img.addEventListener("mouseout", function () {
                    img.style.transform = "scale(1)";
                    img.style.boxShadow = "none";
                });
            });
        });


        document.addEventListener("DOMContentLoaded", function () {
            const productList = document.querySelector(".product-carousel");
            const prevBtn = document.querySelector(".prev-btn");
            const nextBtn = document.querySelector(".next-btn");
            const dots = document.querySelectorAll(".dot");

            let index = 0;
            const totalSlides = 2; // Since we have 6 products and show 3 at a time

            function updateCarousel() {
                productList.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((dot, i) => dot.classList.toggle("active", i === index));
            }

            prevBtn.addEventListener("click", function () {
                index = index > 0 ? index - 1 : totalSlides - 1;
                updateCarousel();
            });

            nextBtn.addEventListener("click", function () {
                index = index < totalSlides - 1 ? index + 1 : 0;
                updateCarousel();
            });

            dots.forEach((dot, i) => {
                dot.addEventListener("click", function () {
                    index = i;
                    updateCarousel();
                });
            });
        });




    </script>
</body>
</html>

<?php
include 'footer.php';
?>