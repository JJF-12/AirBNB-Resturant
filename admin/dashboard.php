<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new PDO("mysql:host=localhost;dbname=restaurant_review_portal", "root", "");

// Get system statistics
$stmt = $db->query("SELECT COUNT(*) as total_users FROM users WHERE user_type != 'admin'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $db->query("SELECT COUNT(*) as total_hosts FROM users WHERE user_type = 'host'");
$total_hosts = $stmt->fetch()['total_hosts'];

$stmt = $db->query("SELECT COUNT(*) as total_customers FROM users WHERE user_type = 'customer'");
$total_customers = $stmt->fetch()['total_customers'];

$stmt = $db->query("SELECT COUNT(*) as total_restaurants FROM restaurants");
$total_restaurants = $stmt->fetch()['total_restaurants'];

$stmt = $db->query("SELECT COUNT(*) as pending_restaurants FROM restaurants WHERE status = 'pending'");
$pending_restaurants = $stmt->fetch()['pending_restaurants'];

$stmt = $db->query("SELECT COUNT(*) as total_bookings FROM bookings");
$total_bookings = $stmt->fetch()['total_bookings'];

$stmt = $db->query("SELECT COUNT(*) as pending_reviews FROM reviews WHERE status = 'pending'");
$pending_reviews = $stmt->fetch()['pending_reviews'];

$stmt = $db->query("SELECT COALESCE(SUM(total_price), 0) as total_revenue FROM bookings WHERE status = 'confirmed'");
$total_revenue = $stmt->fetch()['total_revenue'];

// Get recent activities
$stmt = $db->query("SELECT 'user' as type, CONCAT(first_name, ' ', last_name) as title, 'registered' as action, created_at 
                   FROM users WHERE user_type != 'admin'
                   UNION ALL
                   SELECT 'restaurant' as type, name as title, 'submitted for approval' as action, created_at 
                   FROM restaurants WHERE status = 'pending'
                   UNION ALL
                   SELECT 'booking' as type, CONCAT('Booking #', id) as title, 'created' as action, created_at 
                   FROM bookings
                   ORDER BY created_at DESC 
                   LIMIT 10");
$recent_activities = $stmt->fetchAll();

// Get pending items
$stmt = $db->query("SELECT r.*, u.first_name, u.last_name 
                   FROM restaurants r 
                   JOIN users u ON r.host_id = u.id 
                   WHERE r.status = 'pending' 
                   ORDER BY r.created_at ASC 
                   LIMIT 5");
$pending_listings = $stmt->fetchAll();

$stmt = $db->query("SELECT r.*, u.first_name, u.last_name, 
                          COALESCE(rest.name, h.name) as place_name
                   FROM reviews r 
                   JOIN users u ON r.customer_id = u.id 
                   LEFT JOIN restaurants rest ON r.restaurant_id = rest.id
                   LEFT JOIN hotels h ON r.hotel_id = h.id
                   WHERE r.status = 'pending' 
                   ORDER BY r.created_at ASC 
                   LIMIT 5");
$pending_review_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RestaurantBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="restaurants.php">Restaurants</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="reviews.php">Reviews</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="margin: 2rem auto;">
        <h1>Admin Dashboard</h1>
        <p>System overview and management</p>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_users; ?></span>
                <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_hosts; ?></span>
                <span class="stat-label">Hosts</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_customers; ?></span>
                <span class="stat-label">Customers</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_restaurants; ?></span>
                <span class="stat-label">Restaurants</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $pending_restaurants; ?></span>
                <span class="stat-label">Pending Listings</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_bookings; ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $pending_reviews; ?></span>
                <span class="stat-label">Pending Reviews</span>
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
                    <a href="restaurants.php?status=pending" class="btn btn-warning">Review Pending Listings (<?php echo $pending_restaurants; ?>)</a>
                    <a href="reviews.php?status=pending" class="btn btn-info">Review Pending Reviews (<?php echo $pending_reviews; ?>)</a>
                    <a href="users.php" class="btn btn-secondary">Manage Users</a>
                    <a href="settings.php" class="btn btn-secondary">System Settings</a>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Pending Listings -->
            <section>
                <h2>Pending Restaurant Listings</h2>
                <?php if (empty($pending_listings)): ?>
                    <div class="card" style="text-align: center; padding: 2rem;">
                        <p>No pending listings</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Restaurant</th>
                                    <th>Host</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_listings as $listing): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($listing['name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($listing['cuisine_type']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($listing['first_name'] . ' ' . $listing['last_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></td>
                                        <td>
                                            <a href="review-listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="restaurants.php?status=pending" class="btn btn-secondary">View All Pending</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Pending Reviews -->
            <section>
                <h2>Pending Reviews</h2>
                <?php if (empty($pending_review_list)): ?>
                    <div class="card" style="text-align: center; padding: 2rem;">
                        <p>No pending reviews</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Restaurant</th>
                                    <th>Customer</th>
                                    <th>Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_review_list as $review): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($review['place_name']); ?></td>
                                        <td><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></td>
                                        <td>
                                            <span class="stars">
                                                <?php 
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= round($review['overall_rating']) ? '★' : '☆';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="review-review.php?id=<?php echo $review['id']; ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="reviews.php?status=pending" class="btn btn-secondary">View All Pending</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Recent Activity -->
        <section style="margin-top: 3rem;">
            <h2>Recent Activity</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo $activity['type'] == 'user' ? 'info' : ($activity['type'] == 'restaurant' ? 'warning' : 'success'); ?>">
                                        <?php echo ucfirst($activity['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($activity['title'] . ' ' . $activity['action']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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