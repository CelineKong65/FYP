<?php
session_start();
if (empty($_SESSION['show_loading'])) {
    header("Location: login.php");
    exit();
}
unset($_SESSION['show_loading']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting</title>
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            font-size: 1.2rem;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Login successful! Redirecting...</div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 1000);
    </script>
</body>
</html>