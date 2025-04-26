<?php
// Database configuration
$host = "localhost"; 
$dbname = "fyp";  
$username = "root";  
$password = ""; 

// Create PDO connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start secure session
session_start();

$errors = [];
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['admin_pass']);

    if (empty($email) || empty($password)) {
        $errors['general'] = "Both email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        try {
            $sql = "SELECT AdminID, AdminName, AdminEmail, AdminPassword FROM admin WHERE AdminEmail = :email LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);            
            $stmt->execute();

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                if ($password === $admin["AdminPassword"]) {
                    $_SESSION["AdminID"] = $admin["AdminID"];
                    $_SESSION["admin_email"] = $admin["AdminEmail"];
                    $_SESSION['show_loading'] = true;
                    header("Location: redirect_admin.php");
                    exit();
                } else {
                    $errors['password'] = "Invalid password.";
                }
            } else {
                $errors['general'] = "No admin account found with this email.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_login.css">
</head>
<body>
    <header>
        <div class="logo">
            <a href="../index.php"><img src="../image/logo.png" alt="Watersport Equipment Shop Logo"></a>
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
                        <div class="error-message"><?php echo htmlspecialchars($errors['general'], ENT_QUOTES); ?></div>
                    <?php endif; ?>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES); ?></div>
                    <?php endif; ?>

                    <?php if (isset($errors['password'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        
                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>" 
                               class="<?php echo isset($errors['email']) ? 'error-field' : ''; ?>"
                               placeholder="example: 123@gmail.com" required>
                               <?php if (isset($errors['email'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES); ?></div>
                                <?php endif; ?>

                        <label>Password:</label>
                        <div class="wrapper">
                            <div class="pass-field">
                                <input type="password" name="admin_pass" 
                                       class="<?php echo isset($errors['password']) ? 'error-field' : ''; ?>"
                                       placeholder="example: 123%abc" required minlength="8">
                                <i class="fa-solid fa-eye" id="show-password"></i>
                            </div>
                        </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            const showPassword = document.querySelector("#show-password");
            const passwordField = document.querySelector("input[name='admin_pass']");

            showPassword.addEventListener("click", function() {
                this.classList.toggle("fa-eye-slash");
                const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
                passwordField.setAttribute("type", type);
            });
        });
    </script>
</body>
</html>