<?php
session_start();
require_once 'config/database.php';

$restaurant_id = $_GET['restaurant_id'] ?? null;
$hotel_id = $_GET['hotel_id'] ?? null;
$review_type = $restaurant_id ? 'restaurant' : 'hotel';
$entity_id = $restaurant_id ?: $hotel_id;

$entity = null;
if ($entity_id) {
    if ($review_type === 'restaurant') {
        $stmt = $pdo->prepare("SELECT name FROM restaurants WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT name FROM hotels WHERE id = ?");
    }
    $stmt->execute([$entity_id]);
    $entity = $stmt->fetch();
    if (!$entity) header('Location: index.php');
}

$message = $error = '';

if ($_POST) {
    $review_text = trim($_POST['review_text'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']);
    $reviewer_name = trim($_POST['reviewer_name'] ?? '');
    
    if ($review_type === 'restaurant') {
        $food_rating = $_POST['food_rating'] ?? 0;
        $service_rating = $_POST['service_rating'] ?? 0;
        $value_rating = $_POST['value_rating'] ?? 0;
        
        if ($food_rating < 1 || $service_rating < 1 || $value_rating < 1) {
            $error = 'Please provide all ratings.';
        } else {
            $overall_rating = ($food_rating + $service_rating + $value_rating) / 3;
            $customer_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO reviews (restaurant_id, customer_id, review_type, food_rating, service_rating, value_rating, overall_rating, review_text, is_anonymous, reviewer_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$restaurant_id, $customer_id, 'restaurant', $food_rating, $service_rating, $value_rating, $overall_rating, $review_text, $is_anonymous, $reviewer_name])) {
                $message = 'Review submitted successfully!';
            } else {
                $error = 'Failed to submit review.';
            }
        }
    } else {
        $cleanliness_rating = $_POST['cleanliness_rating'] ?? 0;
        $service_rating = $_POST['service_rating'] ?? 0;
        $facilities_rating = $_POST['facilities_rating'] ?? 0;
        $location_rating = $_POST['location_rating'] ?? 0;
        $value_rating = $_POST['value_rating'] ?? 0;
        
        if ($cleanliness_rating < 1 || $service_rating < 1 || $facilities_rating < 1 || $location_rating < 1 || $value_rating < 1) {
            $error = 'Please provide all ratings.';
        } else {
            $overall_rating = ($cleanliness_rating + $service_rating + $facilities_rating + $location_rating + $value_rating) / 5;
            $customer_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO reviews (hotel_id, customer_id, review_type, service_rating, value_rating, cleanliness_rating, facilities_rating, location_rating, overall_rating, review_text, is_anonymous, reviewer_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$hotel_id, $customer_id, 'hotel', $service_rating, $value_rating, $cleanliness_rating, $facilities_rating, $location_rating, $overall_rating, $review_text, $is_anonymous, $reviewer_name])) {
                $message = 'Review submitted successfully!';
            } else {
                $error = 'Failed to submit review.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Review <?= htmlspecialchars($restaurant['name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .star-rating { font-size: 1.5rem; margin: 0.5rem 0; }
        .star { color: #ddd; cursor: pointer; transition: color 0.2s; }
        .star:hover { color: #ffc107; }
        .review-container { max-width: 800px; margin: 2rem auto; }
        .review-form { background: white; padding: 2rem; border: 1px solid #ddd; }
        .message { padding: 1rem; margin: 1rem 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .imdb-style-header { background: #f5f5f0; padding: 1.5rem; margin-bottom: 1.5rem; }
        .imdb-style-header h3 { margin: 0 0 0.5rem 0; color: #333; }
        .imdb-style-header p { margin: 0; color: #666; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">RestaurantBook</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="search.php">Restaurants</a></li>
                <li><a href="hotel-search.php">Hotels</a></li>
                <li><a href="review.php">Write Review</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="logout.php" class="btn-logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container review-container">
        <?php if (!$entity_id): ?>
        <h1>Write a Review</h1>
        <div class="review-form">
            <div class="form-group">
                <label for="review_type">Review Type</label>
                <select id="review_type" class="form-control" onchange="loadCities()">
                    <option value="">Choose what to review...</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="hotel">Hotel</option>
                </select>
            </div>
            
            <div class="form-group" id="city_group" style="display: none;">
                <label for="city_select">Select City</label>
                <select id="city_select" class="form-control" onchange="loadPlaces()">
                    <option value="">Choose a city...</option>
                </select>
            </div>
            
            <div class="form-group" id="place_group" style="display: none;">
                <label for="place_select" id="place_label">Select Place</label>
                <select id="place_select" class="form-control" onchange="showReviewForm()">
                    <option value="">Choose a place...</option>
                </select>
            </div>
        </div>
        
        <div id="review_form_container" style="display: none;">
            <form method="POST" class="review-form">
                <input type="hidden" id="selected_restaurant_id" name="restaurant_id">
                <input type="hidden" id="selected_hotel_id" name="hotel_id">
                
                <div class="imdb-style-header" style="background: #f5f5f0; padding: 1rem; margin-bottom: 1rem;">
                    <h3 id="place_name_display"></h3>
                    <p id="place_details"></p>
                </div>
                
                <div id="ratings_container"></div>
                
                <div class="form-group">
                    <label for="review_text">Your Review</label>
                    <textarea name="review_text" id="review_text" class="form-control" rows="5" required placeholder="Share your experience..."></textarea>
                </div>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="form-group" style="text-align: center; padding: 2rem; background: #f8f9fa;">
                    <p><strong>Please log in to write a review</strong></p>
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Sign Up</a>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label><input type="checkbox" name="is_anonymous" id="is_anonymous" onchange="toggleAnonymous()"> Submit anonymously</label>
                </div>
                <div class="form-group" id="anonymous-name" style="display:none;">
                    <label for="reviewer_name">Display Name (for anonymous review)</label>
                    <input type="text" name="reviewer_name" id="reviewer_name" class="form-control" placeholder="e.g., John D.">
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Review</button>
                <?php endif; ?>
                
            </form>
        </div>
        <?php else: ?>
        <h1>Review <?= htmlspecialchars($entity['name']) ?></h1>
        
        <?php if ($message): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="review-form" id="review_form">
            <input type="hidden" id="selected_restaurant_id" name="restaurant_id">
            <input type="hidden" id="selected_hotel_id" name="hotel_id">
            
            <div class="imdb-style-header" style="background: #f5f5f0; padding: 1rem; margin-bottom: 1rem;">
                <h3 id="place_name_display"></h3>
                <p id="place_details"></p>
            </div>
            <?php if ($review_type === 'restaurant'): ?>
            <div class="form-group">
                <label>Food Quality</label>
                <div class="star-rating" data-rating="food_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="food_rating" id="food_rating" required>
            </div>

            <div class="form-group">
                <label>Service Quality</label>
                <div class="star-rating" data-rating="service_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="service_rating" id="service_rating" required>
            </div>

            <div class="form-group">
                <label>Value for Money</label>
                <div class="star-rating" data-rating="value_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="value_rating" id="value_rating" required>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label>Cleanliness</label>
                <div class="star-rating" data-rating="cleanliness_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="cleanliness_rating" id="cleanliness_rating" required>
            </div>

            <div class="form-group">
                <label>Service</label>
                <div class="star-rating" data-rating="service_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="service_rating" id="service_rating" required>
            </div>

            <div class="form-group">
                <label>Facilities</label>
                <div class="star-rating" data-rating="facilities_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="facilities_rating" id="facilities_rating" required>
            </div>

            <div class="form-group">
                <label>Location</label>
                <div class="star-rating" data-rating="location_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="location_rating" id="location_rating" required>
            </div>

            <div class="form-group">
                <label>Value for Money</label>
                <div class="star-rating" data-rating="value_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="value_rating" id="value_rating" required>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="review_text">Your Review</label>
                <textarea name="review_text" id="review_text" class="form-control" rows="5" required></textarea>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="form-group">
                <label><input type="checkbox" name="is_anonymous" id="is_anonymous"> Submit anonymously</label>
            </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="form-group">
                <label for="reviewer_name">Your Name</label>
                <input type="text" name="reviewer_name" id="reviewer_name" class="form-control" required>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label><input type="checkbox" name="is_anonymous" id="is_anonymous" onchange="toggleAnonymous()"> Submit anonymously</label>
            </div>
            <div class="form-group" id="anonymous-name" style="display:none;">
                <label for="reviewer_name">Your Name (for anonymous review)</label>
                <input type="text" name="reviewer_name" id="reviewer_name" class="form-control">
            </div>
            <?php endif; ?>
            
            <script>
            function toggleAnonymous() {
                const checkbox = document.getElementById('is_anonymous');
                const nameField = document.getElementById('anonymous-name');
                nameField.style.display = checkbox.checked ? 'block' : 'none';
            }
            </script>

            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
        </div>
        <?php endif; ?>
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
        // Initialize existing star ratings on page load
        initializeStarRatings();

        <?php if (isset($_SESSION['user_id'])): ?>
        document.getElementById('is_anonymous').onchange = function() {
            const nameField = document.getElementById('anonymous-name');
            const nameInput = document.getElementById('reviewer_name');
            if (this.checked) {
                nameField.style.display = 'block';
                nameInput.required = true;
            } else {
                nameField.style.display = 'none';
                nameInput.required = false;
            }
        };
        <?php endif; ?>
        
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            const burger = document.querySelector('.burger');
            
            navLinks.classList.toggle('active');
            burger.classList.toggle('active');
        }
        
        function loadCities() {
            const reviewType = document.getElementById('review_type').value;
            const cityGroup = document.getElementById('city_group');
            const citySelect = document.getElementById('city_select');
            const placeGroup = document.getElementById('place_group');
            
            if (!reviewType) {
                cityGroup.style.display = 'none';
                placeGroup.style.display = 'none';
                return;
            }
            
            const table = reviewType === 'restaurant' ? 'restaurants' : 'hotels';
            fetch(`get_cities.php?type=${reviewType}`)
                .then(response => response.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Choose a city...</option>';
                    data.forEach(city => {
                        citySelect.innerHTML += `<option value="${city.city}">${city.city}</option>`;
                    });
                    cityGroup.style.display = 'block';
                    document.getElementById('place_label').textContent = `Select ${reviewType === 'restaurant' ? 'Restaurant' : 'Hotel'}`;
                });
        }
        
        function loadPlaces() {
            const reviewType = document.getElementById('review_type').value;
            const city = document.getElementById('city_select').value;
            const placeGroup = document.getElementById('place_group');
            const placeSelect = document.getElementById('place_select');
            
            if (!city || !reviewType) {
                placeGroup.style.display = 'none';
                return;
            }
            
            fetch(`get_places.php?type=${reviewType}&city=${encodeURIComponent(city)}`)
                .then(response => response.json())
                .then(data => {
                    placeSelect.innerHTML = `<option value="">Choose a ${reviewType}...</option>`;
                    data.forEach(place => {
                        const details = reviewType === 'restaurant' ? place.cuisine_type : `${place.star_rating} Star`;
                        placeSelect.innerHTML += `<option value="${place.id}" data-name="${place.name}" data-details="${details}" data-city="${place.city}" data-type="${reviewType}">${place.name} - ${details}</option>`;
                    });
                    placeGroup.style.display = 'block';
                });
        }
        
        function showReviewForm() {
            const select = document.getElementById('place_select');
            const option = select.options[select.selectedIndex];
            
            if (!option.value) {
                document.getElementById('review_form_container').style.display = 'none';
                return;
            }
            
            const reviewType = option.dataset.type;
            if (reviewType === 'restaurant') {
                document.getElementById('selected_restaurant_id').value = option.value;
                document.getElementById('selected_hotel_id').value = '';
            } else {
                document.getElementById('selected_hotel_id').value = option.value;
                document.getElementById('selected_restaurant_id').value = '';
            }
            
            document.getElementById('place_name_display').textContent = option.dataset.name;
            document.getElementById('place_details').textContent = `${option.dataset.details} • ${option.dataset.city}`;
            
            // Generate rating fields based on type
            const ratingsContainer = document.getElementById('ratings_container');
            let ratingsHTML = '';
            
            if (reviewType === 'restaurant') {
                const ratings = ['food_rating', 'service_rating', 'value_rating'];
                const labels = ['Food Quality', 'Service Quality', 'Value for Money'];
                
                ratings.forEach((rating, index) => {
                    ratingsHTML += `
                        <div class="form-group">
                            <label>${labels[index]}</label>
                            <div class="star-rating" data-rating="${rating}">
                                <span class="star" data-value="1">★</span>
                                <span class="star" data-value="2">★</span>
                                <span class="star" data-value="3">★</span>
                                <span class="star" data-value="4">★</span>
                                <span class="star" data-value="5">★</span>
                            </div>
                            <input type="hidden" name="${rating}" id="${rating}" required>
                        </div>`;
                });
            } else {
                const ratings = ['cleanliness_rating', 'service_rating', 'facilities_rating', 'location_rating', 'value_rating'];
                const labels = ['Cleanliness', 'Service', 'Facilities', 'Location', 'Value for Money'];
                
                ratings.forEach((rating, index) => {
                    ratingsHTML += `
                        <div class="form-group">
                            <label>${labels[index]}</label>
                            <div class="star-rating" data-rating="${rating}">
                                <span class="star" data-value="1">★</span>
                                <span class="star" data-value="2">★</span>
                                <span class="star" data-value="3">★</span>
                                <span class="star" data-value="4">★</span>
                                <span class="star" data-value="5">★</span>
                            </div>
                            <input type="hidden" name="${rating}" id="${rating}" required>
                        </div>`;
                });
            }
            
            ratingsContainer.innerHTML = ratingsHTML;
            initializeStarRatings();
            document.getElementById('review_form_container').style.display = 'block';
        }
        
        function initializeStarRatings() {
            document.querySelectorAll('.star-rating').forEach(rating => {
                const stars = rating.querySelectorAll('.star');
                const input = document.getElementById(rating.dataset.rating);
                
                stars.forEach((star, index) => {
                    star.onclick = () => {
                        input.value = index + 1;
                        stars.forEach((s, i) => s.style.color = i < input.value ? '#ffc107' : '#ddd');
                    };
                });
            });
        }
    </script>
</body>
</html>