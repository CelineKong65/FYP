<?php
include 'config.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custName = $_POST['custName'];
    $custEmail = $_POST['custEmail'];
    $custPassword = password_hash($_POST['custPassword'], PASSWORD_BCRYPT); // Hash password
    $custPhoneNum = $_POST['custPhoneNum'];
    $custAddress = $_POST['custAddress'];
    
    // Insert data into customer table
    $sql = "INSERT INTO customer (CustName, CustEmail, CustPassword, CustPhoneNum, CustAddress) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $custName, $custEmail, $custPassword, $custPhoneNum, $custAddress);
    
    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration</title>
</head>
<body>
    <h2>Register</h2>
    <form method="POST" action="">
        Name: <input type="text" name="custName" required><br>
        Email: <input type="email" name="custEmail" required><br>
        Password: <input type="password" name="custPassword" required><br>
        Phone Number: <input type="text" name="custPhoneNum"><br>
        Address: <textarea name="custAddress"></textarea><br>
        <input type="submit" value="Register">
    </form>
</body>
</html>
