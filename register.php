<?php
session_start();
include 'config.php';

$errors = [];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Handle AJAX request for real-time checking
if (isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'username') {
        $sql = "SELECT CustName FROM customer WHERE CustName = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$value]);
        $exists = $stmt->rowCount() > 0;
    } elseif ($type === 'email') {
        if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $value)) {
            $is_valid_format = false;
            $error_message = "Invalid gmail format";
        } else {
            $sql = "SELECT CustEmail FROM customer WHERE CustEmail = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$value]);
            $exists = $stmt->rowCount() > 0;
        }
    } elseif ($type === 'phone') {
        if (!preg_match("/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/", $value)) {
            $is_valid_format = false;
            $error_message = "Invalid Malaysian phone number (e.g., 012-3456789)";
        } else {
            $sql = "SELECT CustPhoneNum FROM customer WHERE CustPhoneNum = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$value]);
            $exists = $stmt->rowCount() > 0;
        }
    }

    echo json_encode(['exists' => $exists, 'valid_format' => $is_valid_format, 'message' => $error_message]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 1) {
    // Sanitize other inputs (excluding those checked in real-time)
    $custPassword = $_POST['custPassword'];
    $custName = $_POST['custName'];
    $custEmail = $_POST['custEmail'];
    $custPhoneNum = $_POST['custPhoneNum'];

    // Validate password format (still needs server-side check)
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $custPassword)) {
        $errors['custPassword'] = "Password must follow requirements";
    }

    // If no immediate errors, store in session and proceed to step 2
    if (empty($errors) && !isset($_SESSION['register_errors'])) {
        $_SESSION['register_data'] = [
            'custName' => $custName,
            'custEmail' => $custEmail,
            'custPassword' => $custPassword,
            'custPhoneNum' => $custPhoneNum
        ];
        header("Location: register.php?step=2");
        exit();
    } elseif (isset($_SESSION['register_errors'])) {
        $errors = array_merge($errors, $_SESSION['register_errors']);
        unset($_SESSION['register_errors']); // Clear session errors
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration - Step <?php echo $step; ?></title>
    <link rel="stylesheet" type="text/css" href="register.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="return">
            <a onclick="history.back();"><i class="fa-solid fa-rotate-left"><h2>RETURN</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="image/Picture.png" alt="Side Picture">
        </div>
        <div class="right-side">
            <div class="right-side-inner">
                <div class="frame">
                    <h2>Register - Step <?php echo $step; ?></h2>

                    <?php if ($step == 1): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?step=1" id="register-form">
                            <label for="custName">Name:</label>
                            <input type="text" id="custName" placeholder="Username" name="custName" value="<?php echo isset($_POST['custName']) ? htmlspecialchars($_POST['custName']) : ''; ?>" required>
                            <div class="error-message" id="custName-error">
                                <?php if (isset($errors['custName'])): ?>
                                    <?php echo $errors['custName']; ?>
                                <?php endif; ?>
                            </div>
                            <br>

                            <label for="custEmail">Email:</label>
                            <input type="email" id="custEmail" placeholder="123abc@gmail.com" name="custEmail" value="<?php echo isset($_POST['custEmail']) ? htmlspecialchars($_POST['custEmail']) : ''; ?>" required>
                            <div class="error-message" id="custEmail-error">
                                <?php if (isset($errors['custEmail'])): ?>
                                    <?php echo $errors['custEmail']; ?>
                                <?php endif; ?>
                            </div>
                            <br>

                            <label>Password:</label>
                            <div class="wrapper">
                                <div class="pass-field">
                                    <input type="password" placeholder="123$abcd" name="custPassword" required>
                                    <i class="fa-solid fa-eye" id="show-password"></i>
                                </div>
                                <?php if (isset($errors['custPassword'])): ?>
                                    <div class="error-message"><?php echo $errors['custPassword']; ?></div>
                                <?php endif; ?>

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

                            <label for="custPhoneNum">Phone Number:</label>
                            <input type="text" id="custPhoneNum" placeholder="012-345 6789" name="custPhoneNum" value="<?php echo isset($_POST['custPhoneNum']) ? htmlspecialchars($_POST['custPhoneNum']) : ''; ?>" required>
                            <div class="error-message" id="custPhoneNum-error">
                                <?php if (isset($errors['custPhoneNum'])): ?>
                                    <?php echo $errors['custPhoneNum']; ?>
                                <?php endif; ?>
                            </div>
                            <br>

                            <button type="submit" class="reg">Continue with Address</button>
                        </form>
                    <?php else: ?>
                        <?php include 'register_address.php'; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="register.js"></script>
</body>
</html>