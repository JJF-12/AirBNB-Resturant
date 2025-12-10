<?php
session_start();
require_once 'config/database.php';

$error = $success = '';
$active_tab = $_GET['tab'] ?? 'login';

// Handle Login
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'login') {
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
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            if ($user['user_type'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['user_type'] === 'host') {
                header('Location: host/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
    $active_tab = 'login';
}

// Handle Registration
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'register') {
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
                $active_tab = 'login';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
    if ($error) $active_tab = 'register';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login & Sign Up - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container { max-width: 500px; margin: 4rem auto; }
        .auth-tabs { display: flex; background: #f8f9fa; }
        .auth-tab { flex: 1; padding: 1rem; text-align: center; cursor: pointer; border: none; background: transparent; font-size: 16px; }
        .auth-tab.active { background: white; border-bottom: 3px solid #ff5a5f; }
        .auth-form { background: white; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
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
                <li><a href="hotels.php">Hotels</a></li>
            </ul>
        </nav>
    </header>

    <div class="container auth-container">
        <div class="auth-tabs">
            <button class="auth-tab <?= $active_tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Login</button>
            <button class="auth-tab <?= $active_tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Sign Up</button>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <div class="auth-form">
            <!-- Login Tab -->
            <div id="login-tab" class="tab-content <?= $active_tab === 'login' ? 'active' : '' ?>">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="login_email" class="form-label">Email</label>
                        <input type="email" name="email" id="login_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="login_password" class="form-label">Password</label>
                        <input type="password" name="password" id="login_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </form>
            </div>

            <!-- Register Tab -->
            <div id="register-tab" class="tab-content <?= $active_tab === 'register' ? 'active' : '' ?>">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="register_email" class="form-label">Email</label>
                        <input type="email" name="email" id="register_email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="user_type" class="form-label">Account Type</label>
                        <select name="user_type" id="user_type" class="form-control">
                            <option value="customer">Customer</option>
                            <option value="host">Restaurant/Hotel Owner</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="register_password" class="form-label">Password</label>
                        <input type="password" name="password" id="register_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.auth-tab').forEach(tabBtn => {
                tabBtn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>