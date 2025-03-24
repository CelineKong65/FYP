<?php
session_start();
include 'config.php'; 
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(!isset($_SESSION['reset_email'])) {
    header("Location: findback.php");
    exit();
}

$error_message = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_otp'])) {
        $enteredOTP = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];

        $sql = "SELECT Token FROM password_reset WHERE CustEmail = ? AND created_at >= NOW() - INTERVAL 10 MINUTE ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_SESSION['reset_email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $enteredOTP == $row['Token']){
            header("Location: reset-password.php");
            exit();
        }else{
            $error_message = "Invalid OTP. Please try again";
        }
    } elseif (isset($_POST['resend_otp'])){
        //Generate a new OTP
        $newOTP = rand(100000, 999999);
        //Resend OTP 
        $sql = "UPDATE password_reset SET Token = ?, created_at = NOW() WHERE CustEmail = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newOTP, $_SESSION['reset_email']]);

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
    
                $mail->setFrom('lawrencetan20050429@gmail.com', 'WaterSport_Equipment');//Your Gmail
    
                $mail->addAddress($_SESSION['reset_email']);
    
                $mail->isHTML(true);
    
                $mail->Subject = 'Resend Password Reset OTP';
    
                $mail->Body = "Your OTP is: <b>$otp</b>";
    
                $mail->send();
                $error_message = "OTP resent successfully.";
            } catch (Exception $e) {
                $error_message = "Failed to send OTP. Please try again.";
            }
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link rel="stylesheet" type="text/css" href="OTPverify.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
    <script>
        // Automatic move to next box
        function move(current, next) {
            if (current.value.length >= 1) {
                document.getElementById(next).focus();
            }
        }
    </script>
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
            <h2>Verify OTP</h2>
            <p>Please check your email for getting OTP.<br>If not, you can check in your SPAM message or press "SEND A NEW OTP" for a new OTP</p>
                <div class="frame">
                    <form method="POST" action="">
                        <h4>Insert OTP</h4>
                        <div class="otp-inputs">
                            <input type="text" class="otp" id = "otp1" name="otp1" maxlength="1" oninput="move(this, 'otp2')" required>
                            <input type="text" class="otp" id = "otp2" name="otp2" maxlength="1" oninput="move(this, 'otp3')" required>
                            <input type="text" class="otp" id = "otp3" name="otp3" maxlength="1" oninput="move(this, 'otp4')" required>
                            <input type="text" class="otp" id = "otp4" name="otp4" maxlength="1" oninput="move(this, 'otp5')" required>
                            <input type="text" class="otp" id = "otp5" name="otp5" maxlength="1" oninput="move(this, 'otp6')" required>
                            <input type="text" class="otp" id = "otp6" name="otp6" maxlength="1" required>
                        </div>

                        <div class= "btn">
                            <button type="submit" id="verify_otp" name="verify_otp">CONTINUE</button>
                            <button type="submit" id="resend_otp" name="resend_otp">SEND A NEW OTP</button>
                        </div>
                    </form>
                </div>
            <?php if ($error_message): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php endif; ?>
    </div>
</body>
</html>