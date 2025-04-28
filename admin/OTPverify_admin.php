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
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

header {
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 30px;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}

.logo img {
    width: 80px;
    height: auto;
}

.return{
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: row;
}

.return h2 {
    transition: 0.5s;
    font-size: medium;
    color: #000000;
    font-weight: 300;
    font-size: 18px;
}

.return a {
    transition: 0.5s;
    text-decoration: none;
}

.return a:hover, h2:hover{
    transition: color 0.5s;
    color: #007BFF;
    cursor: pointer;
}

.return i:hover{
    transition: color 0.5s;
    color: #007BFF;
    cursor: pointer;
}

.return i {
    transition: 0.5s;
    font-size: 20pt;
    color: black;
    display: flex;
    align-items: center;
    gap: 6pt;
}

.container {
    display: flex;
    height: 100vh;
    align-items: flex-start;
    justify-content: center;
    padding-top: 15px;
}

.left-side {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: top;
    
}

.left-side img {
    max-width: 108.5%;
    height: auto;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.right-side {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding-top: 120px;
}

.right-side-inner{
    display: flex;
    align-items: flex-start;
    flex-direction: column;
}

.right-side-inner p{
    font-weight: 500;
    margin-bottom: 15px;
}

.right-side-inner h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #000;
}

.frame {
    max-width: 500px;
    width: 100%;
    padding-top: 30px;
}

.frame h4{
    font-weight: 600;
}

.otp-inputs{
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.otp-inputs .otp{
    transition: 0.3s;
    width: 72px;
    height: 50px;
    background-color: rgb(233, 233, 233);
    text-align: center;
    font-size: 18px;
    border: 1px solid #000;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.otp-inputs input:hover{
    transition: 0.3s;
    background-color: #007bffc4;
    border-color: #4c5157;
    outline: none;
}

.otp-inputs input:focus{
    background-color: #007BFF;
    border-color: #4c5157;
    outline: none;
}

.btn{
    display: flex;
    flex-direction: column;
    width: 500px;
    padding: 8px;
    margin-top: 20px;
    gap: 10px;
}

.btn #verify_otp:hover, .btn #resend_otp:hover{
    transition: 0.3s;
    background-color: #006adc;
    color: white;
}

.btn #verify_otp{
    border: none;
    color: white;
    background-color: #007BFF;
    font-weight: 500;
    border-radius: 5px;
    height: 40px;
    font-size: 16px;
    transition: 0.3s;
    cursor: pointer;
}

.btn #resend_otp{
    border: none;
    background-color: white;
    color: #007BFF;
    font-weight: 500;
    border-radius: 5px;
    height: 40px;
    font-size: 16px;
    transition: 0.3s;
    cursor: pointer;
}

.error{
    color: red;
    margin-top: 10px;
}

.success{
    color: green;
    margin-top: 10px;
}
    </style>
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
            <img src="../image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="return">
        <a onclick="history.back();"><i class="fa-solid fa-rotate-left"><h2>RETURN</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="../image/admin_back.png" alt="Side Picture">
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
            <?php elseif ($success_message): ?>
                <p class="success"><?php echo $success_message; ?></p>
            <?php endif ?>
    </div>
</body>
</html>