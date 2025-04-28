<?php
session_start();

if(!isset($_SESSION['reset_email'])){
    header("Location: findback.php");
    exit();
}

// Get current password for comparison
include 'db_connection.php';
$sql = "SELECT AdminPassword FROM admin WHERE AdminEmail = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['reset_email']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$current_password = $row['AdminPassword'];

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if password matches current password
    if ($new_password === $current_password) {
        echo "<script>alert('New password cannot be the same as current password.');</script>";
    } 
    // Check if passwords match
    elseif ($new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match. Please try again.');</script>";
    }
    // Check password requirements
    elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $new_password)) {
        echo "<script>alert('Password does not meet requirements.');</script>";
    }
    else {
        // Update password
        $sql = "UPDATE admin SET AdminPassword = ? WHERE AdminEmail = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_password, $_SESSION['reset_email']]);

        // Clear session
        session_unset();
        session_destroy();

        echo "<script>alert('Password updated successfully.'); window.location.href='admin_login.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Reset Password</title>
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
    margin-top: 15px;
    color: #000;
}

.sub {
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

.sub:hover{
    transition: 0.5s;
    background-color: #007bff94;
}

.frame input{
    height: 35px;
    color: white;
    max-width: 500px;
    width: 100%;
    padding: 6px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
}

.frame input:hover {
    border: 1px solid #4c5157;
    background-color: #b3b0b0;
}

.frame input:focus {
    background-color: #007bff94;
    color: white;
    outline: none;
    border-color: #007BFF;
}

.frame input:not(:placeholder-shown) {
    background-color: #007bff94;
    color: white;
    outline: none;
    border-color: #007BFF;
}

.email{
    margin-top: 5px;
}

/* password requirement */
.password-req {
    margin-top: 10px;
    margin-bottom: 12px;
    font-size: 14px;
    color: #666;
}

.password-req li.valid span{
    color: #b3b0b0;
}

.content p{
    font-size: 12pt;
}

.content li{
    list-style: none;
    display: flex;
    margin-bottom: 2px;
}

.fa-circle {
    font-size: 10px;
    margin-right: 10px;
    display: flex;
    align-items: center;
    margin-bottom: 5px;
    margin-top: 3px;
}

.fa-circle-check{
    color: rgb(38, 255, 0);
    transform: translateY(-13%);
    font-size: 10px;
    margin-right: 10px;
    display: flex;
    align-items: center;
    margin-bottom: 5px;
    margin-top: 3px;
}

/* Wrapper */
.wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
    margin-bottom: 15px;
}

.wrapper input {
    width: 100%;
    padding-right: 35px;
    height: 35px;
    padding: 6px;
    border: 1px solid #ccc;
}

.wrapper .fa-eye,
.wrapper .fa-eye-slash {
    position: absolute;
    right: 10px;
    top: 10px; 
    cursor: pointer;
    color: #7f7f7f;
    background: none;
    border: none;
    padding: 0;
    font-size: 15px;
}

.wrapper .fa-eye:hover,
.wrapper .fa-eye-slash:hover {
    color: #505050;
}

.error-message {
    color: red;
    font-size: 0.8rem;
    margin-top: 2px;
    display: none;
}

