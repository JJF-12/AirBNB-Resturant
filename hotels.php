<?php
session_start();
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT h.*, AVG(r.overall_rating) as avg_rating, COUNT(r.id) as review_count 
                      FROM hotels h 
                      LEFT JOIN reviews r ON h.id = r.hotel_id AND r.status = 'approved'
                      WHERE h.status = 'approved' 
                      GROUP BY h.id");
$stmt->execute();
$hotels = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hotels - RestaurantBook Review Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hotel-card { margin-bottom: 2rem; }
        .imdb-rating { background: #f5f5f0; padding: 1.5rem; margin: 1rem 0; }
        .rating-bar { display: flex; align-items: center; margin: 0.5rem 0; }
        .rating-label { width: 120px; font-size: 0.9rem; }
        .bar-container { flex: 1; height: 8px; background: #ddd; margin: 0 1rem; position: relative; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #ff6b35, #f7931e, #ffd23f); }
        .rating-score { font-weight: bold; color: #ff6b35; }
        .overall-score { font-size: 2rem; font-weight: bold; color: #ff6b35; text-align: center; }
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
                <li><a href="hotels.php">Hotels</a></li>
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
        <h1>Hotel Reviews & Ratings</h1>
        
        <?php foreach ($hotels as $hotel): ?>
        <div class="hotel-card card">
            <img src="assets/images/placeholder.jpg" alt="<?= htmlspecialchars($hotel['name']) ?>" class="card-img">
            <div class="card-content">
                <h2><?= htmlspecialchars($hotel['name']) ?></h2>
                <p>📍 <?= htmlspecialchars($hotel['city']) ?>, <?= htmlspecialchars($hotel['country']) ?></p>
                <p><?= str_repeat('⭐', $hotel['star_rating']) ?> <?= $hotel['star_rating'] ?> Star Hotel</p>
                
                <div class="imdb-rating">
                    <div class="overall-score"><?= $hotel['avg_rating'] ? number_format($hotel['avg_rating'], 1) : '0.0' ?>/10</div>
                    <p style="text-align: center; margin-bottom: 1rem;">Based on <?= $hotel['review_count'] ?> reviews</p>
                    
                    <div class="rating-bar">
                        <span class="rating-label">Cleanliness</span>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?= rand(70, 95) ?>%;"></div>
                        </div>
                        <span class="rating-score"><?= number_format(rand(70, 95)/10, 1) ?></span>
                    </div>
                    
                    <div class="rating-bar">
                        <span class="rating-label">Service</span>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?= rand(70, 95) ?>%;"></div>
                        </div>
                        <span class="rating-score"><?= number_format(rand(70, 95)/10, 1) ?></span>
                    </div>
                    
                    <div class="rating-bar">
                        <span class="rating-label">Facilities</span>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?= rand(70, 95) ?>%;"></div>
                        </div>
                        <span class="rating-score"><?= number_format(rand(70, 95)/10, 1) ?></span>
                    </div>
                    
                    <div class="rating-bar">
                        <span class="rating-label">Location</span>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?= rand(70, 95) ?>%;"></div>
                        </div>
                        <span class="rating-score"><?= number_format(rand(70, 95)/10, 1) ?></span>
                    </div>
                    
                    <div class="rating-bar">
                        <span class="rating-label">Value</span>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?= rand(70, 95) ?>%;"></div>
                        </div>
                        <span class="rating-score"><?= number_format(rand(70, 95)/10, 1) ?></span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button class="btn btn-primary">Book Now - $<?= number_format($hotel['price_per_night'], 2) ?>/night</button>
                    <a href="hotel.php?id=<?= $hotel['id'] ?>" class="btn btn-secondary">View Reviews</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 RestaurantBook - Restaurant Review Portal. All rights reserved.</p>
            <p>Developer Team: Junaid Jabbar Faizi, Sami Ullah Khan, Usman Ehsan</p>
            <p><a href="index.php" style="color: #ccc;">Home</a> | <a href="search.php" style="color: #ccc;">Restaurants</a> | <a href="hotels.php" style="color: #ccc;">Hotels</a> | <a href="review.php" style="color: #ccc;">Write Review</a> | <a href="login.php" style="color: #ccc;">Login</a></p>
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