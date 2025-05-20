<?php
session_start();

// Only allow access in development environment
if ($_SERVER['SERVER_NAME'] != 'localhost') {
    die('This tool is only available in development environments.');
}

// Check if user is admin if logged in
if (isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    die('Access denied. Admin privileges required.');
}

// Include database connection
require_once 'db_config.php';

// Initialize messages array
$messages = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sample_data'])) {
    $data_type = $_POST['data_type'];
    
    if ($data_type === 'flights') {
        $messages[] = addSampleFlights($conn);
    } else if ($data_type === 'all') {
        $messages[] = addSampleFlights($conn);
        $messages[] = addSampleUsers($conn);
        $messages[] = addSampleBookings($conn);
    }
}

/**
 * Add sample flights for testing
 */
function addSampleFlights($conn) {
    // Common city pairs
    $city_pairs = [
        ['Manila', 'Cebu'],
        ['Manila', 'Singapore'],
        ['Manila', 'Hong Kong'],
        ['Manila', 'Tokyo'],
        ['Manila', 'Davao'],
        ['Cebu', 'Manila'],
        ['Cebu', 'Singapore'],
        ['Singapore', 'Manila'],
        ['Hong Kong', 'Manila'],
        ['Tokyo', 'Manila']
    ];
    
    // Airlines
    $airlines = ['Philippine Airlines', 'Cebu Pacific', 'AirAsia', 'Singapore Airlines', 'Cathay Pacific'];
    
    // Counter for added flights
    $flights_added = 0;
    
    // Generate flights for the next 30 days
    for ($day = 0; $day < 30; $day++) {
        $date = date('Y-m-d', strtotime("+$day days"));
        
        foreach ($city_pairs as $pair) {
            $departure_city = $pair[0];
            $arrival_city = $pair[1];
            
            // Skip if cities are the same
            if ($departure_city == $arrival_city) continue;
            
            // Generate 1-3 flights per route per day
            $flights_per_day = rand(1, 3);
            
            for ($f = 0; $f < $flights_per_day; $f++) {
                // Random airline
                $airline = $airlines[array_rand($airlines)];
                
                // Flight number format: 2 letters + 3-4 digits
                $airline_code = strtoupper(substr($airline, 0, 2));
                $flight_number = $airline_code . rand(100, 9999);
                
                // Departure times - morning, afternoon, evening
                $hours = [rand(6, 10), rand(11, 14), rand(15, 21)][$f % 3];
                $minutes = rand(0, 11) * 5; // 0, 5, 10, ... 55
                
                $departure_time = $date . " " . sprintf("%02d:%02d:00", $hours, $minutes);
                
                // Flight duration 1-5 hours
                $duration_hours = rand(1, 5);
                $duration_minutes = rand(0, 11) * 5;
                $arrival_time = date('Y-m-d H:i:s', strtotime("$departure_time + $duration_hours hours + $duration_minutes minutes"));
                
                // Price - base price range $80-300 with some randomness
                $base_price = rand(80, 300);
                $price = $base_price + (rand(-20, 20) * 0.1 * $base_price);
                $price = round($price, 2);
                
                // Seats - between 120 and 300
                $total_seats = rand(12, 30) * 10;
                
                // Status - mostly scheduled
                $statuses = ['scheduled', 'scheduled', 'scheduled', 'scheduled', 'scheduled', 'delayed'];
                $status = $statuses[array_rand($statuses)];
                
                // Insert the flight if it doesn't exist
                $check = $conn->prepare("SELECT COUNT(*) FROM flights WHERE flight_number = ? AND DATE(departure_time) = ?");
                $check->bind_param("ss", $flight_number, $date);
                $check->execute();
                $check->bind_result($count);
                $check->fetch();
                $check->close();
                
                if ($count == 0) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO flights (flight_number, airline, departure_city, arrival_city, 
                                               departure_time, arrival_time, price, total_seats, status) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        $stmt->bind_param("ssssssdis", $flight_number, $airline, $departure_city, $arrival_city, 
                                         $departure_time, $arrival_time, $price, $total_seats, $status);
                        
                        $stmt->execute();
                        $flights_added++;
                    } catch (Exception $e) {
                        return "Error adding flights: " . $e->getMessage();
                    }
                }
            }
        }
    }
    
    return "Successfully added $flights_added sample flights.";
}

