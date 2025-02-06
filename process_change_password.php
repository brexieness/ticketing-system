<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

// Get the current password, new password, and confirm password from the form
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate that the new password matches the confirm password
if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "The new passwords do not match.";
    header('Location: change_password.php');
    exit();
}

// Fetch the user's current password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the current password matches the stored password
if (password_verify($current_password, $user['password'])) {
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $update_stmt = $conn->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
    $update_stmt->execute(['password' => $hashed_password, 'user_id' => $_SESSION['user_id']]);

    // Set a success message and redirect back to the change password page
    $_SESSION['success'] = 'Your password has been changed successfully!';
    header('Location: change_password.php');
    exit();
} else {
    // If current password is incorrect, show an error message
    $_SESSION['error'] = "Current password is incorrect.";
    header('Location: change_password.php');
    exit();
}
?>
