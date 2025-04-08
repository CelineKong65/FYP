<?php
session_start();
include 'config.php'; 

$error = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo "Both email and password are required.";
    } else {
        try {
            $sql = "SELECT CustID, CustName, CustPassword FROM customer WHERE CustEmail = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($password === $user["CustPassword"]) {
                    $_SESSION["user_id"] = $user["CustID"];
                    $_SESSION["username"] = $user["CustName"];

                    echo "Login successful. Redirecting...";
                    header("refresh:2; url=index.php"); 
                    exit();
                } else {
                    echo "Invalid password.";
                }
            } else {
                echo "No account found with this email.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="login.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="home">
        <a href="index.php"><i class="fa-solid fa-house"><h2>HOME</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="image/Picture.png" alt="Side Picture">
        </div>
        <div class="right-side">
            <div class="right-side-inner">
                <div class="frame">
                    <h2>Log in</h2>
                    <form method="post" action="">
                        <label>Email:</label>
                        <input type="email" placeholder="example: 123@gmail.com" name="email" value="<?=htmlspecialchars($email) ?>" required><br>
    
                        <label>Password:</label>
                        <div class="wrapper">
                            <div class="pass-field">
                                <input type="password" placeholder="example: 123%abc" name="password" required><br>
                                <i class="fa-solid fa-eye" id="show-password"></i>
                                <?php if (!empty($error) && isset($user)): ?>
                                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit">Continue</button>
                    </form>
                </div>
            
                <div class="option">
                    <h4>Or continue with</h4>
                    <div class="social-icon">
                        <img src="image/facebook.png" alt="facebook">
                        <img src="image/google.png" alt="google">
                    </div>
                </div>
                <section class="bar">
                    <div class="register">
                        <h3>Don't have an account?</h3>
                        <a href="register.php">Create account</a>
                    </div>
                    <div class="findback">
                        <h3>Forgot you account or password?</h3>
                        <a href="findback.php">Find back your account</a>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <script src="login.js"></script>
</body>
</html>
