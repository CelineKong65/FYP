<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Our Website</title>
</head>
<body>
    <h2>Welcome to Our Website</h2>
    
    <?php if (isset($_SESSION["username"])): ?>
        <p>Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?>! <a href="logout.php">Logout</a></p>
    <?php else: ?>
        <p><a href="login.php">Login</a> | <a href="register.php">Register</a></p>
    <?php endif; ?>
</body>
</html>
