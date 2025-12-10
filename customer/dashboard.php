<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$db = new PDO("mysql:host=localhost;dbname=restaurant_review_portal", "root", "");
$user_id = $_SESSION['user_id'];

// Handle booking cancellation
if (isset($_GET['cancel'])) {
    $booking_id = $_GET['cancel'];
    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$booking_id, $user_id])) {
        header('Location: dashboard.php?success=booking_cancelled');
        exit();
    }
}

// Get upcoming bookings
$stmt = $db->prepare("SELECT b.*, 
                            r.name as restaurant_name, r.city as restaurant_city,
                            h.name as hotel_name, h.city as hotel_city
                     FROM bookings b 
                     LEFT JOIN restaurants r ON b.restaurant_id = r.id 
                     LEFT JOIN hotels h ON b.hotel_id = h.id 
                     WHERE b.user_id = ? AND b.booking_date >= CURDATE() 
                     ORDER BY b.booking_date ASC");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchAll();

// Get past bookings
$stmt = $db->prepare("SELECT b.*, 
                            r.name as restaurant_name, r.city as restaurant_city,
                            h.name as hotel_name, h.city as hotel_city,
                            (SELECT COUNT(*) FROM reviews WHERE customer_id = b.user_id AND (restaurant_id = b.restaurant_id OR hotel_id = b.hotel_id)) as has_review
                     FROM bookings b 
                     LEFT JOIN restaurants r ON b.restaurant_id = r.id 
                     LEFT JOIN hotels h ON b.hotel_id = h.id 
                     WHERE b.user_id = ? AND b.booking_date < CURDATE() 
                     ORDER BY b.booking_date DESC 
                     LIMIT 10");
$stmt->execute([$user_id]);
$past_bookings = $stmt->fetchAll();

// Get saved restaurants (placeholder - table doesn't exist)
$saved_restaurants = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - RestaurantBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li><a href="../index.php">Home</a></li>
                <li><a href="../search.php">Restaurants</a></li>
                <li><a href="../hotel-search.php">Hotels</a></li>
                <li><a href="../review.php">Write Review</a></li>
                <li><a href="dashboard.php">My Bookings</a></li>
                <li><a href="../logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin: 2rem auto;">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>

        <!-- Dashboard Navigation -->
        <div class="dashboard-nav">
            <ul>
                <li><a href="#upcoming" class="active">Upcoming Bookings</a></li>
                <li><a href="#past">Past Bookings</a></li>
                <li><a href="#saved">Saved Restaurants</a></li>
            </ul>
        </div>

        <!-- Upcoming Bookings -->
        <section id="upcoming" style="margin-bottom: 3rem;">
            <h2>Upcoming Bookings</h2>
            <?php if (empty($upcoming_bookings)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <h3>No upcoming bookings</h3>
                    <p>Ready to discover your next dining experience?</p>
                    <a href="../search.php" class="btn btn-primary">Browse Restaurants</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Restaurant</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <?php if ($booking['restaurant_id']): ?>
                                            <strong><?php echo htmlspecialchars($booking['restaurant_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['restaurant_city']); ?></small>
                                        <?php else: ?>
                                            <strong><?php echo htmlspecialchars($booking['hotel_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['hotel_city']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                    </td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                            <a href="?cancel=<?php echo $booking['id']; ?>" class="btn btn-danger" style="font-size: 0.8rem; padding: 0.5rem 1rem;" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Past Bookings -->
        <section id="past" style="margin-bottom: 3rem;">
            <h2>Past Bookings</h2>
            <?php if (empty($past_bookings)): ?>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <p>No past bookings found.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Restaurant</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($past_bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <?php if ($booking['restaurant_id']): ?>
                                            <strong><?php echo htmlspecialchars($booking['restaurant_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['restaurant_city']); ?></small>
                                        <?php else: ?>
                                            <strong><?php echo htmlspecialchars($booking['hotel_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['hotel_city']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                    </td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status'] == 'confirmed' ? 'success' : 'info'; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] == 'confirmed' && $booking['has_review'] == 0): ?>
                                            <?php if ($booking['restaurant_id']): ?>
                                                <a href="../review.php?restaurant_id=<?php echo $booking['restaurant_id']; ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Review</a>
                                            <?php else: ?>
                                                <a href="../review.php?hotel_id=<?php echo $booking['hotel_id']; ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Review</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Saved Restaurants -->
        <section id="saved">
            <h2>Saved Restaurants</h2>
            <?php if (empty($saved_restaurants)): ?>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <p>No saved restaurants yet.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($saved_restaurants as $restaurant): ?>
                        <div class="card">
                            <img src="<?php echo $restaurant['image_path'] ? '../uploads/' . $restaurant['image_path'] : '../assets/images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="card-img">
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
                                </div>
                                <div class="price">$<?php echo number_format($restaurant['price_per_person'], 2); ?> per person</div>
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                    <a href="../restaurant.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-primary" style="flex: 1;">View</a>
                                    <a href="remove-saved.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-secondary" style="padding: 12px;">♥</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 RestaurantBook. All rights reserved.</p>
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