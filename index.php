<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watersport Equipment Shop</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <nav>
            <ul>
                <li><a href="#">HOME</a></li>
                <li><a href="#">ABOUT</a></li>
                <li><a href="#">PRODUCT</a></li>
                <li><a href="#">CONTACT</a></li>
            </ul>
        </nav>
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
                    <img src="Swimming/goggle.png" alt="Goggles" style="width:55%;">
                </div>
                <div class="mySlides fade">
                    <img src="Surfing and beach sports/shortboards1.png" alt="Shortboard" style="width:100%;">
                </div>
                <div class="mySlides fade">
                    <img src="Kayaking/life-jacket1.png" alt="Life Jacket" style="width:100%;">
                </div>
                <div class="mySlides fade">
                    <img src="Snorkelling/Mask3.png" alt="Snorkel Mask" style="width:100%;">
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

    <script>
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
    </script>
</body>
</html>
