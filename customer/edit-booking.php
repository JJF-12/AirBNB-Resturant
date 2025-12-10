<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$db = new PDO("mysql:host=localhost;dbname=restaurant_review_portal", "root", "");
$booking_id = $_GET['id'] ?? 0;

// Get booking details
$stmt = $db->prepare("SELECT b.*, 
                            r.name as restaurant_name, r.capacity as restaurant_capacity, r.price_per_person,
                            h.name as hotel_name, h.total_rooms as hotel_capacity, h.price_per_night
                     FROM bookings b 
                     LEFT JOIN restaurants r ON b.restaurant_id = r.id 
                     LEFT JOIN hotels h ON b.hotel_id = h.id 
                     WHERE b.id = ? AND b.user_id = ? AND b.status = 'pending'");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = $_POST['booking_date'];
    $guests = $_POST['guests'];
    
    if (empty($booking_date) || empty($guests)) {
        $error = 'Please fill in all required fields';
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'Booking date cannot be in the past';
    } else {
        $price_field = $booking['restaurant_id'] ? 'price_per_person' : 'price_per_night';
        $total_price = $guests * $booking[$price_field];
        
        $stmt = $db->prepare("UPDATE bookings SET booking_date = ?, guests = ?, total_price = ? WHERE id = ? AND user_id = ?");
        
        if ($stmt->execute([$booking_date, $guests, $total_price, $booking_id, $_SESSION['user_id']])) {
            header('Location: dashboard.php?success=booking_updated');
            exit();
        } else {
            $error = 'Failed to update booking. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Booking - RestaurantBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="dashboard.php">My Bookings</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="max-width: 600px; margin: 2rem auto;">
        <h1>Edit Booking</h1>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 2rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-content">
                <h2><?php echo htmlspecialchars($booking['restaurant_name'] ?? $booking['hotel_name']); ?></h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="booking_date" class="form-label">Date *</label>
                        <input type="date" id="booking_date" name="booking_date" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo $booking['booking_date']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="guests" class="form-label">Number of <?php echo $booking['restaurant_id'] ? 'Guests' : 'Rooms'; ?> *</label>
                        <select id="guests" name="guests" class="form-control" required onchange="updateTotal()">
                            <?php 
                            $capacity = $booking['restaurant_id'] ? $booking['restaurant_capacity'] : $booking['hotel_capacity'];
                            for ($i = 1; $i <= min(20, $capacity); $i++): 
                            ?>
                                <option value="<?php echo $i; ?>" <?php echo $booking['guests'] == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> <?php echo $booking['restaurant_id'] ? 'guest' . ($i > 1 ? 's' : '') : 'room' . ($i > 1 ? 's' : ''); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div style="border-top: 1px solid #eee; padding-top: 1rem; margin: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Price per <?php echo $booking['restaurant_id'] ? 'person' : 'night'; ?>:</span>
                            <span>$<?php echo number_format($booking['restaurant_id'] ? $booking['price_per_person'] : $booking['price_per_night'], 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span><?php echo $booking['restaurant_id'] ? 'Guests' : 'Rooms'; ?>:</span>
                            <span id="guestCount"><?php echo $booking['guests']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                            <span>Total:</span>
                            <span id="totalAmount">$<?php echo number_format($booking['total_price'], 2); ?></span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Update Booking</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const pricePerUnit = <?php echo $booking['restaurant_id'] ? $booking['price_per_person'] : $booking['price_per_night']; ?>;
        
        function updateTotal() {
            const guests = document.getElementById('guests').value;
            const total = guests * pricePerUnit;
            
            document.getElementById('guestCount').textContent = guests;
            document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
        }
    </script>
</body>
</html>