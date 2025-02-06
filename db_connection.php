<?php
$host = "localhost";
$dbname = "ticketing_system"; // Ensure this matches your database name
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty by default)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
