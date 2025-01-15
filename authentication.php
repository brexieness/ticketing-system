<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // Raw password entered by the user
    $role = $_POST['role']; // Role selected in the login form

    // Query to check the user based on both username and role
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
    $stmt->execute(['username' => $username, 'role' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify the password using password_verify
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
                exit;
            } else if ($user['role'] === 'cashier') {
                header('Location: cashier_dashboard.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid username or password.";
            header('Location: login.php'); // Redirect to login page with error message
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid username or password or wrong role.";
        header('Location: login.php'); // Redirect to login page with error message
        exit;
    }
}
?>
