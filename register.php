<?php
include 'config.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custName = $_POST['custName'];
    $custEmail = $_POST['custEmail'];
    $custPassword = $_POST['custPassword'];
    $custPhoneNum = $_POST['custPhoneNum'];
    $custAddress = $_POST['custAddress'];

    // Validate password format
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])\S{8,}$/", $custPassword)) {
        echo "<script>alert('Password must be at least 8 characters long, include at least one letter, one number, and one special character, and no spaces.'); history.back();</script>";
        exit();
    }

    // Hash password
    $custPassword = password_hash($custPassword, PASSWORD_BCRYPT);

    // Check if email, phone number, or address already exists
    $sql_check = "SELECT CustName, CustEmail, CustPhoneNum, CustAddress FROM customer WHERE CustName = ? OR CustEmail = ? OR CustPhoneNum = ? OR CustAddress = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$custName, $custEmail, $custPhoneNum, $custAddress]);
    $result = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) > 0) {
        foreach ($result as $row) {
            if ($row['CustName'] == $custName) {
                echo "<script>alert('Name already exists.'); history.back();</script>";
            } elseif ($row["CustEmail"] == $custEmail) {
                echo "<script>alert('Email already exists.'); history.back();</script>";
            } elseif ($row["CustPhoneNum"] == $custPhoneNum) {
                echo "<script>alert('Phone Number already exists.'); history.back();</script>";
            } elseif ($row["CustAddress"] == $custAddress) {
                echo "<script>alert('Address already exists.'); history.back();</script>";
            }
        }
        exit();
    }
    
    // Insert data into customer table
    $sql = "INSERT INTO customer (CustName, CustEmail, CustPassword, CustPhoneNum, CustAddress) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $custName, $custEmail, $custPassword, $custPhoneNum, $custAddress);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');
        history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration</title>
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
                    <h2>Register</h2>
                    <form method="POST" action="/register" id="register-form">
                        <label>Name:</label>
                        <input type="text" placeholder="Username" name="custName" required><br>
                        
                        <label>Email:</label>
                        <input type="email" placeholder="123abc@gmail.com" name="custEmail" required><br>
                        
                        <label>Password:</label>
                        <div class="wrapper">
                            <div class="pass-field">
                                <input type="password" placeholder="123$abcd" name="custPassword" required><br>
                                <i class="fa-solid fa-eye" id="show-password"></i>
                            </div>

                            <!-- Password Requirement -->
                            <div class="content">
                                <p>Password minimum requirements</p>
                                <ul class="password-req">
                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 8 characters</span>
                                    </li>

                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 uppercase letter [A...Z]</span>
                                    </li>

                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 lowercase letter [a...z]</span>
                                    </li>

                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>At least 1 special symbol [!...$]</span>
                                    </li>

                                    <li>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>No spaces</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <label>Phone Number:</label>
                        <input type="text" placeholder="012-345 6789" name="custPhoneNum"><br>
                        
                        <label>Address:</label>
                        <textarea name="custAddress" placeholder="4, Jalan Melodies 8, Taman Rainbow, 80100, Johor bahru, Johor" rows="4" cols="69"></textarea><br>
                        
                        <button type="submit" class="reg">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="register.js"></script>
</body>
</html>
