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
    <link rel='stylesheet' href='admin_login.css'>
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
                        <p><a href="findback_admin.php">Forgot your password?</a></p>
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
