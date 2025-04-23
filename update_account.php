<?php
session_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current customer data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT CustName, CustEmail, CustPhoneNum, StreetAddress, City, Postcode, State FROM customer WHERE CustID = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("Customer not found");
}

$errors = [];

// Handle AJAX request for real-time checking (same as register.php)
if (isset($_POST['check_availability'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $exists = false;
    $is_valid_format = true;
    $error_message = '';

    if ($type === 'username') {
        $sql = "SELECT CustName FROM customer WHERE CustName = ? AND CustID != ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$value, $user_id]);
        $exists = $stmt->rowCount() > 0;
    } elseif ($type === 'email') {
        if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $value)) {
            $is_valid_format = false;
            $error_message = "Invalid gmail format";
        } else {
            $sql = "SELECT CustEmail FROM customer WHERE CustEmail = ? AND CustID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$value, $user_id]);
            $exists = $stmt->rowCount() > 0;
        }
    } elseif ($type === 'phone') {
        if (!preg_match("/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/", $value)) {
            $is_valid_format = false;
            $error_message = "Invalid Malaysian phone number (e.g., 012-3456789)";
        } else {
            $sql = "SELECT CustPhoneNum FROM customer WHERE CustPhoneNum = ? AND CustID != ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$value, $user_id]);
            $exists = $stmt->rowCount() > 0;
        }
    }

    echo json_encode(['exists' => $exists, 'valid_format' => $is_valid_format, 'message' => $error_message]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custName = filter_input(INPUT_POST, 'custName', FILTER_SANITIZE_STRING);
    $custEmail = filter_input(INPUT_POST, 'custEmail', FILTER_SANITIZE_EMAIL);
    $custPhoneNum = filter_input(INPUT_POST, 'custPhoneNum', FILTER_SANITIZE_STRING);
    $streetAddress = filter_input(INPUT_POST, 'streetAddress', FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $postcode = filter_input(INPUT_POST, 'postcode', FILTER_SANITIZE_STRING);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate inputs
    if (empty($custName)) {
        $errors['custName'] = "Username is required";
    }
    if (empty($custEmail) || !filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['custEmail'] = "Invalid email format";
    }
    if (empty($custPhoneNum) || !preg_match("/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/", $custPhoneNum)) {
        $errors['custPhoneNum'] = "Invalid phone number format";
    }
    if (empty($streetAddress)) {
        $errors['streetAddress'] = "Street address is required";
    }
    if (empty($city)) {
        $errors['city'] = "City is required";
    }
    if (empty($postcode) || !preg_match("/^\d{5}$/", $postcode)) {
        $errors['postcode'] = "Invalid postcode";
    }
    if (empty($state)) {
        $errors['state'] = "Please select a state";
    }

    // Validate password if new password is provided
    if (!empty($newPassword)) {
        if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $newPassword)) {
            $errors['newPassword'] = "Password must follow requirements";
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirmPassword'] = "New password and confirm password do not match";
        }
    }

    // Check for existing username, email, phone (excluding current user's) - done via AJAX

    if (empty($errors)) {
        $sql = "UPDATE customer SET 
                CustName = ?, 
                CustEmail = ?, 
                CustPhoneNum = ?, 
                StreetAddress = ?, 
                City = ?, 
                Postcode = ?, 
                State = ?";
        $params = [$custName, $custEmail, $custPhoneNum, $streetAddress, $city, $postcode, $state];

        if (!empty($newPassword)) {
            $sql .= ", CustPassword = ?";
            $params[] = $newPassword; // You should hash this password before storing in a real application
        }

        $sql .= " WHERE CustID = ?";
        $params[] = $user_id;

        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $update_success = "Account updated successfully!";
            // Refresh customer data
            $stmt = $conn->prepare("SELECT CustName, CustEmail, CustPhoneNum, StreetAddress, City, Postcode, State FROM customer WHERE CustID = ?");
            $stmt->execute([$user_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $update_error = "Error updating account.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account</title>
    <link rel="stylesheet" type="text/css" href="register.css">
    <link rel="stylesheet" type="text/css" href="update_account.css">
    <script src="https://kit.fontawesome.com/c2f7d169d6.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="return">
            <a href="account.php"><i class="fa-solid fa-rotate-left"><h2>RETURN</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="image/Picture.png" alt="Side Picture">
        </div>
        <div class="right-side">
            <div class="right-side-inner">
                <div class="frame">
                    <h2>Update Account</h2>

                    <?php if (isset($update_success)): ?>
                        <div class="message success"><?= $update_success ?></div>
                    <?php endif; ?>
                    <?php if (isset($update_error)): ?>
                        <div class="message error"><?= $update_error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="update-form">
                        <label for="custName">Username:</label>
                        <input type="text" id="custName" name="custName" value="<?= htmlspecialchars($customer['CustName']) ?>" required>
                        <div class="error-message" id="custName-error">
                            <?php if (isset($errors['custName'])): ?>
                                <?= $errors['custName'] ?>
                            <?php endif; ?>
                        </div>
                        <br>

                        <label for="custEmail">Email:</label>
                        <input type="email" id="custEmail" name="custEmail" value="<?= htmlspecialchars($customer['CustEmail']) ?>" required>
                        <div class="error-message" id="custEmail-error">
                            <?php if (isset($errors['custEmail'])): ?>
                                <?= $errors['custEmail'] ?>
                            <?php endif; ?>
                        </div>
                        <br>

                        <label for="custPhoneNum">Phone Number:</label>
                        <input type="text" id="custPhoneNum" name="custPhoneNum" value="<?= htmlspecialchars($customer['CustPhoneNum']) ?>" required>
                        <div class="error-message" id="custPhoneNum-error">
                            <?php if (isset($errors['custPhoneNum'])): ?>
                                <?= $errors['custPhoneNum'] ?>
                            <?php endif; ?>
                        </div>
                        <br>

                        <label>New Password (optional):</label>
                        <div class="wrapper">
                            <div class="pass-field">
                                <input type="password" placeholder="Leave blank to keep current password" name="newPassword">
                                <i class="fa-solid fa-eye" id="show-new-password"></i>
                            </div>
                            <?php if (isset($errors['newPassword'])): ?>
                                <div class="error-message"><?= $errors['newPassword'] ?></div>
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
                        <br>

                        <label>Confirm New Password:</label>
                        <div class="pass-field">
                            <input type="password" name="confirmPassword">
                            <i class="fa-solid fa-eye" id="show-confirm-password"></i>
                        </div>
                        <?php if (isset($errors['confirmPassword'])): ?>
                            <div class="error-message"><?= $errors['confirmPassword'] ?></div>
                        <?php endif; ?>
                        <br>

                        <label>Street Address:</label>
                        <input type="text" name="streetAddress" value="<?= htmlspecialchars($customer['StreetAddress']) ?>" required>
                        <?php if (isset($errors['streetAddress'])): ?>
                            <div class="error-message"><?= $errors['streetAddress'] ?></div>
                        <?php endif; ?>
                        <br>

                        <label>City:</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($customer['City']) ?>" required><br>

                        <label>Postcode:</label>
                        <input type="text" name="postcode" value="<?= htmlspecialchars($customer['Postcode']) ?>" required>
                        <?php if (isset($errors['postcode'])): ?>
                            <div class="error-message"><?= $errors['postcode'] ?></div>
                        <?php endif; ?>
                        <br>

                        <label>State:</label>
                        <select name="state" class="state" required>
                            <option value="">Select State</option>
                            <?php
                            $states = [
                                "Johor", "Melaka", "Negeri Sembilan", "Kedah", "Kelantan",
                                "Pahang", "Penang", "Perak", "Perlis", "Sabah",
                                "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur", "Putrajaya"
                            ];
                            foreach ($states as $stateOption) {
                                $selected = ($customer['State'] == $stateOption) ? 'selected' : '';
                                echo "<option value=\"$stateOption\" $selected>$stateOption</option>";
                            }
                            ?>
                        </select><br>

                        <button type="submit" class="reg">Update Account</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="register.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.querySelector("#update-form input[name='newPassword'] ~ i");
            const confirmPasswordInput = document.querySelector("#update-form input[name='confirmPassword'] ~ i");

            if (newPasswordInput) {
                newPasswordInput.addEventListener("click", function() {
                    const input = this.previousElementSibling;
                    input.type = input.type === "password" ? "text" : "password";
                    this.className = `fa-solid fa-eye${input.type === "password" ? "" : "-slash"}`;
                });
            }

            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener("click", function() {
                    const input = this.previousElementSibling;
                    input.type = input.type === "password" ? "text" : "password";
                    this.className = `fa-solid fa-eye${input.type === "password" ? "" : "-slash"}`;
                });
            }

            const nameInput = document.getElementById('custName');
            const emailInput = document.getElementById('custEmail');
            const phoneInput = document.getElementById('custPhoneNum');

            if (nameInput) {
                nameInput.addEventListener('blur', function() {
                    checkAvailability('username', this.value, 'custName-error');
                });
            }

            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    checkAvailability('email', this.value, 'custEmail-error');
                });
            }

            if (phoneInput) {
                phoneInput.addEventListener('blur', function() {
                    checkAvailability('phone', this.value, 'custPhoneNum-error');
                });
            }

            function checkAvailability(type, value, errorId) {
                if (!value.trim()) {
                    document.getElementById(errorId).textContent = '';
                    return;
                }

                let isValidFormat = true;
                let errorMessage = '';

                if (type === 'email') {
                    const emailReq = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
                    if (!emailReq.test(value)) {
                        isValidFormat = false;
                        errorMessage = "Please enter a valid gmail address";
                    }
                } else if (type === 'phone') {
                    const phoneReq = /^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/;
                    if (!phoneReq.test(value)) {
                        isValidFormat = false;
                        errorMessage = "Please enter a valid Malaysian phone number (e.g., 012-3456789)";
                    }
                }

                if (!isValidFormat) {
                    document.getElementById(errorId).textContent = errorMessage;
                    document.getElementById(errorId).style.display = 'block';
                    document.getElementById(errorId.replace('-error', '')).classList.add('error');
                    return;
                } else {
                    document.getElementById(errorId).textContent = '';
                    document.getElementById(errorId).style.display = 'none';
                    document.getElementById(errorId.replace('-error', '')).classList.remove('error');
                }

                const formData = new FormData();
                formData.append('check_availability', 'true');
                formData.append('type', type);
                formData.append('value', value);

                fetch(window.location.href, { // Send request to the same update_account.php file
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        document.getElementById(errorId).textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} already exists`;
                        document.getElementById(errorId).style.display = 'block';
                        document.getElementById(errorId.replace('-error', '')).classList.add('error');
                    } else {
                        document.getElementById(errorId).textContent = '';
                        document.getElementById(errorId).style.display = 'none';
                        document.getElementById(errorId.replace('-error', '')).classList.remove('error');
                    }
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                });
            }
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>