<?php
session_start();
include 'config.php'; // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo "Both email and password are required.";
    } else {
        // Fetch user data from database
        $sql = "SELECT CustID, CustName, CustPassword FROM customer WHERE CustEmail = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("SQL error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = $id;
                $_SESSION["username"] = $username;
                echo "Login successful. Redirecting...";
                header("refresh:2; url=index.php"); // Redirect to dashboard after login
                exit();
            } else {
                echo "Invalid email or password.";
            }
        } else {
            echo "No account found with this email.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="login.css">
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Watersport Equipment Shop Logo">
        </div>
        <div class="home">
            <i class="fa fa-home" aria-hidden="true"></i>
            <a href="index.php"><h2>HOME</h2></a>
        </div>
    </header>

    <section class="box">
        <div class="frame">
        <h2>Log in</h2>
            <form method="post" action="">
                <label>Email:</label>
                <input type="email" placeholder="example: 123@gmail.com" name="email" required><br>

                <label>Password:</label>
                <input type="password" placeholder="example: 123%abc" name="password" required><br>

                <button type="submit">Login</button>
            </form>
        </div>
    </section>
</body>
</html>
