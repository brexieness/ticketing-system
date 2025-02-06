<?php
require 'db_connection.php';

$new_password = 'adminpass'; // New password for admin
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password in the database
$stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = 'admin1'");
$stmt->execute(['password' => $hashed_password]);

echo "Admin password updated successfully!";
?>
