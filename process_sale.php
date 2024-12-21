<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

// Fetch movies including tickets_available
$movies = [];
try {
    $stmt = $conn->query("
        SELECT m.id, m.movie_name, m.ticket_price, m.showtime_start, m.showtime_end, 
               COALESCE(ts.tickets_available, 0) AS tickets_available 
        FROM movies m 
        LEFT JOIN ticket_stock ts ON m.id = ts.movie_id 
        ORDER BY m.showtime_start DESC");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Fetch current ticket stock for validation
$stmt = $conn->query("SELECT tickets_available FROM ticket_stock WHERE id = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$tickets_available = $row['tickets_available'] ?? 0;

// Check if cash-in is logged
$cash_log_stmt = $conn->prepare("SELECT * FROM cash_log WHERE cashier_id = :cashier_id AND DATE(log_date) = CURDATE() AND cash_in IS NOT NULL");
$cash_log_stmt->execute(['cashier_id' => $_SESSION['user_id']]);
$cash_log = $cash_log_stmt->fetch(PDO::FETCH_ASSOC);

// If no cash-in log found, display the modal to prompt for cash-in
$show_cash_in_modal = !$cash_log;

if (!$cash_log) {
    $_SESSION['error_message'] = "You must log your cash-in amount before processing sales.";
    // Don't process the sale, display the modal instead
    $receipt = null;
}

$receipt = null; // Initialize receipt variable

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $cash_log) {
    $movie_id = intval($_POST['movie_id']);
    $num_tickets = intval($_POST['num_tickets']);
    $payment_amount = floatval($_POST['payment_amount']);

    // Validate input
    if ($num_tickets <= 0 || $payment_amount <= 0) {
        echo "<div class='alert alert-danger'>Invalid input. Please try again.</div>";
    } else {
        // Fetch selected movie details
        $movie_stmt = $conn->prepare("SELECT movie_name, ticket_price FROM movies WHERE id = :movie_id");
        $movie_stmt->execute(['movie_id' => $movie_id]);
        $movie = $movie_stmt->fetch(PDO::FETCH_ASSOC);

        if ($movie) {
            $movie_name = $movie['movie_name'];
            $ticket_price = $movie['ticket_price'];
            $total_sale = $ticket_price * $num_tickets;

            // Fetch ticket stock for the selected movie
            $ticket_stock_stmt = $conn->prepare("SELECT tickets_available FROM ticket_stock WHERE movie_id = :movie_id");
            $ticket_stock_stmt->execute(['movie_id' => $movie_id]);
            $ticket_stock = $ticket_stock_stmt->fetch(PDO::FETCH_ASSOC);

            // Check if enough tickets are available
            if ($ticket_stock && $ticket_stock['tickets_available'] >= $num_tickets) {
                // Calculate change
                if ($payment_amount >= $total_sale) {
                    $change = $payment_amount - $total_sale;

                    // Update ticket stock
                    $new_stock = $ticket_stock['tickets_available'] - $num_tickets;
                    $update_stock_stmt = $conn->prepare("UPDATE ticket_stock SET tickets_available = :new_stock WHERE movie_id = :movie_id");
                    $update_stock_stmt->execute(['new_stock' => $new_stock, 'movie_id' => $movie_id]);

                    // Record the sale
                    $sale_stmt = $conn->prepare("INSERT INTO sales (cashier_id, movie_name, quantity_sold, total_sale, sale_date) VALUES (:cashier_id, :movie_name, :quantity_sold, :total_sale, NOW())");
                    $sale_stmt->execute([ 
                        'cashier_id' => $_SESSION['user_id'],
                        'movie_name' => $movie_name,
                        'quantity_sold' => $num_tickets,
                        'total_sale' => $total_sale,
                    ]);

                    // Update cash log (if needed)
                    $update_cash_log_stmt = $conn->prepare("UPDATE cash_log SET cash_on_hand = cash_on_hand + :sale_amount WHERE id = :cash_log_id");
                    $update_cash_log_stmt->execute([
                        'sale_amount' => $total_sale,
                        'cash_log_id' => $cash_log['id'],
                    ]);

                    // Prepare the receipt data
                    $receipt = [
                        'total_sale' => $total_sale,
                        'payment_received' => $payment_amount,
                        'change' => $change,
                        'movie_name' => $movie_name,
                        'num_tickets' => $num_tickets
                    ];
                } else {
                    echo "<div class='alert alert-danger'>Insufficient payment. Please enter a higher amount.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Not enough tickets available. Please try again.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Movie not found.</div>";
        }
    }

    // Refresh ticket availability
    $stmt = $conn->query("SELECT tickets_available FROM ticket_stock WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $tickets_available = $row['tickets_available'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Sale - Ticketing System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Process a Sale</h1>

        <!-- Sale Form -->
        <form id="saleForm" action="process_sale.php" method="POST">
        <div class="mb-3">
            <label for="movie_name" class="form-label">Select Movie</label>
            <select class="form-select" id="movie_id" name="movie_id" required>
                <option value="">Select a movie</option>
                <?php foreach ($movies as $movie): ?>
                    <option value="<?= $movie['id'] ?>">
                        <?= htmlspecialchars($movie['movie_name']) ?> 
                        (<?= date('F j, Y, g:i A', strtotime($movie['showtime_start'])) ?> to <?= date('g:i A', strtotime($movie['showtime_end'])) ?>)
                        - Tickets Available: <?= $movie['tickets_available'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


            <div class="mb-3">
                <label for="num_tickets" class="form-label">Number of Tickets</label>
                <input type="number" class="form-control" id="num_tickets" name="num_tickets" required>
            </div>
            <div class="mb-3">
                <label for="payment_amount" class="form-label">Payment Amount</label>
                <input type="number" class="form-control" id="payment_amount" name="payment_amount" required>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">Process Sale</button>
        </form>
        <a href="cashier_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>

        <!-- Receipt Section (Hidden initially) -->
        <?php if ($receipt): ?>
        <div class="alert alert-success mt-4">
            <h4>Sale Receipt</h4>
            <p><strong>Movie:</strong> <?= $receipt['movie_name'] ?></p>
            <p><strong>Number of Tickets:</strong> <?= $receipt['num_tickets'] ?></p>
            <p><strong>Total Sale:</strong> $<?= number_format($receipt['total_sale'], 2) ?></p>
            <p><strong>Payment Received:</strong> $<?= number_format($receipt['payment_received'], 2) ?></p>
            <p><strong>Change:</strong> $<?= number_format($receipt['change'], 2) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal for Cash In Reminder (If Cash In hasn't been done) -->
    <?php if ($show_cash_in_modal): ?>
    <div class="modal fade" id="cashInModal" tabindex="-1" aria-labelledby="cashInModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cashInModalLabel">Cash In Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    You must log a cash-in amount before processing sales.
                    <br><br>
                    Please click below to log your cash-in.
                </div>
                <div class="modal-footer">
                    <a href="cash_in_out.php" class="btn btn-primary">Go to Cash In</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Show the modal if cash-in is required
        var myModal = new bootstrap.Modal(document.getElementById('cashInModal'));
        myModal.show();
    </script>
    <?php endif; ?>

    <!-- Modal for Sale Confirmation -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to process this sale?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="saleForm">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
