<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

// Fetch movies with showtimes grouped by movie ID
$movies_stmt = $conn->prepare("
    SELECT m.id, m.movie_name, m.ticket_price, ts.tickets_available, s.showtime_start, s.showtime_end
    FROM movies m
    LEFT JOIN ticket_stock ts ON m.id = ts.movie_id
    LEFT JOIN showtimes s ON m.id = s.movie_id
    ORDER BY m.movie_name, s.showtime_start
");
$movies_stmt->execute();
$movies = $movies_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group movies by their ID
$groupedMovies = [];
foreach ($movies as $movie) {
    $movieId = $movie['id'];
    $groupedMovies[$movieId]['movie_name'] = $movie['movie_name'];
    $groupedMovies[$movieId]['ticket_price'] = $movie['ticket_price'];
    $groupedMovies[$movieId]['tickets_available'] = $movie['tickets_available'];
    if ($movie['showtime_start'] && $movie['showtime_end']) {
        $groupedMovies[$movieId]['showtimes'][] = [
            'showtime_start' => $movie['showtime_start'],
            'showtime_end' => $movie['showtime_end'],
        ];
    }
}

// Handle AJAX request for processing the sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_sale') {
    $movie_id = $_POST['movie_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $customer_money = $_POST['customer_money'] ?? null;

    if (!$movie_id || !$quantity || $quantity < 1 || !$customer_money || $customer_money < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit();
    }

    try {
        // Fetch movie details and validate stock
        $stmt = $conn->prepare("
            SELECT ts.tickets_available, m.ticket_price, m.movie_name 
            FROM ticket_stock ts 
            JOIN movies m ON ts.movie_id = m.id 
            WHERE ts.movie_id = :movie_id
        ");
        $stmt->execute(['movie_id' => $movie_id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$movie || $movie['tickets_available'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient tickets available.']);
            exit();
        }

        // Calculate total cost
        $total_price = $movie['ticket_price'] * $quantity;
        $change = $customer_money - $total_price;

        // Update ticket stock
        $update_stmt = $conn->prepare("
            UPDATE ticket_stock 
            SET tickets_available = tickets_available - :quantity 
            WHERE movie_id = :movie_id
        ");
        $update_stmt->execute(['quantity' => $quantity, 'movie_id' => $movie_id]);

        // Update cash log
        $cash_stmt = $conn->prepare("
            UPDATE cash_log 
            SET cash_on_hand = cash_on_hand + :total_price 
            WHERE cashier_id = :cashier_id AND DATE(log_date) = CURDATE()
        ");
        $cash_stmt->execute([
            'total_price' => $total_price,
            'cashier_id' => $_SESSION['user_id'],
        ]);

        // Store sale details in session for receipt page
        $_SESSION['sale_details'] = [
            'movie_name' => $movie['movie_name'],
            'quantity' => (int)$quantity, // Convert to integer
            'ticket_price' => (float)$movie['ticket_price'], // Convert to float
            'total_price' => (float)$total_price, // Convert to float
            'customer_money' => (float)$customer_money, // Convert to float
            'change' => (float)$change, // Convert to float
        ];
        
        // Return sale details as part of the response
        echo json_encode([
            'success' => true,
            'message' => 'Sale processed successfully. Redirecting to receipt...',
            'sale_details' => $_SESSION['sale_details']
        ]);
        exit(); // Close the try block with a successful exit
    } catch (Exception $e) {
        // Handle any exceptions
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Sale</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .movie-card {
            transition: transform 0.2s ease;
        }

        .movie-card:hover {
            transform: scale(1.05);
        }

        .summary-card {
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Process Sale</h1>

        <!-- Display Cashier's Current Amount -->
        <?php
        $stmt = $conn->prepare("
            SELECT cash_on_hand FROM cash_log 
            WHERE cashier_id = :cashier_id AND DATE(log_date) = CURDATE()
        ");
        $stmt->execute(['cashier_id' => $_SESSION['user_id']]);
        $cash_log = $stmt->fetch(PDO::FETCH_ASSOC);
        $cash_on_hand = $cash_log ? $cash_log['cash_on_hand'] : 0;
        ?>
        <div class="alert alert-info">
            <strong>Cash on Hand:</strong> $<?php echo number_format($cash_on_hand, 2); ?>
        </div>

        <!-- Step 1: List Available Movies -->
        <h2>Available Movies</h2>
        <div class="row g-4">
        <?php foreach ($groupedMovies as $movieId => $movieDetails): ?>
            <div class="col-md-4">
                <div class="card movie-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($movieDetails['movie_name']); ?></h5>
                        <p class="card-text">
                            <strong>Price:</strong> $<?php echo number_format($movieDetails['ticket_price'], 2); ?><br>
                            <strong>Available:</strong> <?php echo $movieDetails['tickets_available']; ?><br>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- Step 2: Ticket Purchase Form -->
        <div class="mt-5">
            <h2>Purchase Tickets</h2>
            <form id="purchaseForm" method="POST" action="process_sale.php">
                <div class="mb-3">
                    <label for="movie" class="form-label">Select Movie</label>
                    <select id="movie" name="movie_id" class="form-select" required>
                        <option value="" selected disabled>Choose a movie</option>
                        <?php foreach ($groupedMovies as $movieId => $movieDetails): ?>
                            <option value="<?php echo $movieId; ?>" data-price="<?php echo $movieDetails['ticket_price']; ?>" data-available="<?php echo $movieDetails['tickets_available']; ?>" data-showtimes='<?php echo json_encode($movieDetails['showtimes']); ?>'>
                                <?php echo htmlspecialchars($movieDetails['movie_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Showtimes Dropdown (Initially Empty) -->
                <div class="mb-3" id="showtimeContainer" style="display: none;">
                    <label for="showtime" class="form-label">Select Showtime</label>
                    <select id="showtime" name="showtime" class="form-select" required>
                        <option value="" selected disabled>Choose a showtime</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Ticket Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" placeholder="Enter quantity" required>
                </div>
                <div class="mb-3">
                    <label for="customer_money" class="form-label">Customer's Money</label>
                    <input type="number" id="customer_money" name="customer_money" class="form-control" step="0.01" placeholder="Enter customer's money" required>
                </div>
                <button type="button" id="calculateButton" class="btn btn-primary">Calculate Total</button>
            </form>
        </div>

        <!-- Modal for confirming purchase -->
        <div class="modal fade" id="confirmPurchaseModal" tabindex="-1" aria-labelledby="confirmPurchaseModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmPurchaseModalLabel">Confirm Purchase</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="summaryDetails"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="confirmPurchaseButton">Confirm
                            Purchase</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for displaying the receipt -->
        <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receiptModalLabel">Sale Receipt</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="receiptSummary"></div> <!-- This will be filled dynamically with sale details -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('movie').addEventListener('change', function () {
        const movieSelect = this;
        const showtimeContainer = document.getElementById('showtimeContainer');
        const showtimeSelect = document.getElementById('showtime');
        const showtimesData = JSON.parse(movieSelect.selectedOptions[0].getAttribute('data-showtimes'));

        showtimeSelect.innerHTML = '<option value="" selected disabled>Choose a showtime</option>';
        showtimeContainer.style.display = 'block'; // Show the showtime dropdown

        showtimesData.forEach(function (showtime) {
            const option = document.createElement('option');
            option.value = showtime.showtime_start;
            option.textContent = `${showtime.showtime_start} - ${showtime.showtime_end}`;
            showtimeSelect.appendChild(option);
        });
    });

    document.getElementById('calculateButton').addEventListener('click', function () {
        const movieSelect = document.getElementById('movie');
        const showtimeSelect = document.getElementById('showtime');
        const quantityInput = document.getElementById('quantity');
        const customerMoneyInput = document.getElementById('customer_money');
        const summarySection = document.getElementById('summaryDetails');
        const movieId = movieSelect.value;
        const showtime = showtimeSelect.value; // Get selected showtime
        const price = parseFloat(movieSelect.options[movieSelect.selectedIndex].getAttribute('data-price'));
        const available = parseInt(movieSelect.options[movieSelect.selectedIndex].getAttribute('data-available'));
        const quantity = parseInt(quantityInput.value);
        const customerMoney = parseFloat(customerMoneyInput.value);

        if (isNaN(quantity) || quantity < 1) {
            alert('Please enter a valid quantity');
            return;
        }

        if (isNaN(customerMoney) || customerMoney <= 0) {
            alert('Please enter a valid amount of money');
            return;
        }

        if (quantity > available) {
            alert('Insufficient tickets available');
            return;
        }

        const totalPrice = price * quantity;

        summarySection.innerHTML = `  
            <p><strong>Movie:</strong> ${movieSelect.options[movieSelect.selectedIndex].text}</p>
            <p><strong>Showtime:</strong> ${showtime}</p>
            <p><strong>Ticket Price:</strong> $${price.toFixed(2)}</p>
            <p><strong>Quantity:</strong> ${quantity}</p>
            <p><strong>Total Price:</strong> $${totalPrice.toFixed(2)}</p>
            <p><strong>Customer's Money:</strong> $${customerMoney.toFixed(2)}</p>
            <p><strong>Change:</strong> $${(customerMoney - totalPrice).toFixed(2)}</p>
        `;

        const modal = new bootstrap.Modal(document.getElementById('confirmPurchaseModal'));
        modal.show();

        document.getElementById('confirmPurchaseButton').disabled = false;
    });

    document.getElementById('confirmPurchaseButton').addEventListener('click', function () {
        // Submit the form data via AJAX
        const form = document.getElementById('purchaseForm');
        const formData = new FormData(form);
        formData.append('action', 'process_sale');

        fetch('process_sale.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sale processed successfully!');

                // Set receipt details in the modal
                const summarySection = document.getElementById('receiptSummary');
                const saleDetails = data.sale_details; // Assuming the sale details are in data.sale_details

                summarySection.innerHTML = `
                    <p><strong>Movie:</strong> ${saleDetails.movie_name}</p>
                    <p><strong>Quantity:</strong> ${saleDetails.quantity}</p>
                    <p><strong>Ticket Price:</strong> $${saleDetails.ticket_price.toFixed(2)}</p>
                    <p><strong>Total Price:</strong> $${saleDetails.total_price.toFixed(2)}</p>
                    <p><strong>Customer's Money:</strong> $${saleDetails.customer_money.toFixed(2)}</p>
                    <p><strong>Change:</strong> $${saleDetails.change.toFixed(2)}</p>
                `;

                // Show the receipt modal immediately
                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing the sale.');
        });
    });
    
    </script>

</body>
</html>
