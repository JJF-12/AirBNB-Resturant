-- Restaurant Review Portal - Simplified Database
CREATE DATABASE IF NOT EXISTS restaurant_review_portal;
USE restaurant_review_portal;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    user_type ENUM('customer', 'host', 'admin') DEFAULT 'customer',
    status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Restaurants table
CREATE TABLE restaurants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    host_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    cuisine_type VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    price_per_person DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Hotels table
CREATE TABLE hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    host_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    star_rating ENUM('1','2','3','4','5') NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    total_rooms INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Menu items table
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    restaurant_id INT DEFAULT NULL,
    hotel_id INT DEFAULT NULL,
    booking_date DATE NOT NULL,
    guests INT DEFAULT 1,
    total_price DECIMAL(10,2),
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE SET NULL
);

-- Restaurant images table
CREATE TABLE restaurant_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    restaurant_id INT DEFAULT NULL,
    hotel_id INT DEFAULT NULL,
    review_type ENUM('restaurant','hotel') NOT NULL,
    food_rating INT DEFAULT NULL,
    service_rating INT DEFAULT NULL,
    value_rating INT DEFAULT NULL,
    cleanliness_rating INT DEFAULT NULL,
    facilities_rating INT DEFAULT NULL,
    location_rating INT DEFAULT NULL,
    overall_rating DECIMAL(2,1),
    review_text TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    reviewer_name VARCHAR(100),
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Insert admin user
INSERT INTO users (email, password, first_name, last_name, user_type, status) 
VALUES ('admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active');

-- Sample restaurants
INSERT INTO restaurants (host_id, name, description, cuisine_type, address, city, country, capacity, price_per_person, amenities) VALUES
(1, 'Bella Vista Italian', 'Authentic Italian cuisine with fresh ingredients', 'Italian', '123 Main St', 'New York', 'USA', 50, 45.00, 'Outdoor Seating,Wine Bar'),
(1, 'Dragon Palace', 'Traditional Chinese dishes with modern presentation', 'Chinese', '456 Oak Ave', 'New York', 'USA', 80, 32.00, 'Takeout,Delivery'),
(1, 'Taco Fiesta', 'Vibrant Mexican flavors with fresh ingredients', 'Mexican', '789 Beach Blvd', 'New York', 'USA', 60, 28.00, 'Live Music,Bar'),
(1, 'Spice Garden', 'Aromatic Indian cuisine with authentic spices', 'Indian', '321 Broadway', 'New York', 'USA', 70, 38.00, 'Vegetarian Options'),
(1, 'Sakura Sushi', 'Fresh sushi and traditional Japanese dishes', 'Japanese', '654 Park Ave', 'New York', 'USA', 40, 55.00, 'Sushi Bar'),
(1, 'Le Petit Bistro', 'Elegant French dining with wine pairings', 'French', '987 Fifth Ave', 'New York', 'USA', 35, 75.00, 'Wine Cellar');

-- Sample hotels
INSERT INTO hotels (host_id, name, description, star_rating, address, city, country, total_rooms, price_per_night, amenities) VALUES
(1, 'Grand Hotel Plaza', 'Luxury hotel in downtown Manhattan', '5', '100 Central Park West', 'New York', 'USA', 200, 299.00, 'WiFi,Pool,Gym,Spa,Restaurant'),
(1, 'Manhattan Boutique Hotel', 'Stylish boutique hotel in Midtown', '4', '500 Times Square', 'New York', 'USA', 150, 189.00, 'WiFi,Gym,Restaurant,Bar');

-- Sample menu items
INSERT INTO menu_items (restaurant_id, name, description, price, category) VALUES
(1, 'Spaghetti Carbonara', 'Traditional Roman pasta with eggs, cheese, and pancetta', 24.00, 'Main Courses'),
(1, 'Margherita Pizza', 'Fresh tomatoes, mozzarella, and basil', 18.00, 'Pizza'),
(2, 'Peking Duck', 'Crispy duck with pancakes and hoisin sauce', 35.00, 'Main Courses'),
(3, 'Fish Tacos', 'Grilled fish with cabbage slaw and lime crema', 16.00, 'Main Courses');

-- Sample restaurant images
INSERT INTO restaurant_images (restaurant_id, image_path, is_primary) VALUES
(1, 'restaurant-1.jpg', TRUE),
(2, 'restaurant-2.jpg', TRUE),
(3, 'restaurant-3.jpg', TRUE),
(4, 'restaurant-4.jpg', TRUE),
(5, 'restaurant-5.jpg', TRUE),
(6, 'restaurant-6.jpg', TRUE);

-- Sample reviews
INSERT INTO reviews (restaurant_id, review_type, food_rating, service_rating, value_rating, overall_rating, review_text, is_anonymous, reviewer_name, status, customer_id) VALUES
(1, 'restaurant', 5, 5, 4, 4.7, 'Excellent Italian food! The carbonara was perfectly creamy.', TRUE, 'John D.', 'approved', 1),
(2, 'restaurant', 4, 4, 5, 4.3, 'Great Chinese food with authentic flavors.', TRUE, 'Sarah M.', 'approved', 1),
(3, 'restaurant', 5, 4, 5, 4.7, 'Amazing Mexican cuisine! Fresh and flavorful.', TRUE, 'Mike R.', 'approved', 1);

-- Indexes
CREATE INDEX idx_restaurants_city ON restaurants(city);
CREATE INDEX idx_restaurants_cuisine ON restaurants(cuisine_type);
CREATE INDEX idx_restaurants_status ON restaurants(status);
CREATE INDEX idx_hotels_city ON hotels(city);
CREATE INDEX idx_hotels_star_rating ON hotels(star_rating);
CREATE INDEX idx_hotels_status ON hotels(status);
CREATE INDEX idx_reviews_status ON reviews(status);
CREATE INDEX idx_bookings_date ON bookings(booking_date);
