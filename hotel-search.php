<?php
session_start();
require_once 'config/database.php';

$star_rating = $_GET['star_rating'] ?? '';
$location = $_GET['location'] ?? '';
$price_range = $_GET['price_range'] ?? '';

$where_conditions = ["h.status = 'approved'"];
$params = [];

if ($star_rating) {
    $where_conditions[] = "h.star_rating = ?";
    $params[] = $star_rating;
}

if ($location) {
    $where_conditions[] = "h.city LIKE ?";
    $params[] = "%$location%";
}

if ($price_range) {
    switch($price_range) {
        case 'budget':
            $where_conditions[] = "h.price_per_night < 100";
            break;
        case 'moderate':
            $where_conditions[] = "h.price_per_night BETWEEN 100 AND 200";
            break;
        case 'expensive':
            $where_conditions[] = "h.price_per_night BETWEEN 200 AND 400";
            break;
        case 'luxury':
            $where_conditions[] = "h.price_per_night > 400";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT h.*, AVG(r.overall_rating) as avg_rating, COUNT(r.id) as review_count 
                      FROM hotels h 
                      LEFT JOIN reviews r ON h.id = r.hotel_id AND r.status = 'approved'
                      WHERE $where_clause
                      GROUP BY h.id 
                      ORDER BY avg_rating DESC");
$stmt->execute($params);
$hotels = $stmt->fetchAll();

$cities_stmt = $pdo->query("SELECT DISTINCT city FROM hotels WHERE status = 'approved' ORDER BY city");
$cities = $cities_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hotels - RestaurantBook</title>
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
        <h1>Browse Hotels</h1>
        
        <form class="filters" method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Star Rating</label>
                    <select name="star_rating" class="filter-select">
                        <option value="">All Ratings</option>
                        <option value="5" <?= $star_rating === '5' ? 'selected' : '' ?>>5 Stars</option>
                        <option value="4" <?= $star_rating === '4' ? 'selected' : '' ?>>4 Stars</option>
                        <option value="3" <?= $star_rating === '3' ? 'selected' : '' ?>>3 Stars</option>
                        <option value="2" <?= $star_rating === '2' ? 'selected' : '' ?>>2 Stars</option>
                        <option value="1" <?= $star_rating === '1' ? 'selected' : '' ?>>1 Star</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Price Range</label>
                    <select name="price_range" class="filter-select">
                        <option value="">All Prices</option>
                        <option value="budget" <?= $price_range === 'budget' ? 'selected' : '' ?>>$ (Under $100)</option>
                        <option value="moderate" <?= $price_range === 'moderate' ? 'selected' : '' ?>>$$ ($100-200)</option>
                        <option value="expensive" <?= $price_range === 'expensive' ? 'selected' : '' ?>>$$$ ($200-400)</option>
                        <option value="luxury" <?= $price_range === 'luxury' ? 'selected' : '' ?>>$$$$ (Over $400)</option>
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
            <?php foreach ($hotels as $hotel): ?>
            <div class="card">
                <?php 
                $hotel_image = 'hotel-' . $hotel['star_rating'] . '-star.jpg';
                ?>
                <img src="assets/images/<?= $hotel_image ?>" alt="<?= htmlspecialchars($hotel['name']) ?>" class="card-img" onerror="this.src='assets/images/placeholder.jpg'">
                <div class="card-content">
                    <h3 class="card-title"><?= htmlspecialchars($hotel['name']) ?></h3>
                    <p class="card-text"><?= str_repeat('⭐', $hotel['star_rating']) ?> <?= $hotel['star_rating'] ?> Star • <?= htmlspecialchars($hotel['city']) ?></p>
                    <div class="rating">
                        <span class="stars">
                            <?php 
                            $rating = $hotel['avg_rating'] ? round($hotel['avg_rating']) : 0;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            ?>
                        </span>
                        <span>(<?= $hotel['review_count'] ?> reviews)</span>
                    </div>
                    <p class="card-text"><?= htmlspecialchars(substr($hotel['description'], 0, 100)) ?>...</p>
                    <div class="price">$<?= number_format($hotel['price_per_night'], 2) ?> per night</div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="hotel.php?id=<?= $hotel['id'] ?>" class="btn btn-primary" style="flex: 1;">View Details</a>
                        <a href="hotel.php?id=<?= $hotel['id'] ?>" class="btn btn-secondary">View Reviews</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($hotels)): ?>
        <div style="text-align: center; padding: 2rem;">
            <h3>No hotels found</h3>
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