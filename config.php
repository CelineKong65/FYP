<?php
$host = "localhost";  // Database server
$dbname = "fyp";      // Name of the database
$username = "root";   // Database username
$password = "";       // Database password

// Attempt to create a new PDO database connection
try {
    // Create PDO instance with MySQL connection, database name, and UTF-8 charset
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, display the error and stop the script
    die("Connection failed: " . $e->getMessage());
}
?>
