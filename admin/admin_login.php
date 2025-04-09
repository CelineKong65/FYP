<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = trim($_POST['admin_id']);
    $username = trim($_POST['username']);
    $password = trim($_POST['admin_pass']);

    if (!empty($admin_id) && !empty($username) && !empty($password)) {
        $sql = "SELECT AdminID, AdminName, AdminPassword FROM admin WHERE AdminID = ? AND AdminName = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $admin_id, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();

            if ($password === $admin['AdminPassword']) {
                $_SESSION['AdminID'] = $admin['AdminID'];
                $_SESSION['admin_username'] = $admin['AdminName'];
                header("Location: dashboard.php");
                exit();
            } else {
                echo "<script>alert('Incorrect password.'); window.location.href='admin_login.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Invalid ID or username.'); window.location.href='admin_login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Please fill in all fields.'); window.location.href='admin_login.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    border-radius: 50px;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: url('../image/admin_back.jpg');
    background-size: cover;
}

.header
{
    display: flex;
    align-items: center;
}

img{
    height: 80px;
    transform: translateX(-3px);
    margin-right: 10px;
}

h3{
    font-size: 23pt;
    margin-left: 40px;
}

#login-form{
    padding: 70px;
    border-radius: 16px;
    width: 500px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 30px;
    height: 610px;
    background-color: white;
}

.input-box{
    margin: 30px 0;
}

.input-box input {
    background-color:#f8f8ff;;
    width: 100%;
    border: none;
    padding: 12px 12px 12px 45px;
    border-radius: 99px;
    font-size: 17px;
    font-weight: 600;
    margin-top: 10px;
}

.input-box input::placeholder {
    color: rgba(0, 0, 0, 0.342);
    font-size: 17px;
    font-weight: 500;
}

p{
    margin-top: 30px;
}

a{
    text-decoration: underline;
    font-style: italic;
}

.button {
    display: flex;
    justify-content: flex-end;
}

.forget_password
{
    align-items: center;
    margin: 30px;
}

p{
    text-align: center;
}

.Submit-btn{
    background-color: #4CAF50;
    color: white;
    padding: 10px 25px;
    border: none;
    border-radius:10px;
    font-size: 16px;
    cursor: pointer;
}

.back_div {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 10;
}

.back{
    background-color: white;
    color: black;
    padding: 10px 20px;
    border-color: transparent;
    border-radius: 10px;
    font-size: 15px;
}
    </style>
</head>
<body>
    <div class="back_div">
        <button name="back" class="back" onclick="window.location.href='../index.php'">< Back</button>
    </div>
    <div>
        <div id="login-title">
            <div id="login-form">
                <form name="loginfrm" method="post" class="Loginfrm" action="">
                    <div class="header">
                        <img src="../image/logo.png">
                        <h3>Admin Login</h3>
                    </div>
                    
                    <div class="input-box">
                        <label><b>Admin ID</b></label>
                        <input type="text" name="admin_id" placeholder="Enter your ID" required>
                    </div>

                    <div class="input-box">
                        <label><b>Username</b></label>
                        <input type="text" name="username" placeholder="Enter your username" required>
                    </div>

                    <div class="input-box">
                        <label><b>Password</b></label>
                        <input type="password" name="admin_pass" placeholder="Enter your password" required>
                    </div>

                    <div class="forget_password">
                        <p><a href="forget_password.html">Forgot your password?</a></p>
                    </div>

                    <div class="button">
                        <input type="submit" name="loginbtn" class="Submit-btn" value="LOGIN" />
                    </div>

                    <?php if (isset($error)) { ?>
                        <p style="color: red; text-align: center;"><?php echo $error; ?></p>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
