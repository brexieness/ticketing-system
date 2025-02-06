<?php
session_start();
require 'db_connection.php';

// Debugging: Display incoming POST data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check if POST data exists
    if (!isset($_POST['username'], $_POST['password'], $_POST['role'])) {
        echo "Form data missing!";
        exit;
    }

    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Debugging: Print received values
    echo "Username: $username <br>";
    echo "Password: $password <br>";
    echo "Role: $role <br>";

    // Prepare the query
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
    $stmt->execute(['username' => $username, 'role' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify the password
        if (password_verify($password, $user['password'])) {
            echo "✅ Password verified! Redirecting...";
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Stop debugging and redirect
            if ($role === 'admin') {
                header('Location: admin_dashboard.php');
            } elseif ($role === 'cashier') {
                header('Location: cashier_dashboard.php');
            }
            exit;
        } else {
            echo "❌ Password incorrect!";
        }
    } else {
        echo "❌ User not found!";
    }
}
?>
