<?php
include 'config.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custName = $_POST['custName'];
    $custEmail = $_POST['custEmail'];
    $custPassword = $_POST['custPassword'];
    $custPhoneNum = $_POST['custPhoneNum'];
    $custAddress = $_POST['custAddress'];

    // Validate password format
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/", $custPassword)) {
        echo "<script>alert('Password must be at least 8 characters long, include at least one letter, one number, and one special character.');
        history.back();
        </script>";
        exit();
    }

    // Hash password
    $custPassword = password_hash($custPassword, PASSWORD_BCRYPT);

    // Check if email, phone number, or address already exists
    $sql_check = "SELECT CustName, CustEmail, CustPhoneNum, CustAddress FROM customer WHERE CustName = ? OR CustEmail = ? OR CustPhoneNum = ? OR CustAddress = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssss", $custName, $custEmail, $custPhoneNum, $custAddress);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->bind_result($existingName, $existingEmail, $existingPhoneNum, $existingAddress);
        $stmt_check->fetch();

        if ($existingName == $custName){
            echo "<script>alert('Name already exists.');
            history.back();</script>";
        }elseif ($existingEmail == $custEmail) {
            echo "<script>alert('Email already exists.');
            history.back();</script>";
        } elseif ($existingPhoneNum == $custPhoneNum) {
            echo "<script>alert('Phone number already exists.');
            history.back();</script>";
        } elseif ($existingAddress == $custAddress) {
            echo "<script>alert('Address already exists.');
            history.back();</script>";
        }

        $stmt_check->close();
        $conn->close();
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="return">
        <a onclick="history.back();"><i class='bx bxs-left-arrow'><h2>RETURN</h2></i></a>
        </div>
    </header>

    <section class="container">
        <div class="left-side">
            <img src="Picture.png" alt="Side Picture">
        </div>
        <div class="right-side">
            <div class="right-side-inner">
                <div class="frame">
                    <h2>Register</h2>
                    <form method="POST" action="">
                        <label>Name:</label>
                        <input type="text" placeholder="Username" name="custName" required><br>
                        
                        <label>Email:</label>
                        <input type="email" placeholder="123abc@gmail.com" name="custEmail" required><br>
                        
                        <label>Password:</label>
                        <div class="wrapper">
                            <input type="password" placeholder="123$abcd" name="custPassword" required>
                            <button type="button" id="togglePassword" class="eye-icon">
                                <i class='bx bxs-hide'></i>
                            </button>
                        </div><br>

                        <!-- Password Requirement -->
                         <div class="password-req">
                            <div class="req" id="req-uppercase">
                                <span class="circle"></span>
                                1 uppercase letter
                            </div>

                            <div class="req" id="req-lowercase">
                                <span class="circle"></span>
                                1 lowercase letter
                            </div>

                            <div class="req" id="req-space">
                                <span class="circle"></span>
                                No spaces
                            </div>

                            <div class="req" id="req-length">
                                <span class="circle"></span>
                                At least 8 characters
                            </div>
                         </div>


                        <label>Phone Number:</label>
                        <input type="text" placeholder="012-345 6789" name="custPhoneNum"><br>
                        
                        <label>Address:</label>
                        <textarea name="custAddress" placeholder="4, Jalan Melodies 8, Taman Rainbow, 80100, Johor bahru, Johor" row="4" cols="69"></textarea><br>
                        
                        <button type="submit" class="reg">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="register.js"></script>
</body>
</html>
