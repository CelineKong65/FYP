<?php
session_start();

if (!isset($_SESSION['reset_email'])){
    header("Location: findback.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $enteredOTP = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];

    if($enteredOTP == $_SESSION['reset_otp']){
        //OTP Correct, continun with reset password
        header("Location: reset-password.php");
        exit();
    }else{
        echo "<script>alert('Invalid OTP. Please try again.');</script>";
    }
}
?>