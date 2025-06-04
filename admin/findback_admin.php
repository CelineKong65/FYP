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

            $mail->setFrom('lawrencetan20050429@gmail.com', 'KPL_WaterSport_Equipment');//Your Gmail

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
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
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

.frame {
    max-width: 500px;
    width: 100%;
    padding-top: 30px;
}

.frame h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #000;
}

.frame h4{
    margin-top: 10px;
    font-weight: 500;
}

.frame p{
    font-weight: 500;
    margin-bottom: 20px;
}

.frame label {
    font-size: 16px;
    display: block;
    margin-bottom: 5px;
    color: #000;
}

.frame input {
    height: 35px;
    color: white;
    max-width: 500px;
    width: 100%;
    padding: 6px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
}

.frame input:hover, textarea:hover {
    border: 1px solid #4c5157;
    background-color: #b3b0b0;
}

.frame input:focus, textarea:focus {
    background-color: #007bff94;
    color: white;
    outline: none;
    border-color: #007BFF;
}

.frame input:not(:placeholder-shown), textarea:not(:placeholder-shown) {
    background-color: #007bff94;
    color: white;
    outline: none;
    border-color: #007BFF;
}

.frame button{
    font-size: 18px;
    transition: 0.5s;
    color: #fff;
    width: 500px;
    padding: 8px;
    margin-top: 20px;
    border-radius: 10px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    background-color: #007BFF;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.frame button:hover{
    transition: 0.5s;
    background-color: #0060c7;
}

.error{
    color: red;
    margin-top: 10px;
}
    </style>
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