input.error, select.error {
    border-color: red;
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
                <h2>Admin Reset Password</h2>
                <div class="frame">
                    <h2>Your Email:</h2>
                        <form method="POST" action="" id="reset-form">
                            <label>Email: <?php echo $_SESSION['reset_email']; ?> </label>
                            
                            <label>New Password:</label>
                            <div class="wrapper">
                                <div class="pass-field">
                                    <input type="password" id="new_password" name="new_password" placeholder="Enter your new password" required>
                                    <i class="fa-solid fa-eye" id="show-password"></i>
                                </div>
                                <div class="error-message" id="password-error"></div>
                                <div class="error-message" id="current-password-error"></div>

                                <div class="content">
                                    <p>Password minimum requirements</p>
                                    <ul class="password-req">
                                        <li><i class="fa-solid fa-circle"></i><span>At least 8 characters</span></li>
                                        <li><i class="fa-solid fa-circle"></i><span>At least 1 uppercase letter</span></li>
                                        <li><i class="fa-solid fa-circle"></i><span>At least 1 lowercase letter</span></li>
                                        <li><i class="fa-solid fa-circle"></i><span>At least 1 number</span></li>
                                        <li><i class="fa-solid fa-circle"></i><span>At least 1 special symbol</span></li>
                                        <li><i class="fa-solid fa-circle"></i><span>No spaces</span></li>
                                    </ul>
                                </div>
                            </div>

                            <label>Confirm Password:</label>
                            <div class="wrapper">
                                <div class="pass-field">
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your new password" required>
                                    <i class="fa-solid fa-eye" id="show-confirm-password"></i>
                                </div>
                                <div class="error-message" id="confirm-error"></div>
                            </div>
                            
                            <button type="submit" class="sub">DONE</button>
                        </form>
                </div>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById("new_password");
        const confirmInput = document.getElementById("confirm_password");
        const eyeIcon = document.getElementById("show-password");
        const confirmEyeIcon = document.getElementById("show-confirm-password");
        const requirementList = document.querySelectorAll(".password-req li");
        const form = document.getElementById("reset-form");
        const currentPasswordError = document.getElementById("current-password-error");
        
        // Current password check is handled server-side only
        // This is just for UI validation
        const currentPassword = ""; // We don't expose the current password to client-side

        const requirements = [
            {regex: /\S{8,}/, index: 0},
            {regex: /[A-Z]/, index: 1},
            {regex: /[a-z]/, index: 2},
            {regex: /\d/, index: 3},
            {regex: /[@$!%*#?&]/, index: 4},
            {regex: /^\S*$/, index: 5}
        ];

        // Password validation
        passwordInput.addEventListener("keyup", (e) => {
            // Check password requirements
            requirements.forEach(item => {
                const isValid = item.regex.test(e.target.value);
                const requirementItem = requirementList[item.index];
                
                requirementItem.firstElementChild.className = isValid ? 
                    "fa-solid fa-circle-check" : "fa-solid fa-circle";
                requirementItem.classList.toggle("valid", isValid);
            });
            
            // Check password match
            checkPasswordMatch();
        });

        // Confirm password validation
        confirmInput.addEventListener("keyup", checkPasswordMatch);

        function checkPasswordMatch() {
            const passwordError = document.getElementById("password-error");
            const confirmError = document.getElementById("confirm-error");
            
            if (passwordInput.value && !/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/.test(passwordInput.value)) {
                passwordError.textContent = "Password does not meet requirements";
                passwordError.style.display = "block";
            } else {
                passwordError.style.display = "none";
            }
            
            if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                confirmError.textContent = "Passwords do not match";
                confirmError.style.display = "block";
            } else {
                confirmError.style.display = "none";
            }
        }

        // Toggle password visibility
        eyeIcon.addEventListener("click", function() {
            togglePasswordVisibility(passwordInput, eyeIcon);
        });
        
        confirmEyeIcon.addEventListener("click", function() {
            togglePasswordVisibility(confirmInput, confirmEyeIcon);
        });

        function togglePasswordVisibility(inputElement, iconElement) {
            inputElement.type = inputElement.type === "password" ? "text" : "password";
            iconElement.className = `fa-solid fa-eye${inputElement.type === "password" ? "" : "-slash"}`;
        }

        // Form validation
        form.addEventListener("submit", function(e) {
            let isValid = true;
            
            // Check password requirements
            requirements.forEach(item => {
                if (!item.regex.test(passwordInput.value)) {
                    isValid = false;
                }
            });
            
            // Check password match
            if (passwordInput.value !== confirmInput.value) {
                document.getElementById("confirm-error").textContent = "Passwords do not match";
                document.getElementById("confirm-error").style.display = "block";
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>