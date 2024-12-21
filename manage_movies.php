<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db_connection.php';

// Fetch all movies for displaying (including stock from ticket_stock table)
$movies = [];
try {
    $stmt = $conn->query("SELECT m.id, m.movie_name, m.ticket_price, m.showtime_start, m.showtime_end, ts.tickets_available FROM movies m LEFT JOIN ticket_stock ts ON m.id = ts.movie_id ORDER BY m.showtime_start DESC");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Handle movie addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_movie'])) {
    $movie_name = $_POST['movie_name'];
    $ticket_price = $_POST['ticket_price'];
    $showtime_start = $_POST['showtime_start'];
    $showtime_end = $_POST['showtime_end'];
    $tickets_available = $_POST['tickets_available']; // Get tickets available

    try {
        // Insert movie into the movies table
        $stmt = $conn->prepare("INSERT INTO movies (movie_name, ticket_price, showtime_start, showtime_end) VALUES (:movie_name, :ticket_price, :showtime_start, :showtime_end)");
        $stmt->execute([
            ':movie_name' => $movie_name,
            ':ticket_price' => $ticket_price,
            ':showtime_start' => $showtime_start,
            ':showtime_end' => $showtime_end
        ]);
        
        // Get the last inserted movie ID
        $movie_id = $conn->lastInsertId();
        
        // Insert stock information into the ticket_stock table
        $stmt = $conn->prepare("INSERT INTO ticket_stock (movie_id, tickets_available) VALUES (:movie_id, :tickets_available)");
        $stmt->execute([
            ':movie_id' => $movie_id,
            ':tickets_available' => $tickets_available
        ]);

        header('Location: manage_movies.php');  // Refresh page after adding movie
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle movie editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_movie'])) {
    $movie_id = $_POST['movie_id'];
    $movie_name = $_POST['movie_name'];
    $ticket_price = $_POST['ticket_price'];
    $showtime_start = $_POST['showtime_start'];
    $showtime_end = $_POST['showtime_end'];
    $tickets_available = $_POST['tickets_available'];

    try {
        // Update movie details in movies table
        $stmt = $conn->prepare("UPDATE movies SET movie_name = :movie_name, ticket_price = :ticket_price, showtime_start = :showtime_start, showtime_end = :showtime_end WHERE id = :movie_id");
        $stmt->execute([
            ':movie_name' => $movie_name,
            ':ticket_price' => $ticket_price,
            ':showtime_start' => $showtime_start,
            ':showtime_end' => $showtime_end,
            ':movie_id' => $movie_id
        ]);
        
        // Update stock information in ticket_stock table
        $stmt = $conn->prepare("UPDATE ticket_stock SET tickets_available = :tickets_available WHERE movie_id = :movie_id");
        $stmt->execute([
            ':tickets_available' => $tickets_available,
            ':movie_id' => $movie_id
        ]);

        header('Location: manage_movies.php');  // Refresh page after editing movie
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle movie deletion
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete_movie'])) {
    $movie_id = $_GET['delete_movie'];

    try {
        // Delete from ticket_stock first to avoid foreign key constraint
        $stmt = $conn->prepare("DELETE FROM ticket_stock WHERE movie_id = :movie_id");
        $stmt->execute([':movie_id' => $movie_id]);

        // Then delete from movies table
        $stmt = $conn->prepare("DELETE FROM movies WHERE id = :movie_id");
        $stmt->execute([':movie_id' => $movie_id]);

        header('Location: manage_movies.php');  // Refresh page after deletion
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
    <title>Manage Movies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Manage Movies (Admin)</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-4">Back to Dashboard</a>

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

                    <!-- Modal to Edit Movie -->
                    <div class="modal fade" id="editMovieModal<?= $movie['id'] ?>" tabindex="-1" aria-labelledby="editMovieModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editMovieModalLabel">Edit Movie</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="manage_movies.php" method="POST">
                                        <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                        <div class="mb-3">
                                            <label for="movie_name" class="form-label">Movie Name</label>
                                            <input type="text" class="form-control" id="movie_name" name="movie_name" value="<?= htmlspecialchars($movie['movie_name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="ticket_price" class="form-label">Ticket Price</label>
                                            <input type="number" class="form-control" id="ticket_price" name="ticket_price" value="<?= $movie['ticket_price'] ?>" step="0.01" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="showtime_start" class="form-label">Showtime Start</label>
                                            <input type="datetime-local" class="form-control" id="showtime_start" name="showtime_start" value="<?= date('Y-m-d\TH:i', strtotime($movie['showtime_start'])) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="showtime_end" class="form-label">Showtime End</label>
                                            <input type="datetime-local" class="form-control" id="showtime_end" name="showtime_end" value="<?= date('Y-m-d\TH:i', strtotime($movie['showtime_end'])) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tickets_available" class="form-label">Tickets Available</label>
                                            <input type="number" class="form-control" id="tickets_available" name="tickets_available" value="<?= $movie['tickets_available'] ?>" required>
                                        </div>
                                        <button type="submit" name="edit_movie" class="btn btn-primary">Update Movie</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal to Add Movie -->
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
                            <input type="datetime-local" class="form-control" id="showtime_start" name="showtime_start" required>
                        </div>
                        <div class="mb-3">
                            <label for="showtime_end" class="form-label">Showtime End</label>
                            <input type="datetime-local" class="form-control" id="showtime_end" name="showtime_end" required>
                        </div>
                        <div class="mb-3">
                            <label for="tickets_available" class="form-label">Tickets Available</label>
                            <input type="number" class="form-control" id="tickets_available" name="tickets_available" required>
                        </div>
                        <button type="submit" name="add_movie" class="btn btn-primary">Add Movie</button>
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
