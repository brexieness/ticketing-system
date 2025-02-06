<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

$sales_data = [];
$total_tickets = 0;
$total_revenue = 0;
$active_cashiers = 0;

try {
    // Query to get total tickets sold and revenue
    $stmt = $conn->query("
        SELECT 
            DATE(sale_date) AS sale_date, 
            SUM(quantity_sold) AS total_tickets, 
            SUM(total_sale) AS total_revenue 
        FROM sales 
        GROUP BY DATE(sale_date)
        ORDER BY sale_date DESC
    ");
    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query to get overall totals
    $totals_stmt = $conn->query("
        SELECT 
            SUM(quantity_sold) AS total_tickets, 
            SUM(total_sale) AS total_revenue 
        FROM sales
    ");
    $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);
    $total_tickets = $totals['total_tickets'] ?? 0;
    $total_revenue = $totals['total_revenue'] ?? 0;

    // Query to get active cashiers
    $cashiers_stmt = $conn->query("
        SELECT COUNT(*) AS active_cashiers 
        FROM users 
        WHERE role = 'cashier'
    ");
    $cashiers = $cashiers_stmt->fetch(PDO::FETCH_ASSOC);
    $active_cashiers = $cashiers['active_cashiers'] ?? 0;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <!-- Header Section -->
        <h1>Welcome, Admin!</h1>
        <div class="btn-group mb-4">
            <a href="manage_cashiers.php" class="btn btn-primary">Manage Cashier</a>
            <a href="admin_dashboard.php" class="btn btn-secondary">View Reports</a>
            <a href="manage_movies.php" class="btn btn-warning">Manage Movies</a>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
        </div>

        <!-- Summary Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Tickets Sold</h5>
                        <p class="card-text"><?= $total_tickets ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <p class="card-text">$<?= number_format($total_revenue, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Active Cashiers</h5>
                        <p class="card-text"><?= $active_cashiers ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <h2>Sales Reports</h2>

        <!-- Daily Sales Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Tickets Sold</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales_data as $row): ?>
                    <tr>
                        <td><?= $row['sale_date'] ?></td>
                        <td><?= $row['total_tickets'] ?></td>
                        <td>$<?= number_format($row['total_revenue'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Logout Confirmation Modal -->
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

    </div>
        <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>

</body>
</html>
