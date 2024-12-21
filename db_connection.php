<?php
// Database configuration
$host = 'localhost';  // XAMPP default host
$dbname = 'ticketing_system';  // Your database name
$username = 'root';  // Default MySQL username in XAMPP
$password = '';  // Default is no password in XAMPP

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
