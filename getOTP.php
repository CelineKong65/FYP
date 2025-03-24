<?php
session_start();

    if (isset($_SESSION['reset_OTP'])) {
        echo json_encode(['otp' => $_SESSION['reset_otp']]);
    }else{
        echo json_encode(['otp' => null]);
    }
?>