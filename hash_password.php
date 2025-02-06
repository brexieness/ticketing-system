<?php
require 'db_connection.php';

$new_password = 'cashier123'; // Change this to any new password you want
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password in the database
$stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = 'cashier1'");
$stmt->execute(['password' => $hashed_password]);

echo "Password updated successfully!";
?>
