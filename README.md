# Airline Reservation System

![Airline Reservation System](assets/images/logo.png)

## Overview

A comprehensive web-based airline reservation system built with PHP and MySQL. This system allows users to search for flights, make bookings, manage their reservations, and perform check-ins. The admin panel provides tools for managing flights, bookings, users, and generating reports.

## Features

### User Features
- **User Registration & Authentication**: Secure login and registration system
- **Flight Search**: Search flights by origin, destination, date, and passenger count
- **Flight Booking**: Book flights with passenger details and seat selection
- **Booking Management**: View, modify or cancel existing bookings
- **Online Check-in**: Check in for flights 48 hours before departure
- **Payment Processing**: Secure payment integration
- **User Dashboard**: Personalized dashboard with upcoming flight information
- **Email Notifications**: Automatic notifications for booking confirmations, check-ins, etc.
- **FAQ Section**: Comprehensive knowledge base with searchable questions and answers
- **Flight Status Tracking**: Real-time updates on flight status (boarding, departed, arrived)
- **Boarding Pass**: Digital boarding pass generation after successful check-in
- **Multiple Payment Options**: Support for various payment methods

### Admin Features
- **Flight Management**: Add, edit, delete, and update flight schedules
- **Booking Management**: View and manage all bookings in the system
- **User Management**: Manage user accounts and permissions
- **Pricing Control**: Set and adjust flight prices, including base fare and taxes
- **Dynamic Currency Support**: Configure system-wide currency symbols and formatting
- **Flight Statistics**: View occupancy rates, cancellation statistics, and performance metrics
- **Advanced Reporting**: Generate sales reports, occupancy reports, and other analytics
- **System Configuration**: Configure system-wide settings
- **Passenger Management**: Track passenger information and check-in status
- **Admin Dashboard**: Visual overview of system performance and key metrics

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- XAMPP/WAMP/LAMP stack (recommended)
- Composer (for dependency management)

### Steps
1. Clone the repository to your local machine:
   ```
   git clone https://github.com/yourusername/airlinereservationsystem.git
   ```

2. Move the project to your web server directory:
   ```
   mv airlinereservationsystem /path/to/your/www/directory
   ```

3. Import the database schema:
   ```
   mysql -u username -p your_database < db/schema.sql
   ```

4. Copy the configuration file:
   ```
   cp db/db_config.sample.php db/db_config.php
   ```

5. Update database credentials in `db/db_config.php`:
   ```php
   // Database credentials
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "your_database";
   ```

6. Set appropriate permissions:
   ```
   chmod 755 -R /path/to/your/project
   chmod 777 -R /path/to/your/project/uploads
   ```

7. Access the application through your web browser:
   ```
   http://localhost/airlinereservationsystem
   ```

## Database Setup

The system uses a MySQL database with the following main tables:
- `users` - User accounts and authentication
- `flights` - Flight schedules and details
- `bookings` - User bookings
- `passengers` - Passenger information
- `payments` - Payment records
- `tickets` - Generated tickets for flights
- `notifications` - User notifications
- `refunds` - Payment refund records

The complete database schema can be found in `db/schema.sql`.

## Directory Structure

### Key Directories
- `/admin` - Admin panel and management interfaces
- `/auth` - Authentication related pages (login, register, forgot password)
- `/booking` - Booking process and management
- `/flights` - Flight search and display
- `/user` - User dashboard and profile management
- `/includes` - Reusable components and helper functions
- `/assets` - CSS, JavaScript, images and other static resources
- `/db` - Database configuration and initialization scripts
- `/pages` - Informational pages including FAQ, About, Contact, etc.

## Recent Updates
- Added comprehensive FAQ system with searchable Q&A
- Implemented multi-currency support throughout the system
- Enhanced admin dashboard with improved statistics and reporting
- Added flight status tracking (boarding, departed, arrived states)
- Improved mobile responsiveness across all pages

