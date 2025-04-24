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

.home{
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: row;
}

.home h2 {
    transition: 0.5s;
    font-size: medium;
    color: #000000;
    font-weight: 300;
    font-size: 18px;
}

.home a {
    transition: 0.5s;
    text-decoration: none;
}

.home a:hover, h2:hover{
    transition: color 0.5s;
    color: #007BFF;
    cursor: pointer;
}

.home i:hover{
    transition: color 0.5s;
    color: #007BFF;
    cursor: pointer;
}

.home i {
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
    padding-top: 0px;
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
    margin-bottom: 20px;
    color: #000;
}

.frame label {
    font-size: 16px;
    display: block;
    margin-bottom: 5px;
    color: #000;
}

.frame button {
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

.frame button:hover {
    transition: 0.5s;
    background-color: #007bff94;
}

.frame input {
    height: 35px;
    color: #000;
    max-width: 500px;
    width: 100%;
    padding: 6px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
}

.frame input:hover {
    border: 1px solid #4c5157;
    background-color: #f8f8f8;
}

.frame input:focus {
    background-color: #fff;
    color: #000;
    outline: none;
    border-color: #007BFF;
}

.frame input:not(:placeholder-shown) {
    background-color: #fff;
    color: #000;
    outline: none;
    border-color: #007BFF;
}

.wrapper{
    position: relative;
    display: inline-block;
    width: 100%;
}

.wrapper input{
    width: 100%;
    padding-right: 40px;
}

.pass-field {
    position: relative;
}

.pass-field .fa-eye,
.pass-field .fa-eye-slash {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-100%);
    cursor: pointer;
    color: #7f7f7f;
    background: none;
    border: none;
    padding: 0;
    font-size: 15px;
}

.pass-field .fa-eye:hover,
.pass-field .fa-eye-slash:hover {
    color: #505050;
}

.error-message {
    color: #d32f2f;
    background-color: #fde8e8;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
    font-size: 14px;
    border-left: 4px solid #d32f2f;
}

.error-field {
    border: 1px solid #d32f2f !important;
}

.findback{
    margin-top: 20px;
}

.findback h3, a{
    font-size: 11pt;
    font-weight: 600; 
}

.findback a{
    padding-top: 20px;
    color: #007BFF;   
}

.findback a:hover{
    color: #007bff94;
}
a{
    text-decoration: none;
}
</style>
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