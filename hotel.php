<?php
session_start();
require_once 'config/database.php';

$hotel_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ? AND status = 'approved'");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    header('Location: hotels.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT r.*, 
           CASE 
               WHEN r.is_anonymous = 1 THEN COALESCE(r.reviewer_name, 'Anonymous')
               ELSE CONCAT(u.first_name, ' ', SUBSTRING(u.last_name, 1, 1), '.')
           END as display_name
    FROM reviews r
    LEFT JOIN users u ON r.customer_id = u.id
    WHERE r.hotel_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt->execute([$hotel_id]);
$reviews = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT AVG(overall_rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE hotel_id = ? AND status = 'approved'");
$stmt->execute([$hotel_id]);
$rating_data = $stmt->fetch();
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['total_reviews'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($hotel['name']) ?> - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hotel-header { margin-bottom: 3rem; }
        .review-item { border-bottom: 1px solid #eee; padding: 1.5rem 0; }
        .review-actions { display: flex; gap: 1rem; align-items: center; }
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
        <div class="hotel-header">
            <img src="assets/images/placeholder.jpg" alt="<?= htmlspecialchars($hotel['name']) ?>" style="width: 100%; height: 300px; object-fit: cover; margin-bottom: 2rem;">
            
            <h1><?= htmlspecialchars($hotel['name']) ?></h1>
            <p>📍 <?= htmlspecialchars($hotel['city']) ?>, <?= htmlspecialchars($hotel['country']) ?></p>
            <p><?= str_repeat('⭐', $hotel['star_rating']) ?> <?= $hotel['star_rating'] ?> Star Hotel</p>
            <div class="rating">
                <span class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= round($avg_rating) ? '★' : '☆' ?>
                    <?php endfor; ?>
                </span>
                <span><?= $avg_rating ?> (<?= $total_reviews ?> reviews)</span>
            </div>
            <p><?= nl2br(htmlspecialchars($hotel['description'])) ?></p>
            <p><strong>Price:</strong> $<?= number_format($hotel['price_per_night'], 2) ?>/night</p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="margin-top: 2rem;">
                    <a href="booking.php?hotel_id=<?= $hotel['id'] ?>" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">Book Now</a>
                </div>
            <?php else: ?>
                <div style="margin-top: 2rem;">
                    <a href="login.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">Login to Book</a>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Reviews (<?= $total_reviews ?>)</h2>
                <a href="review.php?hotel_id=<?= $hotel['id'] ?>" class="btn btn-primary">Write Review</a>
            </div>
            
            <div id="reviews-container">
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet. Be the first to review!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item" data-review-id="<?= $review['id'] ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h4><?= htmlspecialchars($review['display_name']) ?></h4>
                                    <div class="rating">
                                        <span class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?= $i <= round($review['overall_rating']) ? '★' : '☆' ?>
                                            <?php endfor; ?>
                                        </span>
                                        <span><?= number_format($review['overall_rating'], 1) ?>/5</span>
                                    </div>
                                </div>
                                <span style="color: #666; font-size: 0.9rem;"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                            </div>
                            <?php if ($review['review_text']): ?>
                                <p style="margin-bottom: 1rem;"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                            <?php endif; ?>
                            
                            <div class="review-actions">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="like-btn" onclick="toggleLike(<?= $review['id'] ?>, 'like')" style="background: none; border: none; cursor: pointer; color: #666;">
                                        👍 <span class="like-count"><?= $review['likes_count'] ?? 0 ?></span>
                                    </button>
                                    <button class="dislike-btn" onclick="toggleLike(<?= $review['id'] ?>, 'dislike')" style="background: none; border: none; cursor: pointer; color: #666;">
                                        👎 <span class="dislike-count"><?= $review['dislikes_count'] ?? 0 ?></span>
                                    </button>
                                <?php else: ?>
                                    <span style="color: #666;">👍 <?= $review['likes_count'] ?? 0 ?> 👎 <?= $review['dislikes_count'] ?? 0 ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
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