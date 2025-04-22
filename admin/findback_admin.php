<?php
session_start();

include 'db_connection.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    //Check is email exist in database
    $sql = "SELECT AdminID, AdminEmail FROM admin WHERE AdminEmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if($admin) {
        //Generate OTP
        $otp = rand(100000, 999999);
   
        // Save new OTP
        $sql = "INSERT INTO password_reset_admin (AdminID, AdminEmail, Token, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$admin['AdminID'], $admin['AdminEmail'], $otp]);

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

            $mail->setFrom('lawrencetan20050429@gmail.com', 'Water_Sport');//Your Gmail

            $mail->addAddress($email);

            $mail->isHTML(true);

            $mail->Subject = 'Password Reset OTP';

            $mail->Body = "Your OTP is: <b>$otp</b>";

            $mail->send();
            $_SESSION['reset_email'] = $email;
            header("Location: OTPverify_admin.php");
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
    <link rel="stylesheet" type="text/css" href="findback_admin.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
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
            <h2>FindBack Your Account</h2>
            <p>Enter your email address to receive an OTP for password reset.</p>
            <div class="frame">
                <h2>Enter Your Email</h2>
                    <form method="POST" action="">
                        <label>Email:</label>
                        <input type="email" name="email" placeholder="Enter your email address" required>
                        <button type="submit" class="sub">SEND OTP</button>
                    </form>
                    <?php if ($error_message): ?>
                        <p class="error"><?php echo $error_message; ?></p>
                    <?php endif; ?>
            </div>
        </div>
    </section>
</body>
</html>