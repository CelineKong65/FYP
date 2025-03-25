<style>
    /* Footer Styles */
    .footer-section {
        background-color:black; 
        color: #ffffff; 
        padding: 50px 0;
        width:100%;
    }

    .footer-section {
        position: relative;
        bottom: 0;
        width: 100%;
        background-color: black;
        color: #ffffff;
        padding: 50px 0;
    }

    .footer-section .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .footer-section .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }

    .footer-section .col-lg-3,
    .footer-section .col-lg-2,
    .footer-section .col-lg-4 {
        padding: 0 15px;
        flex: 1;
    }

    .footer-left .footer-logo span {
        font-size: 24px;
        font-weight: bold;
        color: #ffffff;
    }

    .footer-left ul {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }

    .footer-left ul li {
        margin-bottom: 10px;
        color: #bdc3c7;
    }

    .footer-social a {
        display: inline-block;
        margin-right: 10px;
        color: #ffffff;
        font-size: 18px;
        transition: color 0.3s;
    }

    .footer-social img {
        width: 24px; /* Adjust the size of the icons */
        height: 24px;
    }

    .footer-social a:hover {
        opacity: 0.7;
    }

    .footer-social a:hover {
        color: #3498db; 
    }

    .footer-widget h5 {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #ffffff;
    }

    .footer-widget ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-widget ul li {
        margin-bottom: 10px;
    }

    .footer-widget ul li a {
        color: #bdc3c7; 
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-widget ul li a:hover {
        color: #3498db; 
    }

    .newslatter-item h5 {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #ffffff;
    }

    .newslatter-item p {
        color: #bdc3c7; 
        margin-bottom: 20px;
    }

    .subscribe-form {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .subscribe-form input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px 0 0 5px;
        height: 40px; 
        box-sizing: border-box;
        font-size: 14px; 
    }

    .subscribe-form button {
        height: 40px;
        padding: 0 15px;
        background-color: #3498db;
        color: #ffffff;
        border: 1px solid #3498db;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
        transition: background-color 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        font-size: 14px; 
        line-height: 1; 
    }

    .subscribe-form button:hover {
        background-color: #2980b9; 
    }
</style>

<footer class="footer-section">
    <div class="container">
        <div class="row" style="padding-bottom: 40px;">
          
            <div class="col-lg-3">
                <div class="footer-left">
                    <div class="footer-logo">
                        <a href="index.php">
                            <span>KPL Watersport</span>
                        </a>
                    </div>
                    <ul>
                        <li>+60 102282675</li>
                        <li>watersport@gmail.com</li>
                        <li>address</li>
                    </ul>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/" target="_blank">
                            <img src="image/facebook.png" alt="Facebook">
                        </a>
                        <a href="https://www.instagram.com/?hl=en" target="_blank">
                            <img src="image/instagram.png" alt="Instagram">
                        </a>
                        <a href="#" target="_blank">
                            <img src="image/twitter.png" alt="Twitter">
                        </a>
                        <a href="#" target="_blank">
                            <img src="image/whatsapp.png" alt="WhatsApp">
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 offset-lg-1">
                <div class="footer-widget">
                    <h5>Information</h5>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="footer-widget">
                    <h5>Account</h5>
                    <ul>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="account.php">My Account</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo isset($_SESSION['user_id']) ? 'shopping_cart.php' : 'login.php'; ?>">Shopping Cart</a></li>
                        <li><a href="<?php echo isset($_SESSION['user_id']) ? 'payment.php' : 'login.php'; ?>">Check Out</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="newslatter-item">
                    <h5>Keep in touch</h5>
                    <p>Get E-mail updates about our latest special offers.</p>
                    <form action="index.php" class="subscribe-form">
                        <input type="text" placeholder="Enter Your Mail">
                        <button type="button" class="footer-subscribe-btn">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</footer>