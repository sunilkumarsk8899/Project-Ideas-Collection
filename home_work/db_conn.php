<?php
// db_connect.php
// Database configuration
$servername = "localhost"; // Or "127.0.0.1"
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password
$dbname = "homework_db";   // The name you give your database in phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully"; // Uncomment to test connection
?>
