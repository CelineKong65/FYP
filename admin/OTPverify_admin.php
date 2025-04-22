<?php
session_start();

include 'db_connection.php';

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(!isset($_SESSION['reset_email'])) {
    header("Location: findback_admin.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$error_message = "";
$success_message = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_otp'])) {
        $enteredOTP = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];

        $sql = "SELECT Token FROM password_reset_admin WHERE AdminEmail = ? AND created_at >= NOW() - INTERVAL 10 MINUTE ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_SESSION['reset_email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($row && $enteredOTP == $row['Token']){
            header("Location: reset_password_admin.php");
            exit();
        }else{
            $error_message = "Invalid OTP. Please try again";
        }
    } elseif (isset($_POST['resend_otp'])){
        //Generate a new OTP
        $newOTP = rand(100000, 999999);
        //Resend OTP 
        $sql = "UPDATE password_reset_admin SET Token = ?, created_at = NOW() WHERE AdminEmail = ? ORDER BY created_at DESC LIMIT 1";
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
    
                $mail->Body = "Your OTP is: <b>$newOTP</b>";
    
                $mail->send();
                $success_message = "OTP resent successfully.";
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
    <link rel="stylesheet" type="text/css" href="OTPverify_admin.css">
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
            <img src="../image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="back_div">
            <button name="back" class="back" onclick="window.location.href='admin_login.php'">BACK</button>
        </div>
    </header>

    <section class="container">
        <div class="main-content">
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
            <?php if ($error_message): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php elseif ($success_message): ?>
                <p class="success"><?php echo $success_message; ?></p>
            <?php endif ?>
            </div>
        </div>
    </section>
</body>
</html>