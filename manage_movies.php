<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

// Fetch all movies including their tickets_available
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

// Fetch feedback message
$feedback = '';
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}

// Handle movie addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $movie_name = $_POST['movie_name'];
    $ticket_price = $_POST['ticket_price'];
    $showtime_start = $_POST['showtime_start'];
    $showtime_end = $_POST['showtime_end'];
    $tickets_available = $_POST['tickets_available']; // Make sure this is captured

    try {
        $conn->beginTransaction();

        // Insert into movies table
        $stmt = $conn->prepare("
            INSERT INTO movies (movie_name, ticket_price, showtime_start, showtime_end) 
            VALUES (:movie_name, :ticket_price, :showtime_start, :showtime_end)");
        $stmt->execute([
            ':movie_name' => $movie_name,
            ':ticket_price' => $ticket_price,
            ':showtime_start' => $showtime_start,
            ':showtime_end' => $showtime_end
        ]);

        // Get the last inserted movie ID
        $movie_id = $conn->lastInsertId();

        // Insert stock info into ticket_stock table (Ensure tickets_available is passed correctly)
        $stmt = $conn->prepare("
            INSERT INTO ticket_stock (movie_id, tickets_available) 
            VALUES (:movie_id, :tickets_available)");
        $stmt->execute([
            ':movie_id' => $movie_id,
            ':tickets_available' => $tickets_available
        ]);

        $conn->commit();
        $_SESSION['feedback'] = "Movie successfully added!";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['feedback'] = "Failed to add movie: " . $e->getMessage();
    }

    header('Location: manage_movies.php');
    exit();
}

// Handle movie editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_movie'])) {
    $movie_id = $_POST['movie_id'];
    $movie_name = $_POST['movie_name'];
    $ticket_price = $_POST['ticket_price'];
    $showtime_start = $_POST['showtime_start'];
    $showtime_end = $_POST['showtime_end'];
    $tickets_available = $_POST['tickets_available']; // Ensure tickets_available is captured

    try {
        $conn->beginTransaction();

        // Update movie details in movies table
        $stmt = $conn->prepare("
            UPDATE movies 
            SET movie_name = :movie_name, ticket_price = :ticket_price, 
                showtime_start = :showtime_start, showtime_end = :showtime_end 
            WHERE id = :movie_id");
        $stmt->execute([
            ':movie_name' => $movie_name,
            ':ticket_price' => $ticket_price,
            ':showtime_start' => $showtime_start,
            ':showtime_end' => $showtime_end,
            ':movie_id' => $movie_id
        ]);

        // Update stock info in ticket_stock table
        $stmt = $conn->prepare("
            UPDATE ticket_stock 
            SET tickets_available = :tickets_available 
            WHERE movie_id = :movie_id");
        $stmt->execute([
            ':tickets_available' => $tickets_available,
            ':movie_id' => $movie_id
        ]);

        $conn->commit();
        $_SESSION['feedback'] = "Movie successfully updated!";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['feedback'] = "Failed to update movie: " . $e->getMessage();
    }

    header('Location: manage_movies.php');
    exit();
}

// Handle movie deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_movie'])) {
    $movie_id = $_GET['delete_movie'];

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("DELETE FROM ticket_stock WHERE movie_id = :movie_id");
        $stmt->execute([':movie_id' => $movie_id]);

        $stmt = $conn->prepare("DELETE FROM movies WHERE id = :movie_id");
        $stmt->execute([':movie_id' => $movie_id]);

        $conn->commit();
        $_SESSION['feedback'] = "Movie successfully deleted!";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['feedback'] = "Failed to delete movie: " . $e->getMessage();
    }

    header('Location: manage_movies.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Manage Movies (Admin)</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-4">Back to Dashboard</a>

        <!-- Feedback Section -->
        <?php if (!empty($feedback)): ?>
            <div class="alert <?= strpos($feedback, 'Failed') === false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($feedback) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Button to trigger Add Movie modal -->
        <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addMovieModal">Add Movie</button>

        <!-- Movies List -->
        <h3 class="mt-5">Current Movies</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Movie Name</th>
                    <th>Ticket Price</th>
                    <th>Showtime Start</th>
                    <th>Showtime End</th>
                    <th>Tickets Available</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movies as $movie): ?>
                    <tr>
                        <td><?= htmlspecialchars($movie['movie_name']) ?></td>
                        <td>$<?= number_format($movie['ticket_price'], 2) ?></td>
                        <td><?= date('F j, Y, g:i A', strtotime($movie['showtime_start'])) ?></td>
                        <td><?= date('F j, Y, g:i A', strtotime($movie['showtime_end'])) ?></td>
                        <td><?= $movie['tickets_available'] ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editMovieModal<?= $movie['id'] ?>">Edit</button>
                            <a href="manage_movies.php?delete_movie=<?= $movie['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this movie?')">Delete</a>
                        </td>
                    </tr>
                    <!-- Edit Modal for Each Movie -->
                    <!-- Code similar to the Add Modal with prefilled values -->
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Movie Modal -->
    <div class="modal fade" id="addMovieModal" tabindex="-1" aria-labelledby="addMovieModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMovieModalLabel">Add New Movie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_movies.php" method="POST">
                        <div class="mb-3">
                            <label for="movie_name" class="form-label">Movie Name</label>
                            <input type="text" class="form-control" id="movie_name" name="movie_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="ticket_price" class="form-label">Ticket Price</label>
                            <input type="number" class="form-control" id="ticket_price" name="ticket_price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="showtime_start" class="form-label">Showtime Start</label>
                            <input type="datetime-local" class="form-control" id="showtime_start" name="showtime_start"
                            required>
                        </div>
                        <div class="mb-3">
                            <label for="showtime_end" class="form-label">Showtime End</label>
                            <input type="datetime-local" class="form-control" id="showtime_end" name="showtime_end" required>
                        </div>
                        <div class="mb-3">
                            <label for="tickets_available" class="form-label">Tickets Available</label>
                            <input type="number" class="form-control" id="tickets_available" name="tickets_available" required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_movie">Add Movie</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Validation Script -->
    <script>
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (event) => {
                const start = form.querySelector('#showtime_start')?.value;
                const end = form.querySelector('#showtime_end')?.value;

                if (start && end && new Date(start) >= new Date(end)) {
                    event.preventDefault();
                    alert('Showtime Start must be earlier than Showtime End.');
                }
            });
        });
    </script>
</body>
</html>
