<?php
session_start();
require_once 'config/database.php';

$cuisine = $_GET['cuisine'] ?? '';
$location = $_GET['location'] ?? '';
$price_range = $_GET['price_range'] ?? '';

$where_conditions = ["r.status = 'approved'"];
$params = [];

if ($cuisine) {
    $where_conditions[] = "r.cuisine_type = ?";
    $params[] = $cuisine;
}

if ($location) {
    $where_conditions[] = "r.city LIKE ?";
    $params[] = "%$location%";
}

if ($price_range) {
    switch($price_range) {
        case 'budget':
            $where_conditions[] = "r.price_per_person < 25";
            break;
        case 'moderate':
            $where_conditions[] = "r.price_per_person BETWEEN 25 AND 50";
            break;
        case 'expensive':
            $where_conditions[] = "r.price_per_person BETWEEN 50 AND 100";
            break;
        case 'fine_dining':
            $where_conditions[] = "r.price_per_person > 100";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT r.*, ri.image_path, AVG(rev.overall_rating) as avg_rating, COUNT(rev.id) as review_count 
                      FROM restaurants r 
                      LEFT JOIN restaurant_images ri ON r.id = ri.restaurant_id AND ri.is_primary = 1
                      LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.status = 'approved'
                      WHERE $where_clause
                      GROUP BY r.id 
                      ORDER BY avg_rating DESC");
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

$cuisines_stmt = $pdo->query("SELECT DISTINCT cuisine_type FROM restaurants WHERE status = 'approved' ORDER BY cuisine_type");
$cuisines = $cuisines_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Restaurants - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filters { background: white; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .filter-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .filter-select { width: 100%; padding: 10px; border: 2px solid #e1e5e9; }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <div class="burger" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Home</a></li>
                <li><a href="search.php">Restaurants</a></li>
                <li><a href="hotel-search.php">Hotels</a></li>
                <li><a href="review.php">Write Review</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="customer/dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php" class="btn-logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin: 2rem auto;">
        <h1>Browse Restaurants</h1>
        
        <form class="filters" method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Cuisine Type</label>
                    <select name="cuisine" class="filter-select">
                        <option value="">All Cuisines</option>
                        <?php foreach ($cuisines as $c): ?>
                            <option value="<?= htmlspecialchars($c['cuisine_type']) ?>" <?= $cuisine === $c['cuisine_type'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['cuisine_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Price Range</label>
                    <select name="price_range" class="filter-select">
                        <option value="">All Prices</option>
                        <option value="budget" <?= $price_range === 'budget' ? 'selected' : '' ?>>$ (Under $25)</option>
                        <option value="moderate" <?= $price_range === 'moderate' ? 'selected' : '' ?>>$$ ($25-50)</option>
                        <option value="expensive" <?= $price_range === 'expensive' ? 'selected' : '' ?>>$$$ ($50-100)</option>
                        <option value="fine_dining" <?= $price_range === 'fine_dining' ? 'selected' : '' ?>>$$$$ (Over $100)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($location) ?>" placeholder="City" class="filter-select">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                </div>
            </div>
        </form>

        <div class="grid">
            <?php foreach ($restaurants as $restaurant): ?>
            <div class="card">
                <?php 
                $cuisine_name = strtolower(str_replace(' ', '-', $restaurant['cuisine_type']));
                $image_file = 'cuisine-' . $cuisine_name . '.jpg';
                ?>
                <img src="assets/images/<?= $image_file ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>" class="card-img" onerror="this.src='assets/images/placeholder.jpg'">
                <div class="card-content">
                    <h3 class="card-title"><?= htmlspecialchars($restaurant['name']) ?></h3>
                    <p class="card-text"><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['city']) ?></p>
                    <div class="rating">
                        <span class="stars">
                            <?php 
                            $rating = $restaurant['avg_rating'] ? round($restaurant['avg_rating']) : 0;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            ?>
                        </span>
                        <span>(<?= $restaurant['review_count'] ?> reviews)</span>
                    </div>
                    <p class="card-text"><?= htmlspecialchars(substr($restaurant['description'], 0, 100)) ?>...</p>
                    <div class="price">$<?= number_format($restaurant['price_per_person'], 2) ?> per person</div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="restaurant.php?id=<?= $restaurant['id'] ?>" class="btn btn-primary" style="flex: 1;">View Details</a>
                        <a href="restaurant.php?id=<?= $restaurant['id'] ?>" class="btn btn-secondary">View Reviews</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($restaurants)): ?>
        <div style="text-align: center; padding: 2rem;">
            <h3>No restaurants found</h3>
            <p>Try adjusting your filters or search criteria.</p>
        </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 RestaurantBook - Restaurant Review Portal. All rights reserved.</p>
            <p>Developer Team: Junaid Jabbar Faizi, Sami Ullah Khan, Usman Ehsan</p>
            <p><a href="index.php" style="color: #ccc;">Home</a> | <a href="search.php" style="color: #ccc;">Restaurants</a> | <a href="hotel-search.php" style="color: #ccc;">Hotels</a> | <a href="review.php" style="color: #ccc;">Write Review</a> | <a href="login.php" style="color: #ccc;">Login</a></p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            const burger = document.querySelector('.burger');
            
            navLinks.classList.toggle('active');
            burger.classList.toggle('active');
        }
    </script>
</body>
</html>