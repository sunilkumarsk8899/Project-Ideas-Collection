<?php
// db_config.php
// Database connection and session start.

// Start the session. This MUST be at the top of any file that needs session data.
session_start();

// Database credentials
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is blank. Change this if you set one.
$dbname = "school_portal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Don't echo sensitive errors on a live site.
    // For development, this is okay:
    die("Connection failed: " . $conn->connect_error);
} else {
    // Connection successful message
    echo "Connected successfully to the database.";
}

// Set charset to utf8mb4 for full UTF-8 support
$conn->set_charset("utf8mb4");

?>

