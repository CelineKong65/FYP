<style>
/* Footer Styles */
.footer-section {
    background-color: black;
    color: #ffffff;
    padding: 50px 0;
    width: 100%;
    position: relative;
    bottom: 0;
}

.footer-section .footer-container {
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
.footer-section .col-lg-2 {
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
    width: 24px;
    height: 24px;
}

.footer-social a:hover {
    opacity: 0.7;
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

.footer-copyright {
    margin-top: 30px;
    color: rgba(255, 255, 255, 0.7);
    align-items: center;
    justify-content: center;
    display: flex;
}

iframe {
    width: 500px;
    height: 200px;
}

</style>


<footer class="footer-section">
    <div class="footer-container">
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
                        <li>
                            <a href="https://maps.app.goo.gl/fTRxNBsoeXojXcuKA" target="_blank" 
                                style="color: #bdc3c7; text-decoration: none;">
                                Jalan Ayer Keroh Lama, 75450 Bukit Beruang, Melaka
                            </a>
                        </li>
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

        </div>
        <p class="footer-copyright">&copy; <?php echo date('Y'); ?> KPL Watersport. All rights reserved.</p>
    </div>
</footer>
