<?php
session_start();
if (isset($_SESSION['error'])):
?>
    <div class="alert alert-danger">
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ticketing System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .input-group-text {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="card p-4 shadow" style="width: 300px;">
            <h3 class="text-center">Login</h3>
            <form action="authentication.php" method="POST">
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="cashier">Cashier</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <!-- Eye icon inside the input field -->
                        <span class="input-group-text" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;

            // Toggle the icon (eye or eye-slash)
            this.firstElementChild.classList.toggle('fa-eye');
            this.firstElementChild.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
