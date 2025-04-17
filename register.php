<?php
session_start();
include 'config.php';

$errors = [];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 1) {
    // Sanitize inputs
    $custName = $_POST['custName'];
    $custEmail = $_POST['custEmail'];
    $custPassword = $_POST['custPassword'];
    $custPhoneNum = $_POST['custPhoneNum'];

    // Validate gmail format
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $custEmail)) {
        $errors['custEmail'] = "Please use a valid gmail address (e.g. example@gmail.com)";
    }

    // Validate password format
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $custPassword)) {
        $errors['custPassword'] = "Password must follow requirements";
    }

    // Check if gmail or phone number already exists
    $sql_check = "SELECT CustEmail, CustPhoneNum FROM customer WHERE CustEmail = ? OR CustPhoneNum = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$custEmail, $custPhoneNum]);
    $result = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) > 0) {
        foreach ($result as $row) {
            if ($row["CustName"] == $custName){
                $errors['custName'] == "Username already exists";
            }

            if ($row["CustEmail"] == $custEmail) {
                $errors['custEmail'] = "Email already exists";
            }

            if ($row["CustPhoneNum"] == $custPhoneNum) {
                $errors['custPhoneNum'] = "Phone Number already exists";
            }
        }
    }

    // If no errors, store in session and proceed to step 2
    if (empty($errors)) {
        $_SESSION['register_data'] = [
            'custName' => $custName,
            'custEmail' => $custEmail,
            'custPassword' => $custPassword,
            'custPhoneNum' => $custPhoneNum
        ];
        header("Location: register.php?step=2");
        exit();
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
                            <!-- Basic Info Fields -->
                            <label>Name:</label>
                            <input type="text" placeholder="Username" name="custName" value="<?php echo isset($_POST['custName']) ? htmlspecialchars($_POST['custName']) : ''; ?>" required>
                            <?php if (isset($errors['custName'])): ?>
                                <div class="error-message"><?php echo $errors['custName']; ?></div>
                            <?php endif; ?>
                            <br>

                            <label>Email:</label>
                            <input type="email" placeholder="123abc@gmail.com" name="custEmail" value="<?php echo isset($_POST['custEmail']) ? htmlspecialchars($_POST['custEmail']) : ''; ?>" required>
                            <?php if (isset($errors['custEmail'])): ?>
                                <div class="error-message"><?php echo $errors['custEmail']; ?></div>
                            <?php endif; ?>
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

                            <label>Phone Number:</label>
                            <input type="text" placeholder="012-345 6789" name="custPhoneNum" value="<?php echo isset($_POST['custPhoneNum']) ? htmlspecialchars($_POST['custPhoneNum']) : ''; ?>" required>
                            <?php if (isset($errors['custPhoneNum'])): ?>
                                <div class="error-message"><?php echo $errors['custPhoneNum']; ?></div>
                            <?php endif; ?>
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