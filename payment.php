<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new PDO("mysql:host=localhost;dbname=restaurant_review_portal", "root", "");
$booking_id = $_GET['booking_id'] ?? 0;

// Get booking details with restaurant or hotel info
$stmt = $db->prepare("SELECT b.*, 
                            r.name as restaurant_name, r.address as restaurant_address, r.city as restaurant_city,
                            h.name as hotel_name, h.address as hotel_address, h.city as hotel_city
                     FROM bookings b 
                     LEFT JOIN restaurants r ON b.restaurant_id = r.id 
                     LEFT JOIN hotels h ON b.hotel_id = h.id 
                     WHERE b.id = ? AND b.user_id = ? AND b.status = 'pending'");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: customer/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $terms_accepted = isset($_POST['terms_accepted']);
    
    if (empty($payment_method)) {
        $error = 'Please select a payment method';
    } elseif (!$terms_accepted) {
        $error = 'Please accept the terms and conditions';
    } else {
        // Update booking status to confirmed
        $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        
        if ($stmt->execute([$booking_id])) {
            header('Location: customer/dashboard.php?success=booking_confirmed');
            exit();
        } else {
            $error = 'Payment processing failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - RestaurantBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="customer/dashboard.php">My Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="max-width: 800px; margin: 2rem auto;">
        <h1>Complete Your Payment</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
            <!-- Booking Summary -->
            <div class="card">
                <div class="card-content">
                    <h2>Booking Summary</h2>
                    
                    <div style="margin-bottom: 2rem;">
                        <?php if ($booking['restaurant_id']): ?>
                            <h3><?php echo htmlspecialchars($booking['restaurant_name']); ?></h3>
                            <p><?php echo htmlspecialchars($booking['restaurant_address']); ?></p>
                            <p><?php echo htmlspecialchars($booking['restaurant_city']); ?></p>
                        <?php else: ?>
                            <h3><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                            <p><?php echo htmlspecialchars($booking['hotel_address']); ?></p>
                            <p><?php echo htmlspecialchars($booking['hotel_city']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div style="border-top: 1px solid #eee; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Booking ID:</span>
                            <span><strong>#<?php echo $booking['id']; ?></strong></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Date:</span>
                            <span><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></span>
                        </div>
                        <?php if ($booking['restaurant_id']): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Time:</span>
                            <span>Evening Dining</span>
                        </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span><?php echo $booking['restaurant_id'] ? 'Guests' : 'Rooms'; ?>:</span>
                            <span><?php echo $booking['guests']; ?></span>
                        </div>

                    </div>

                    <div style="border-top: 2px solid #eee; padding-top: 1rem; margin-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                            <span>Total Amount:</span>
                            <span class="price">$<?php echo number_format($booking['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-content">
                    <h2>Payment Details</h2>
                    
                    <form method="POST" data-validate>
                        <div class="form-group">
                            <label class="form-label">Payment Method *</label>
                            <div style="margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer;">
                                    <input type="radio" name="payment_method" value="credit_card" required style="margin-right: 0.5rem;">
                                    <span>Credit/Debit Card</span>
                                </label>
                                <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer;">
                                    <input type="radio" name="payment_method" value="paypal" required style="margin-right: 0.5rem;">
                                    <span>PayPal</span>
                                </label>
                                <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer;">
                                    <input type="radio" name="payment_method" value="stripe" required style="margin-right: 0.5rem;">
                                    <span>Stripe</span>
                                </label>
                            </div>
                        </div>

                        <!-- Credit Card Fields (shown when credit card is selected) -->
                        <div id="credit-card-fields" style="display: none;">
                            <div class="form-group">
                                <label for="card_number" class="form-label">Card Number</label>
                                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="expiry" class="form-label">Expiry Date</label>
                                    <input type="text" id="expiry" name="expiry" class="form-control" placeholder="MM/YY">
                                </div>
                                <div class="form-group">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cardholder_name" class="form-label">Cardholder Name</label>
                                <input type="text" id="cardholder_name" name="cardholder_name" class="form-control" placeholder="John Doe">
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" name="terms_accepted" required style="margin-right: 0.5rem;">
                                <span>I agree to the <a href="terms.php" target="_blank" style="color: #ff5a5f;">Terms & Conditions</a> and <a href="privacy.php" target="_blank" style="color: #ff5a5f;">Privacy Policy</a></span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 1rem;">
                            Complete Payment - $<?php echo number_format($booking['total_price'], 2); ?>
                        </button>
                    </form>

                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <p style="font-size: 0.9rem; color: #666;">
                            🔒 Your payment information is secure and encrypted
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 RestaurantBook. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Show/hide credit card fields based on payment method selection
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const creditCardFields = document.getElementById('credit-card-fields');
            
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (this.value === 'credit_card') {
                        creditCardFields.style.display = 'block';
                        // Make credit card fields required
                        creditCardFields.querySelectorAll('input').forEach(input => {
                            input.setAttribute('required', 'required');
                        });
                    } else {
                        creditCardFields.style.display = 'none';
                        // Remove required attribute from credit card fields
                        creditCardFields.querySelectorAll('input').forEach(input => {
                            input.removeAttribute('required');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>