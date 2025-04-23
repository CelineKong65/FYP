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
    <link rel="stylesheet" href="admin_login.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="home">
            <a href="../index.php"><i class="fa-solid fa-house"><h2>HOME</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="../image/admin_back.png" alt="Side Picture">
        </div>
        <div class="right-side">
            <div class="right-side-inner">
                <div class="frame">
                    <h2>Admin Login</h2>
                    <?php if (isset($errors['general'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['general']); ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <label>Admin ID:</label>
                        <input type="text" name="admin_id" value="<?php echo htmlspecialchars($admin_id); ?>" 
                               class="<?php echo isset($errors['admin_id']) ? 'error-field' : ''; ?>"
                               placeholder="Enter your ID" required>
                        <?php if (isset($errors['admin_id'])): ?>
                            <div class="error-message"><?php echo $errors['admin_id']; ?></div>
                        <?php endif; ?>

                        <label>Username:</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" 
                               class="<?php echo isset($errors['username']) ? 'error-field' : ''; ?>"
                               placeholder="Enter your username" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="error-message"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>

                        <label>Password:</label>
                        <div class="wrapper">
                            <div class="pass-field">
                                <input type="password" name="admin_pass" 
                                       class="<?php echo isset($errors['password']) ? 'error-field' : ''; ?>"
                                       placeholder="Enter your password" required>
                                <i class="fa-solid fa-eye" id="show-password"></i>
                            </div>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>

                        <button type="submit">Continue</button>
                    </form>
                </div>
            
                <div class="findback">
                    <h3>Forgot your password?</h3>
                    <a href="findback_admin.php">Find back your password</a>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Toggle password visibility
        const showPassword = document.querySelector("#show-password");
        const passwordField = document.querySelector("input[name='admin_pass']");

        showPassword.addEventListener("click", function() {
            this.classList.toggle("fa-eye-slash");
            const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
            passwordField.setAttribute("type", type);
        });
    </script>
</body>
</html>