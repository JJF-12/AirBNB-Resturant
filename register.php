<?php
session_start();
require_once 'config/database.php';

$error = $success = '';

if ($_POST) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'customer';
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        
        if ($check_stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, user_type, status) VALUES (?, ?, ?, ?, ?, 'active')");
            
            if ($stmt->execute([$first_name, $last_name, $email, $hashed_password, $user_type])) {
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container { max-width: 500px; margin: 4rem auto; }
        .register-form { background: white; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e5e9; font-size: 16px; }
        .form-control:focus { outline: none; border-color: #ff5a5f; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; margin: 1rem 0; }
        .success { background: #d4edda; color: #155724; padding: 1rem; margin: 1rem 0; }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="search.php">Restaurants</a></li>
                <li><a href="hotel-search.php">Hotels</a></li>
                <li><a href="review.php">Write Review</a></li>
                <li><a href="login.php" class="btn-login">Login</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container register-container">
        <h1 style="text-align: center; margin-bottom: 2rem;">Create Account</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" class="register-form">
            <div class="form-group">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="user_type" class="form-label">Account Type</label>
                <select name="user_type" id="user_type" class="form-control">
                    <option value="customer">Customer</option>
                    <option value="host">Restaurant/Hotel Owner</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            
            <p style="text-align: center; margin-top: 1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 RestaurantBook - Restaurant Review Portal. All rights reserved.</p>
            <p>Developer Team: Junaid Jabbar Faizi, Sami Ullah Khan, Usman Ehsan</p>
            <p><a href="index.php" style="color: #ccc;">Home</a> | <a href="search.php" style="color: #ccc;">Restaurants</a> | <a href="hotel-search.php" style="color: #ccc;">Hotels</a> | <a href="review.php" style="color: #ccc;">Write Review</a> | <a href="login.php" style="color: #ccc;">Login</a></p>
        </div>
    </footer>
</body>
</html>