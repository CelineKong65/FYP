<?php

session_start();

if(!isset($_SESSION['reset_email'])){
    header("Location: findback_admin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        //Update password
        include 'db_connection.php';
        $sql = "UPDATE admin SET AdminPassword = ? WHERE AdminEmail = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_password, $_SESSION['reset_email']]);

        //Clear session
        session_unset();
        session_destroy();

        echo "<script>alert('Password updated successfully.'); window.location.href='admin_login.php';</script>";
    } else {
        echo "<script>alert('Password do not match. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <link rel="stylesheet" type="text/css" href="reset_password_admin.css">
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
            <h2>Reset Password</h2>
            <div class="frame">
                <h2>Your Email:</h2>
                <form method="POST" action="">
                    <label>Email: <?php echo $_SESSION['reset_email']; ?> </label>
                    <label>New Password:</label>
                    <input style="text" name="new_password" placeholder="Enter your new password" required>
                    <label>Confirm Password:</label>
                    <input style="text" name="confirm_password" placeholder="Enter again your new password" required>
                    <button type="submit" class="sub">DONE</button>
                </form>
            </div>
        </div>
    </section>
</body>
</html>