<?php
session_start();
// Check if the user came from a successful registration
if (!isset($_SESSION['registration_success'])) {
    header("Location: register.php");
    exit();
}

// Clear the session flag
unset($_SESSION['registration_success']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Successful</title>
    <link rel="stylesheet" type="text/css" href="register.css">
</head>
<style>
    .container{
        justify-content: center;
        align-items: center;
        width: 100%;
        height: auto;
        margin-top: 300px;
    }

    .success-message {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: 0 auto;
    z-index: 1;
}

.success-message h2 {
    color: #28a745;
    margin-bottom: 20px;
}

.success-message p {
    font-size: 18px;
    margin-bottom: 15px;
}

#countdown {
    font-weight: bold;
    color: #007bff;
}
</style>
<body>
    <header>
        <div class="logo">
            <img src="image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
    </header>

    <section class="container">
        <div class="success-message">
            <h2>Registration Successful!</h2>
            <p>You will be automatically redirected to the login page in <span id="countdown">2</span> seconds.</p>
            <p>If you are not redirected, <a href="login.php">click here</a>.</p>
        </div>
    </section>

    <script>
        let seconds = 2;
        const countdown = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            seconds--;
            countdown.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>