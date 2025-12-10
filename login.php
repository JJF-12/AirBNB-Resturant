<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            if ($user['user_type'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['user_type'] === 'host') {
                header('Location: host/dashboard.php');
            } else {
                header('Location: customer/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 4rem auto; }
        .login-form { background: white; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e5e9; font-size: 16px; }
        .form-control:focus { outline: none; border-color: #ff5a5f; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; margin: 1rem 0; }
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
                <li><a href="register.php" class="btn-signup">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container login-container">
        <h1 style="text-align: center; margin-bottom: 2rem;">Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            
            <p style="text-align: center; margin-top: 1rem;">
                Don't have an account? <a href="register.php">Sign up here</a>
            </p>
        </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 RestaurantBook - Restaurant Review Portal. All rights reserved.</p>
            <p>Developer Team: Junaid Jabbar Faizi, Sami Ullah Khan, Usman Ehsan</p>
            <p><a href="index.php" style="color: #ccc;">Home</a> | <a href="search.php" style="color: #ccc;">Restaurants</a> | <a href="hotel-search.php" style="color: #ccc;">Hotels</a> | <a href="review.php" style="color: #ccc;">Write Review</a> | <a href="register.php" style="color: #ccc;">Sign Up</a></p>
        </div>
    </footer>
</body>
</html>