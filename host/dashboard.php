<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'host') {
    header('Location: ../login.php');
    exit();
}

$db = new PDO("mysql:host=localhost;dbname=restaurant_review_portal", "root", "");
$host_id = $_SESSION['user_id'];

// Get host statistics
$stmt = $db->prepare("SELECT COUNT(*) as total_listings FROM restaurants WHERE host_id = ?");
$stmt->execute([$host_id]);
$total_listings = $stmt->fetch()['total_listings'];

$stmt = $db->prepare("SELECT COUNT(*) as pending_bookings FROM bookings b 
                     JOIN restaurants r ON b.restaurant_id = r.id 
                     WHERE r.host_id = ? AND b.status = 'pending'");
$stmt->execute([$host_id]);
$pending_bookings = $stmt->fetch()['pending_bookings'];

$stmt = $db->prepare("SELECT COUNT(*) as total_bookings FROM bookings b 
                     JOIN restaurants r ON b.restaurant_id = r.id 
                     WHERE r.host_id = ?");
$stmt->execute([$host_id]);
$total_bookings = $stmt->fetch()['total_bookings'];

$stmt = $db->prepare("SELECT COALESCE(SUM(b.total_price), 0) as total_revenue FROM bookings b 
                     JOIN restaurants r ON b.restaurant_id = r.id 
                     WHERE r.host_id = ? AND b.status = 'confirmed'");
$stmt->execute([$host_id]);
$total_revenue = $stmt->fetch()['total_revenue'];

// Get recent bookings
$stmt = $db->prepare("SELECT b.*, r.name as restaurant_name, u.first_name, u.last_name, u.email 
                     FROM bookings b 
                     JOIN restaurants r ON b.restaurant_id = r.id 
                     JOIN users u ON b.user_id = u.id 
                     WHERE r.host_id = ? 
                     ORDER BY b.created_at DESC 
                     LIMIT 10");
$stmt->execute([$host_id]);
$recent_bookings = $stmt->fetchAll();

// Get host restaurants
$stmt = $db->prepare("SELECT r.*, COUNT(b.id) as booking_count, AVG(rev.overall_rating) as avg_rating
                     FROM restaurants r 
                     LEFT JOIN bookings b ON r.id = b.restaurant_id
                     LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.status = 'approved'
                     WHERE r.host_id = ? 
                     GROUP BY r.id 
                     ORDER BY r.created_at DESC");
$stmt->execute([$host_id]);
$restaurants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard - RestaurantBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add-listing.php">Add Restaurant</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin: 2rem auto;">
        <h1>Host Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Host'); ?>!</p>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_listings; ?></span>
                <span class="stat-label">Total Listings</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $pending_bookings; ?></span>
                <span class="stat-label">Pending Bookings</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_bookings; ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">$<?php echo number_format($total_revenue, 2); ?></span>
                <span class="stat-label">Total Revenue</span>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-content">
                <h2>Quick Actions</h2>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="add-listing.php" class="btn btn-primary">Add New Restaurant</a>
                    <a href="bookings.php" class="btn btn-secondary">Manage Bookings</a>
                    <a href="calendar.php" class="btn btn-secondary">Calendar</a>
                    <a href="reviews.php" class="btn btn-secondary">Reviews</a>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <section style="margin-bottom: 3rem;">
            <h2>Recent Bookings</h2>
            <?php if (empty($recent_bookings)): ?>
                <div class="card" style="text-align: center; padding: 2rem;">
                    <p>No bookings yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Restaurant</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?><br>
                                        <small><?php echo htmlspecialchars($booking['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['restaurant_name']); ?></td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?><br>
                                        <small>Evening Dining</small>
                                    </td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'pending' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="approve-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-success" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Approve</a>
                                            <a href="decline-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Decline</a>
                                        <?php else: ?>
                                            <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- My Restaurants -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>My Restaurants</h2>
                <a href="add-listing.php" class="btn btn-primary">Add New Restaurant</a>
            </div>
            
            <?php if (empty($restaurants)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <h3>No restaurants listed yet</h3>
                    <p>Start by adding your first restaurant listing</p>
                    <a href="add-listing.php" class="btn btn-primary">Add Restaurant</a>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($restaurants as $restaurant): ?>
                        <div class="card">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                <p class="card-text"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?> • <?php echo htmlspecialchars($restaurant['city']); ?></p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                                    <span class="badge badge-<?php echo $restaurant['status'] == 'approved' ? 'success' : ($restaurant['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($restaurant['status']); ?>
                                    </span>
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
                                </div>
                                <p class="card-text">
                                    <strong><?php echo $restaurant['booking_count']; ?></strong> bookings<br>
                                    <strong>$<?php echo number_format($restaurant['price_per_person'], 2); ?></strong> per person
                                </p>
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                    <a href="../restaurant.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-secondary" style="flex: 1;">View</a>
                                    <a href="edit-listing.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-primary" style="flex: 1;">Edit</a>
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

    <script src="../assets/js/main.js"></script>
</body>
</html>