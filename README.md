# RestaurantBook - Restaurant & Hotel Review Portal

A comprehensive restaurant and hotel booking platform with review system built using PHP, MySQL, HTML, CSS, and JavaScript.

## Quick Start Guide

### Prerequisites
- XAMPP/WAMP/LAMP server with PHP 7.4+ and MySQL 5.7+
- Web browser (Chrome, Firefox, Safari, Edge)

### Installation Steps

1. **Download & Extract**
   - Extract project files to your web server directory
   - For XAMPP: Place in `C:\xampp\htdocs\AirBNB Resturant`

2. **Database Setup**
   - Start Apache and MySQL in XAMPP Control Panel
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Import `database.sql` file to create database and sample data

3. **Access Website**
   - Open browser and go to: `http://localhost/AirBNB%20Resturant`
   - Website should load with sample restaurants and hotels

### Default Login Credentials
- **Admin**: admin@restaurant.com / password
- **Demo Users**: Create new accounts via registration

### Testing the System
1. Browse restaurants and hotels without login
2. Register new customer account
3. Login and make bookings
4. Write reviews for restaurants/hotels
5. Access admin panel with admin credentials

## Project Structure

```
/
├── assets/css/style.css     # Main stylesheet
├── assets/images/           # Restaurant/hotel images
├── customer/dashboard.php   # Customer booking management
├── prototype/               # Static HTML demo pages
├── index.php               # Homepage with search
├── search.php              # Restaurant search & filters
├── hotel-search.php        # Hotel search & filters
├── restaurant.php          # Restaurant details & booking
├── hotel.php               # Hotel details & booking
├── booking.php             # Booking form (restaurants/hotels)
├── payment.php             # Payment processing
├── review.php              # Review submission form
├── login.php               # User authentication
├── register.php            # User registration
└── database.sql            # Database with sample data
```

## Features

### Customer Features
- Browse restaurants and hotels with filters
- View detailed information, menus, and reviews
- Make bookings for restaurants and hotels
- Write and manage reviews with ratings
- Customer dashboard for booking management
- Like/dislike reviews and interactions

### Admin Features
- Admin dashboard with system overview
- User management and moderation
- Restaurant and hotel approval system
- Review moderation and management
- Booking oversight and statistics

### Technical Features
- **Responsive Design**: Works on desktop and mobile
- **Security**: Password hashing, session management, SQL injection prevention
- **Database**: MySQL with sample data included
- **Review System**: Multi-category ratings (food, service, value for restaurants; cleanliness, facilities, location for hotels)
- **Booking System**: Real-time booking for both restaurants and hotels
- **Payment Demo**: Simulated payment processing

## Troubleshooting

- **Database Connection Error**: Check MySQL is running and database is imported
- **Page Not Found**: Ensure files are in correct web server directory
- **Images Not Loading**: Check `assets/images/` directory exists with sample images
- **Login Issues**: Use default admin credentials or register new account

## Demo Data Included

- 6 sample restaurants with different cuisines
- 2 sample hotels with ratings
- Sample reviews and ratings
- Menu items for restaurants
- Admin user account for testing

Developed by: Junaid Jabbar Faizi, Sami Ullah Khan, Usman Ehsan