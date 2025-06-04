<?php
session_start();
include 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    //Check is email exist in database
    $sql = "SELECT CustID, CustEmail FROM customer WHERE CustEmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user) {
        //Generate OTP
        $otp = rand(100000, 999999);

        //Save OTP to password_reset
        $sql = "INSERT INTO password_reset (CustID, CustEmail, Token, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user['CustID'], $user['CustEmail'], $otp]);

        //Send OTP to user email via PHPMailer
        $mail = new PHPMailer(true);

        try{
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'lawrencetan20050429@gmail.com'; //Your Gmail
            $mail->Password = 'khzd gkui ieyv aadf'; //Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('lawrencetan20050429@gmail.com', 'KPL_WaterSport_Equipment');//Your Gmail

            $mail->addAddress($email);

            $mail->isHTML(true);

            $mail->Subject = 'Password Reset OTP';

            $mail->Body = "Your OTP is: <b>$otp</b>";

            $mail->send();
            $_SESSION['reset_email'] = $email;
            header("Location: OTPverify.php");
            exit();
        } catch (Exception $e) {
            $error_message = "Failed to send OTP. Please try again.";
        }
    }else{
        $error_message = "Email not found. Please enter a valid email address.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Find Back your Account</title>
    <link rel="stylesheet" type="text/css" href="findback.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
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
                <h2>FindBack Your Account</h2>
                <p>Enter your email address to receive an OTP for password reset.</p>
                <div class="frame">
                    <h2>Enter Your Email</h2>
                        <form method="POST" action="">
                            <label>Email:</label>
                            <input style="email" name="email" placeholder="Enter your email address" required>
                            <button type="submit" class="sub">SEND OTP</button>
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