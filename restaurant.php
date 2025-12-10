<?php
session_start();

$db = new PDO("mysql:host=localhost;dbname=restaurant_review_portal", "root", "");
$restaurant_id = $_GET['id'] ?? 0;

// Get restaurant details
$stmt = $db->prepare("SELECT r.*, u.first_name, u.last_name, u.email
                     FROM restaurants r 
                     JOIN users u ON r.host_id = u.id 
                     WHERE r.id = ? AND r.status = 'approved'");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header('Location: search.php');
    exit();
}

// Get restaurant images
$stmt = $db->prepare("SELECT * FROM restaurant_images WHERE restaurant_id = ? ORDER BY is_primary DESC");
$stmt->execute([$restaurant_id]);
$images = $stmt->fetchAll();

// Get menu items
$stmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name");
$stmt->execute([$restaurant_id]);
$menu_items = $stmt->fetchAll();

// Get reviews
$stmt = $db->prepare("SELECT r.*, 
                            CASE 
                                WHEN r.is_anonymous = 1 THEN COALESCE(r.reviewer_name, 'Anonymous')
                                ELSE CONCAT(u.first_name, ' ', SUBSTRING(u.last_name, 1, 1), '.')
                            END as display_name
                     FROM reviews r 
                     LEFT JOIN users u ON r.customer_id = u.id 
                     WHERE r.restaurant_id = ? AND r.status = 'approved' 
                     ORDER BY r.created_at DESC 
                     LIMIT 10");
$stmt->execute([$restaurant_id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $db->prepare("SELECT AVG(overall_rating) as avg_rating, COUNT(*) as total_reviews 
                     FROM reviews 
                     WHERE restaurant_id = ? AND status = 'approved'");
$stmt->execute([$restaurant_id]);
$rating_data = $stmt->fetch();

$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['total_reviews'];

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isHost() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'host';
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .image-gallery {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 200px 200px;
            gap: 10px;
            margin-bottom: 2rem;
            border-radius: 12px;
            overflow: hidden;
        }
        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        .gallery-img:first-child {
            grid-row: 1 / 3;
        }
        .booking-widget {
            position: sticky;
            top: 100px;
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .menu-category {
            margin-bottom: 2rem;
        }
        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 1.5rem 0;
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="search.php">Browse</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isHost()): ?>
                        <li><a href="host/dashboard.php">Host Dashboard</a></li>
                    <?php elseif (isAdmin()): ?>
                        <li><a href="admin/dashboard.php">Admin</a></li>
                    <?php else: ?>
                        <li><a href="customer/dashboard.php">My Bookings</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin: 2rem auto;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3rem;">
            <!-- Main Content -->
            <div>
                <!-- Image Gallery -->
                <div class="image-gallery">
                    <?php if (empty($images)): ?>
                        <img src="assets/images/placeholder.jpg" alt="Restaurant" class="gallery-img">
                    <?php else: ?>
                        <?php foreach (array_slice($images, 0, 5) as $image): ?>
                            <img src="assets/images/<?php echo $image['image_path']; ?>" alt="Restaurant" class="gallery-img">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Restaurant Info -->
                <div style="margin-bottom: 3rem;">
                    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                    <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 1rem;">
                        <div class="rating">
                            <span class="stars" style="font-size: 1.2rem;">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= round($avg_rating) ? '★' : '☆';
                                }
                                ?>
                            </span>
                            <span><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> reviews)</span>
                        </div>
                        <span><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></span>
                        <span><?php echo htmlspecialchars($restaurant['city']); ?></span>
                    </div>
                    <p style="font-size: 1.1rem; line-height: 1.6; color: #666;">
                        <?php echo nl2br(htmlspecialchars($restaurant['description'])); ?>
                    </p>
                </div>

                <!-- Menu -->
                <?php if (!empty($menu_items)): ?>
                    <div style="margin-bottom: 3rem;">
                        <h2 style="margin-bottom: 2rem;">Menu</h2>
                        <?php 
                        $grouped_menu = [];
                        foreach ($menu_items as $item) {
                            $category = $item['category'] ?: 'Other';
                            $grouped_menu[$category][] = $item;
                        }
                        ?>
                        <?php foreach ($grouped_menu as $category => $items): ?>
                            <div class="menu-category">
                                <h3 style="margin-bottom: 1rem; color: #ff5a5f;"><?php echo htmlspecialchars($category); ?></h3>
                                <?php foreach ($items as $item): ?>
                                    <div class="menu-item">
                                        <div>
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <?php if ($item['description']): ?>
                                                <p style="color: #666; margin: 0.5rem 0 0 0;"><?php echo htmlspecialchars($item['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Location -->
                <div style="margin-bottom: 3rem;">
                    <h2 style="margin-bottom: 1rem;">Location</h2>
                    <p><?php echo htmlspecialchars($restaurant['address']); ?></p>
                    <p><?php echo htmlspecialchars($restaurant['city'] . ', ' . $restaurant['country']); ?></p>
                </div>

                <!-- House Rules -->
                <?php if (isset($restaurant['house_rules']) && $restaurant['house_rules']): ?>
                    <div style="margin-bottom: 3rem;">
                        <h2 style="margin-bottom: 1rem;">House Rules</h2>
                        <p><?php echo nl2br(htmlspecialchars($restaurant['house_rules'])); ?></p>
                    </div>
                <?php endif; ?>



                <!-- Reviews -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2>Reviews (<?php echo $total_reviews; ?>)</h2>
                        <a href="review.php?restaurant_id=<?php echo $restaurant['id']; ?>" class="btn btn-primary">Write Review</a>
                    </div>
                    
                    <div id="reviews-container">
                        <?php if (empty($reviews)): ?>
                            <p>No reviews yet. Be the first to review!</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item" data-review-id="<?php echo $review['id']; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div>
                                            <h4><?php echo htmlspecialchars($review['display_name']); ?></h4>
                                            <div class="rating">
                                                <span class="stars">
                                                    <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= round($review['overall_rating']) ? '★' : '☆';
                                                    }
                                                    ?>
                                                </span>
                                                <span><?php echo number_format($review['overall_rating'], 1); ?>/5</span>
                                            </div>
                                        </div>
                                        <span style="color: #666; font-size: 0.9rem;"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <?php if ($review['review_text']): ?>
                                        <p style="margin-bottom: 1rem;"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="review-actions" style="display: flex; gap: 1rem; align-items: center;">
                                        <?php if (isLoggedIn()): ?>
                                            <button class="like-btn" onclick="toggleLike(<?php echo $review['id']; ?>, 'like')" style="background: none; border: none; cursor: pointer; color: #666;">
                                                👍 <span class="like-count"><?php echo $review['likes_count'] ?? 0; ?></span>
                                            </button>
                                            <button class="dislike-btn" onclick="toggleLike(<?php echo $review['id']; ?>, 'dislike')" style="background: none; border: none; cursor: pointer; color: #666;">
                                                👎 <span class="dislike-count"><?php echo $review['dislikes_count'] ?? 0; ?></span>
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #666;">👍 <?php echo $review['likes_count'] ?? 0; ?> 👎 <?php echo $review['dislikes_count'] ?? 0; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Widget -->
            <div>
                <div class="booking-widget">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <span class="price" style="font-size: 1.5rem;">$<?php echo number_format($restaurant['price_per_person'], 2); ?></span>
                        <span> per person</span>
                    </div>

                    <?php if (isLoggedIn() && !isHost()): ?>
                        <a href="booking.php?restaurant_id=<?php echo $restaurant['id']; ?>" class="btn btn-primary" style="width: 100%; display: block; text-align: center; text-decoration: none;">Book Now</a>
                    <?php elseif (!isLoggedIn()): ?>
                        <div style="text-align: center;">
                            <p>Please login to make a booking</p>
                            <a href="login.php" class="btn btn-primary" style="width: 100%;">Login</a>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center;">
                            <p>Hosts cannot book their own restaurants</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 RestaurantBook. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleLike(reviewId, type) {
            fetch('review_interaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    review_id: reviewId,
                    type: type
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const reviewItem = document.querySelector(`[data-review-id="${reviewId}"]`);
                    reviewItem.querySelector('.like-count').textContent = data.likes;
                    reviewItem.querySelector('.dislike-count').textContent = data.dislikes;
                }
            });
        }
    </script>
</body>
</html>