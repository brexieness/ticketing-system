<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

$message = "";

// Fetch today's cash log
$stmt = $conn->prepare("SELECT * FROM cash_log WHERE cashier_id = :cashier_id AND DATE(log_date) = CURDATE()");
$stmt->execute(['cashier_id' => $_SESSION['user_id']]);
$cash_log = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cash_in = isset($_POST['cash_in']) ? floatval($_POST['cash_in']) : null;
    $cash_out = isset($_POST['cash_out']) ? floatval($_POST['cash_out']) : null;

    // Handle Cash-In
    if ($cash_in !== null) {
        if ($cash_log) {
            $message = "Cash-in already logged for today!";
        } else {
            $stmt = $conn->prepare("INSERT INTO cash_log (cashier_id, cash_in, cash_out, cash_on_hand, log_date) VALUES (:cashier_id, :cash_in, NULL, :cash_in, NOW())");
            $stmt->execute([
                'cashier_id' => $_SESSION['user_id'],
                'cash_in' => $cash_in,
            ]);
            $message = "Cash-in logged successfully!";
            $cash_log['cash_in'] = $cash_in;
        }
    }

    // Handle Cash-Out
    if ($cash_out !== null) {
        if (!$cash_log || !$cash_log['cash_in']) {
            $message = "Please log your cash-in amount first!";
        } else {
            $stmt = $conn->prepare("UPDATE cash_log SET cash_out = :cash_out, cash_on_hand = cash_on_hand - :cash_out WHERE cashier_id = :cashier_id AND DATE(log_date) = CURDATE()");
            $stmt->execute([
                'cash_out' => $cash_out,
                'cashier_id' => $_SESSION['user_id'],
            ]);
            $message = "Cash-out logged successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Cash-In/Out</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Log Cash-In/Out</h1>

        <!-- Notification -->
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <!-- Cash-In Form -->
        <form action="cash_in_out.php" method="POST" class="mb-4">
            <h3>Cash-In</h3>
            <?php if ($cash_log && $cash_log['cash_in']): ?>
                <p class="text-success">You have already logged a cash-in amount of <strong><?= number_format($cash_log['cash_in'], 2) ?></strong> today.</p>
            <?php else: ?>
                <div class="mb-3">
                    <label for="cash_in" class="form-label">Enter Cash-In Amount</label>
                    <input type="number" step="0.01" class="form-control" id="cash_in" name="cash_in" required>
                </div>
                <button type="submit" class="btn btn-primary">Log Cash-In</button>
            <?php endif; ?>
        </form>

        <!-- Cash-Out Form -->
        <form action="cash_in_out.php" method="POST">
            <h3>Cash-Out</h3>
            <?php if (!$cash_log || !$cash_log['cash_in']): ?>
                <p class="text-danger">You must log a cash-in amount before cashing out.</p>
            <?php else: ?>
                <div class="mb-3">
                    <label for="cash_out" class="form-label">Enter Cash-Out Amount</label>
                    <input type="number" step="0.01" class="form-control" id="cash_out" name="cash_out" required>
                </div>
                <button type="submit" class="btn btn-danger">Log Cash-Out</button>
            <?php endif; ?>
        </form>

        <a href="cashier_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
