<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

try {
    // Query to fetch all cashiers (username and password)
    $cashiers_stmt = $conn->query("SELECT user_id, username, password FROM users WHERE role = 'cashier'");
    $cashiers_list = $cashiers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Handle deletion of cashier
if (isset($_GET['delete_cashier_id'])) {
    $cashier_id = $_GET['delete_cashier_id'];
    try {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = :user_id AND role = 'cashier'");
        $delete_stmt->execute(['user_id' => $cashier_id]);
        header('Location: manage_cashiers.php'); // Redirect to avoid resubmission
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle adding new cashier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cashier'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // MD5 for password encryption (you might want to switch to a stronger method like password_hash)
    $role = 'cashier';

    try {
        // Insert the new cashier into the users table
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute(['username' => $username, 'password' => $password, 'role' => $role]);
        echo "<div class='alert alert-success'>Cashier account created successfully!</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cashiers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Manage Cashiers</h1>

        <!-- Button to trigger modal -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCashierModal">Add New Cashier</button>

        <!-- Cashiers List Table -->
        <h2>Cashier List</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Password (MD5)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cashiers_list as $cashier): ?>
                    <tr>
                        <td><?= $cashier['username'] ?></td>
                        <td><?= $cashier['password'] ?></td>
                        <td>
                            <a href="?delete_cashier_id=<?= $cashier['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this cashier?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Admin Dashboard</a>
    </div>

    <!-- Modal for adding a new cashier -->
    <div class="modal fade" id="addCashierModal" tabindex="-1" aria-labelledby="addCashierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCashierModalLabel">Add New Cashier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form to create new cashier -->
                    <form method="POST" action="manage_cashiers.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" name="create_cashier" class="btn btn-success">Create Cashier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>
</body>
</html>
