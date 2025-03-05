<?php
session_start();

// Database connection
$host = '127.0.0.1';
$db = 'fyp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if form data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productID = $_POST['productID'] ?? null;
    $selectedColor = $_POST['selectedColor'] ?? null;
    $selectedSize = $_POST['selectedSize'] ?? null;
    $quantity = $_POST['quantity'] ?? null;

    if (!$productID || !$quantity) {
        die("Error: Missing product ID or quantity.");
    }

    // Insert into cart table
    $stmt = $pdo->prepare("INSERT INTO cart (ProductID, Color, Size, Quantity) 
        VALUES (:productID, :color, :size, :quantity)");
    $stmt->execute([
        'productID' => $productID,
        'color' => $selectedColor,
        'size' => $selectedSize,
        'quantity' => $quantity
    ]);

    // Redirect to cart page
    header("Location: view_cart.php");
    exit;
} else {
    die("Invalid request.");
}
