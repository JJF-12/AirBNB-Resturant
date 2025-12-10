<?php
require_once 'includes/session.php';
$db = new Database();

// Get featured restaurants
$db->query("SELECT r.*, AVG(rev.overall_rating) as avg_rating, COUNT(rev.id) as review_count 
           FROM restaurants r 
           LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.status = 'approved'
           WHERE r.status = 'approved' 
           GROUP BY r.id 
           ORDER BY avg_rating DESC, review_count DESC 
           LIMIT 6");
$featured_restaurants = $db->resultset();

// Get popular cuisines
$db->query("SELECT cuisine_type, COUNT(*) as count FROM restaurants WHERE status = 'approved' GROUP BY cuisine_type ORDER BY count DESC LIMIT 8");
$popular_cuisines = $db->resultset();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestaurantBook - Restaurant Review Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <?php if (isLoggedIn()): ?>
                    <?php if (isHost()): ?>
                        <li><a href="host/dashboard.php">Dashboard</a></li>
                    <?php elseif (isAdmin()): ?>
                        <li><a href="admin/dashboard.php">Admin</a></li>
                    <?php else: ?>
                        <li><a href="customer/dashboard.php">My Bookings</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="btn-logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Hero Section with Search -->
        <section class="search-section">
            <div class="container">
                <h1>Restaurant Review Portal</h1>
                <p>Discover, review and book restaurants & hotels worldwide</p>
                
                <form class="search-form" id="searchForm" action="search.php" method="GET">
                    <input type="text" name="location" id="location" placeholder="Location (restaurants & hotels)" class="search-input" required>
                    <input type="date" name="date" id="date" class="search-input" required>
                    <input type="number" name="guests" id="guests" placeholder="Guests" min="1" max="20" class="search-input" required>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </section>

        <!-- Quick Access -->
        <section class="container" style="margin: 4rem auto; text-align: center;">
            <div style="display: flex; gap: 2rem; justify-content: center; margin-bottom: 4rem; flex-wrap: wrap;">
                <a href="search.php" class="btn btn-primary" style="padding: 1.5rem 3rem; font-size: 1.2rem;">Browse Restaurants</a>
                <a href="hotel-search.php" class="btn btn-secondary" style="padding: 1.5rem 3rem; font-size: 1.2rem;">Browse Hotels</a>
                <a href="review.php" class="btn btn-success" style="padding: 1.5rem 3rem; font-size: 1.2rem;">Write Review</a>
            </div>
        </section>

        <!-- Featured Restaurants -->
        <section class="container" style="margin: 4rem auto;">
            <h2 style="text-align: center; margin-bottom: 2rem; font-size: 2.5rem;">Featured Restaurants</h2>
            <div class="grid">
                <?php foreach ($featured_restaurants as $restaurant): ?>
                    <div class="card">
                        <?php 
                        $cuisine_name = strtolower(str_replace(' ', '-', $restaurant['cuisine_type']));
                        $image_file = 'cuisine-' . $cuisine_name . '.jpg';
                        ?>
                        <img src="assets/images/<?php echo $image_file; ?>" 
                             alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="card-img"
                             onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                            <p class="card-text"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?> • <?php echo htmlspecialchars($restaurant['city']); ?></p>
                            <div class="rating">
                                <span class="stars">
                                    <?php 
                                    $rating = $restaurant['avg_rating'] ? round($restaurant['avg_rating']) : 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                                <span>(<?php echo $restaurant['review_count']; ?> reviews)</span>
                            </div>
                            <div class="price">$<?php echo number_format($restaurant['price_per_person'], 2); ?> per person</div>
                            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <a href="restaurant.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-primary" style="flex: 1;">View Details</a>
                                <a href="restaurant.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-secondary">Reviews</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Popular Cuisines -->
        <section class="container" style="margin: 4rem auto;">
            <h2 style="text-align: center; margin-bottom: 2rem; font-size: 2.5rem;">Popular Cuisines</h2>
            <div class="grid">
                <?php foreach ($popular_cuisines as $cuisine): ?>
                    <div class="card" style="text-align: center;" onclick="window.location.href='search.php?cuisine=<?php echo urlencode($cuisine['cuisine_type']); ?>'">
                        <?php 
                        $cuisine_name = strtolower(str_replace(' ', '-', $cuisine['cuisine_type']));
                        $image_file = 'cuisine-' . $cuisine_name . '.jpg';
                        ?>
                        <img src="assets/images/<?php echo $image_file; ?>" 
                             alt="<?php echo htmlspecialchars($cuisine['cuisine_type']); ?>" class="card-img"
                             onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($cuisine['cuisine_type']); ?></h3>
                            <p class="card-text"><?php echo $cuisine['count']; ?> restaurants</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Platform Features -->
        <section class="container" style="margin: 4rem auto; text-align: center;">
            <h2 style="margin-bottom: 3rem; font-size: 2.5rem;">Platform Features</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">🍽️ Restaurant Reviews</h3>
                        <p class="card-text">Multi-category ratings for food quality, service & value</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">🏨 Hotel Reviews</h3>
                        <p class="card-text">IMDb-style ratings for cleanliness, facilities & location</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">📝 Moderated Reviews</h3>
                        <p class="card-text">All reviews moderated before publication</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">💬 Comment System</h3>
                        <p class="card-text">Users can comment on reviews with host responses</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">📱 Responsive Design</h3>
                        <p class="card-text">Full functionality on desktop and mobile devices</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3 class="card-title">🔒 Secure Platform</h3>
                        <p class="card-text">Authentication, session management & CSRF protection</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 RestaurantBook - Restaurant Review Portal. All rights reserved.</p>
            <p>Developer Team: Junaid Jabbar Faizi, Sami Ullah Khan, Usman Ehsan</p>
            <p><a href="index.php" style="color: #ccc;">Home</a> | <a href="search.php" style="color: #ccc;">Restaurants</a> | <a href="hotel-search.php" style="color: #ccc;">Hotels</a> | <a href="review.php" style="color: #ccc;">Write Review</a> | <a href="login.php" style="color: #ccc;">Login</a></p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
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