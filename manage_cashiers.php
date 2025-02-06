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

// Handle creation of a new cashier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cashier'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // Password entered by the admin
    $role = 'cashier'; // Role is set to 'cashier'

    // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Insert the new cashier into the users table with the hashed password
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute(['username' => $username, 'password' => $hashed_password, 'role' => $role]);

        // Return success message for AJAX
        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        // Return error message for AJAX
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cashiers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h1>Manage Cashiers</h1>

        <!-- Button to trigger modal -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCashierModal">Add New Cashier</button>

        <!-- Cashiers List Table -->
        <h2>Cashier List</h2>
        <table class="table table-bordered" id="cashiersTable">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Password (Hashed)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cashiers_list as $cashier): ?>
                    <tr data-cashier-id="<?= $cashier['user_id'] ?>">
                        <td><?= $cashier['username'] ?></td>
                        <td>Hashed Password</td> <!-- Display a generic message instead of the actual hash -->
                        <td>
                            <button class="btn btn-danger btn-sm btn-delete" data-cashier-id="<?= $cashier['user_id'] ?>">Delete</button>
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
                    <form id="createCashierForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-success">Create Cashier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for confirming deletion -->
    <div class="modal fade" id="deleteCashierModal" tabindex="-1" aria-labelledby="deleteCashierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCashierModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this cashier account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Cashier account created successfully!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>

    <script>
        // Handle form submission via AJAX
        $('#createCashierForm').submit(function (e) {
            e.preventDefault();

            var formData = $(this).serialize(); // Get form data

            $.ajax({
                url: 'manage_cashiers.php',
                type: 'POST',
                data: formData + '&create_cashier=true',
                success: function (response) {
                    var res = JSON.parse(response);
                    if (res.success) {
                        // Display the success modal
                        $('#successModal').modal('show');

                        // Add new cashier to the table dynamically
                        var newRow = '<tr>' +
                            '<td>' + $('#username').val() + '</td>' +
                            '<td>Hashed Password</td>' +
                            '<td><button class="btn btn-danger btn-sm btn-delete" data-cashier-id="' + $('#username').val() + '">Delete</button></td>' +
                            '</tr>';
                        $('#cashiersTable tbody').append(newRow);

                        // Clear the form fields
                        $('#username').val('');
                        $('#password').val('');

                        // Close the "Add Cashier" modal automatically
                        $('#addCashierModal').modal('hide');
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function () {
                    alert('An error occurred while creating the cashier.');
                }
            });
        });

        // Show the confirmation modal when the delete button is clicked
        $(document).on('click', '.btn-delete', function () {
            var cashierId = $(this).data('cashier-id'); // Get the cashier ID
            $('#confirmDeleteBtn').data('cashier-id', cashierId); // Store the cashier ID in the delete button
            $('#deleteCashierModal').modal('show'); // Show the confirmation modal
        });

        // Handle deletion after confirmation
        $('#confirmDeleteBtn').click(function () {
            var cashierId = $(this).data('cashier-id'); // Get the cashier ID
            window.location.href = 'manage_cashiers.php?delete_cashier_id=' + cashierId; // Trigger deletion
        });
    </script>

</body>

</html>
