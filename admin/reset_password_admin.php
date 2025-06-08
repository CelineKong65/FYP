<?php
session_start();

if(!isset($_SESSION['reset_email'])){
    header("Location: findback.php");
    exit();
}

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
    <link rel="stylesheet" type="text/css" href="reset_password_admin.css">
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
        
        // Current password from PHP
        const currentPassword = "<?php echo $current_password; ?>"; 

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
            
            // Check if matches current password
            if (e.target.value === currentPassword) {
                currentPasswordError.textContent = "This password is the same as your current password";
                currentPasswordError.style.display = "block";
            } else {
                currentPasswordError.style.display = "none";
            }

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
            
            // Check if password matches current
            if (passwordInput.value === currentPassword) {
                currentPasswordError.textContent = "New password cannot be the same as current password";
                currentPasswordError.style.display = "block";
                isValid = false;
            }

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