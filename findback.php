<?
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $email= &$_POST['email'];

    $sql = "SELECT CustEmail FROM customer WHERE CustEmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        //Generate random 6 code
        $code = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;

        //Send the code to user email
        include 'send-email.php';

        header("Location: verify.php");
        exit();
    }else{
        echo "<script>alert('Email not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" type="text/css" href="findback.css">
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
                    <h2>Forgot Password</h2>
                    <p>In the prepared box, type your current email address.<br>The code will be sent to your email soon.</p>
                    <form method="POST" action="">
                        <label>Enter your email:</label>
                        <input type="email" name="email" placeholder="example@email.com" required>
                        <button type="submit" class="sub">SUBMIT</button>
                    </form>
                </div>
            </div> 
        </div>
    </section>
</body>
</html>