/**
 * Add sample users for testing
 */
function addSampleUsers($conn) {
    // Sample users
    $users = [
        ['john.doe@example.com', 'John', 'Doe', 'password123', '09123456789'],
        ['jane.smith@example.com', 'Jane', 'Smith', 'password123', '09234567890'],
        ['bob.johnson@example.com', 'Bob', 'Johnson', 'password123', '09345678901'],
        ['alice.wong@example.com', 'Alice', 'Wong', 'password123', '09456789012'],
        ['mike.santos@example.com', 'Mike', 'Santos', 'password123', '09567890123']
    ];
    
    $users_added = 0;
    
    foreach ($users as $user) {
        // Check if user exists
        $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $check->bind_param("s", $user[0]);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();
        
        if ($count == 0) {
            try {
                $stmt = $conn->prepare("INSERT INTO users (email, first_name, last_name, password, phone, role, created_at) 
                                       VALUES (?, ?, ?, ?, ?, 'user', NOW())");
                
                $hashed_password = password_hash($user[3], PASSWORD_DEFAULT);
                $stmt->bind_param("sssss", $user[0], $user[1], $user[2], $hashed_password, $user[4]);
                
                $stmt->execute();
                $users_added++;
            } catch (Exception $e) {
                return "Error adding users: " . $e->getMessage();
            }
        }
    }
    
    return "Successfully added $users_added sample users.";
}

/**
 * Add sample bookings for testing
 */
function addSampleBookings($conn) {
    // Get users
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'user' LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row['user_id'];
    }
    
    if (empty($users)) {
        return "No users found. Please add sample users first.";
    }
    
    // Get flights
    $stmt = $conn->prepare("SELECT flight_id, price FROM flights WHERE departure_time > NOW() LIMIT 20");
    $stmt->execute();
    $result = $stmt->get_result();
    $flights = [];
    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }
    
    if (empty($flights)) {
        return "No flights found. Please add sample flights first.";
    }
    
    // Booking statuses
    $statuses = ['confirmed', 'confirmed', 'confirmed', 'pending', 'cancelled'];
    $payment_statuses = ['completed', 'completed', 'pending', 'pending', 'refunded'];
    
    $bookings_added = 0;
    
    // Create 10 random bookings
    for ($i = 0; $i < 10; $i++) {
        $user_id = $users[array_rand($users)];
        $flight = $flights[array_rand($flights)];
        $flight_id = $flight['flight_id'];
        $status = $statuses[array_rand($statuses)];
        $payment_status = $payment_statuses[array_rand($payment_statuses)];
        $passengers = rand(1, 4);
        $booking_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
        
        // Calculate total amount
        $total_amount = $flight['price'] * $passengers;
        
        try {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, booking_date, passengers, 
                                   total_amount, booking_status, payment_status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("iisidss", $user_id, $flight_id, $booking_date, $passengers, 
                             $total_amount, $status, $payment_status);
            
            $stmt->execute();
            $bookings_added++;
        } catch (Exception $e) {
            return "Error adding bookings: " . $e->getMessage();
        }
    }
    
    return "Successfully added $bookings_added sample bookings.";
}

// HTML page for the utility
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sample Data - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Add Sample Data for Testing</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This tool adds sample data to the database for testing purposes.
                            Use only in development environment.
                        </div>
                        
                        <?php foreach ($messages as $message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="data_type" class="form-label">Select Data Type</label>
                                <select class="form-select" id="data_type" name="data_type" required>
                                    <option value="flights">Sample Flights</option>
                                    <option value="all">All Sample Data (Flights, Users, Bookings)</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_sample_data" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Add Sample Data
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="mt-4">
                            <h5>Current Database Status:</h5>
                            <?php
                            // Count flights
                            $flight_count = $conn->query("SELECT COUNT(*) as count FROM flights")->fetch_assoc()['count'];
                            
                            // Count users
                            $user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
                            
                            // Count bookings
                            $booking_count = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
                            ?>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Flights
                                    <span class="badge bg-primary rounded-pill"><?php echo $flight_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Users
                                    <span class="badge bg-primary rounded-pill"><?php echo $user_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Bookings
                                    <span class="badge bg-primary rounded-pill"><?php echo $booking_count; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="../admin/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Admin
                            </a>
                            <a href="../index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
