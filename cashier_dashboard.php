<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

// Check if cash-in has been logged today
$cash_log_stmt = $conn->prepare("SELECT * FROM cash_log WHERE cashier_id = :cashier_id AND DATE(log_date) = CURDATE() AND cash_in IS NOT NULL");
$cash_log_stmt->execute(['cashier_id' => $_SESSION['user_id']]);
$cash_log = $cash_log_stmt->fetch(PDO::FETCH_ASSOC);

// Cash-in status message
$cash_in_status = $cash_log ? "Cash-in amount logged." : "You have not logged your cash-in amount yet.";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Cashier)</h1>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                Logout
            </button>
        </div>

        <!-- Cashier Actions Section -->
        <div class="mt-5">
            <h3>Cashier Actions</h3>

            <!-- Cash-in Status -->
            <div class="alert alert-info">
                <strong>Status:</strong> <?= $cash_in_status ?>
            </div>

            <div class="row g-4">
                <!-- Process Sale Button -->
                <div class="col-md-4">
                    <?php if ($cash_log): ?>
                        <a href="process_sale.php" class="btn btn-primary btn-lg btn-block">Process a Sale</a>
                    <?php else: ?>
                        <button class="btn btn-primary btn-lg btn-block" disabled>Process a Sale</button>
                    <?php endif; ?>
                </div>
                <!-- Log Cash In/Out Button -->
                <div class="col-md-4">
                    <a href="cash_in_out.php" class="btn btn-warning btn-lg btn-block">Log Cash In/Out</a>
                </div>
                <!-- View Personal Sales Report Button -->
                <div class="col-md-4">
                    <a href="view_sales_report.php" class="btn btn-info btn-lg btn-block">View Personal Sales Report</a>
                </div>
                <!-- Change Password Button -->
                <div class="col-md-4">
                    <a href="change_password.php" class="btn btn-secondary btn-lg btn-block">Change Password</a>
                </div>
            </div>


            <!-- If no cash-in logged, prompt user to log cash-in -->
            <?php if (!$cash_log): ?>
                <div class="alert alert-warning mt-4">
                    <strong>Warning:</strong> You must log your cash-in amount before processing a sale.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>