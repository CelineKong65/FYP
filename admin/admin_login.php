<?php

$host = "localhost"; 
$dbname = "fyp";  
$username = "root";  
$password = ""; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();

$errors = [];
$admin_id = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = trim($_POST['admin_id']);
    $username = trim($_POST['username']);
    $password = trim($_POST['admin_pass']);

    // Validate each field
    if (empty($admin_id)) {
        $errors['admin_id'] = "Admin ID is required";
    }
    if (empty($username)) {
        $errors['username'] = "Username is required";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    if (empty($errors)) {
        try {
            $sql = "SELECT AdminID, AdminName, AdminPassword FROM admin WHERE AdminID = :admin_id AND AdminName = :username LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":admin_id", $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->execute();

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                if ($password === $admin["AdminPassword"]) {
                    $_SESSION["AdminID"] = $admin["AdminID"];
                    $_SESSION["admin_username"] = $admin["AdminName"];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $errors['password'] = "Incorrect password";
                }
            } else {
                $errors['general'] = "Invalid Admin ID or username";
            }
        } catch (PDOException $e) {
            $errors['general'] = "System error. Please try again later.";
        }
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
                        <input type="text" name="admin_id" value="<?php echo htmlspecialchars($admin_id); ?>" placeholder="Enter your ID" required
                               class="<?php echo isset($errors['admin_id']) ? 'error-field' : ''; ?>">
                        <?php if (isset($errors['admin_id'])): ?>
                            <div class="error-message"><?php echo $errors['admin_id']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="input-box">
                        <label><b>Username</b></label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username" required
                               class="<?php echo isset($errors['username']) ? 'error-field' : ''; ?>">
                        <?php if (isset($errors['username'])): ?>
                            <div class="error-message"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="input-box">
                        <label><b>Password</b></label>
                        <input type="password" name="admin_pass" placeholder="Enter your password" required
                               class="<?php echo isset($errors['password']) ? 'error-field' : ''; ?>">
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($errors['general'])): ?>
                        <p style="color: red; text-align: center; font-weight: bold;"><?php echo $errors['general']; ?></p>
                    <?php endif; ?>

                    <div class="forget_password">
                        <p><a href="findback_admin.php">Forgot your password?</a></p>
                    </div>

                    <div class="button">
                        <input type="submit" name="loginbtn" class="Submit-btn" value="LOGIN" />
                    </div>

                </form>
            </div>
        </div>
    </div>
</body>
</html>