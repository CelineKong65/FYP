<?
session_start();
include 'config.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $email= &$_POST['email'];

    $sql = "SELECT CustID, CustEmail FROM customer WHERE CustEmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        //Generate random 6 code
        $otp = rand(100000, 999999);

        //Store OTP and email in the password_reset table
        $sql = "INSERT INTO password_reset (CustID, CustEmail, Token) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user['CustID'], $user['CustEmail'], $otp]);

        //Store in session
        $_SESSION['reset_email'] = $user['CustEmail'];
        $_SESSION['reset_otp'] = $otp;

        //Continue with OTPverify.php
        header("Location: OTPverify.php");
        exit();
    }else{
        $error_message = "Email nto found. Please enter a valid email address.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" type="text/css" href="findback.css">
    <script src="https://smtpjs.com/v3/smtp.js"></script>
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
    <script src="findback.js"></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="return">
        <a onclick="history.back();"><i class="fa-solid fa-rotate-left"><h2>RETURN</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="image/Picture.png" alt="Side Picture">
        </div>
        <div class="right-side">
            <div class="right-side-inner">
                <div class="frame">
                    <h2>Forgot Password</h2>
                    <p>In the prepared box, type your current email address.<br>The OTP code will be sent to your email soon.</p>
                    <form id="emailForm" method="POST" action="">
                        <label>Enter your email:</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com" required>
                        <button type="submit">SEND OTP</button>
                    </form>
                    <?php if ($error_message): ?>
                        <p class="error"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                </div>
            </div> 
        </div>
    </section>
</body>
</html>