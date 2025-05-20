<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Common function to get base URL
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}
$baseUrl = getBaseUrl();

// Initialize variables
$flights = [];
$error_message = '';
$search_performed = false;

// Process search if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['departure_city']) || isset($_GET['arrival_city']))) {
    $search_performed = true;
    
    // Get and sanitize search parameters
    $departure_city = isset($_GET['departure_city']) ? trim($conn->real_escape_string($_GET['departure_city'])) : '';
    $arrival_city = isset($_GET['arrival_city']) ? trim($conn->real_escape_string($_GET['arrival_city'])) : '';
    $departure_date = isset($_GET['departure_date']) ? trim($conn->real_escape_string($_GET['departure_date'])) : '';
    $passengers = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
    
    // Validate inputs
    $validation_errors = [];
    
    if (empty($departure_city)) {
        $validation_errors[] = "Departure city is required";
    }
    
    if (empty($arrival_city)) {
        $validation_errors[] = "Arrival city is required";
    }
    
    if ($departure_city == $arrival_city && !empty($departure_city)) {
        $validation_errors[] = "Departure and arrival cities cannot be the same";
    }
    
    if (empty($departure_date)) {
        $validation_errors[] = "Departure date is required";
    }
    
    // Only proceed if no validation errors
    if (empty($validation_errors)) {
        try {
            // Add debug query to check if flights exist at all
            $check_query = "SELECT COUNT(*) as count FROM flights";
            $check_result = $conn->query($check_query);
            $total_flights = $check_result->fetch_assoc()['count'];
            
            if ($total_flights == 0) {
                // No flights in database - provide specific message
                $error_message = "No flights are currently available in our system. Please try again later.";
                
                // Add sample flight data if in development environment
                if ($_SERVER['SERVER_NAME'] == 'localhost') {
                    // Add sample flights for testing - this helps developers
                    addSampleFlights($conn);
                    $error_message .= "<br><strong>Development notice:</strong> Sample flights have been added. Please try your search again.";
                }
            } else {
                // Build the search query with improved city matching and date handling
                $query = "SELECT f.* 
                          FROM flights f 
                          WHERE 
                          (LOWER(f.departure_city) = LOWER(?) OR 
                           f.departure_city LIKE CONCAT('%', ?, '%') OR
                           SOUNDEX(f.departure_city) = SOUNDEX(?))
                          AND 
                          (LOWER(f.arrival_city) = LOWER(?) OR 
                           f.arrival_city LIKE CONCAT('%', ?, '%') OR
                           SOUNDEX(f.arrival_city) = SOUNDEX(?))";
                
                // Adjust total_seats condition to use correct field and logic
                $query .= " AND (f.total_seats - COALESCE((SELECT SUM(b.passengers) FROM bookings b WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0)) >= ?";
                
                // Add date filtering - make it more flexible with date range
                if (!empty($departure_date)) {
                    // Allow for same day or day before/after to provide more results
                    $query .= " AND DATE(f.departure_time) BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)";
                }
                
                // Add status check - include status NULL as valid too for legacy data
                $query .= " AND (f.status = 'scheduled' OR f.status = 'active' OR f.status IS NULL)";
                
                // Order by departure time
                $query .= " ORDER BY f.departure_time ASC";
                
                // Debug actual query for development
                if (isset($_GET['debug']) && $_SERVER['SERVER_NAME'] == 'localhost') {
                    echo "<!-- DEBUG QUERY: " . htmlspecialchars($query) . " -->";
                }
                
                // Prepare and execute the query
                $stmt = $conn->prepare($query);
                
                if (!empty($departure_date)) {
                    // Fix: Change type string from "ssssssis" to "ssssssis" + "s" to match 9 parameters
                    $stmt->bind_param("ssssssis" . "s", 
                        $departure_city,
                        $departure_city,
                        $departure_city,
                        $arrival_city,
                        $arrival_city,
                        $arrival_city,
                        $passengers,
                        $departure_date,
                        $departure_date
                    );
                } else {
                    $stmt->bind_param("sssssi",
                        $departure_city,
                        $departure_city,
                        $departure_city,
                        $arrival_city,
                        $arrival_city,
                        $arrival_city,
                        $passengers
                    );
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $flights[] = $row;
                    }
                } else {
                    // Check for flights with exact cities but on other dates
                    $alt_query = "SELECT MIN(DATE(departure_time)) as next_date, 
                                  MAX(DATE(departure_time)) as last_date 
                                  FROM flights 
                                  WHERE LOWER(departure_city) = LOWER(?) 
                                  AND LOWER(arrival_city) = LOWER(?) 
                                  AND DATE(departure_time) > CURDATE()";
                    
                    $alt_stmt = $conn->prepare($alt_query);
                    $alt_stmt->bind_param("ss", $departure_city, $arrival_city);
                    $alt_stmt->execute();
                    $alt_result = $alt_stmt->get_result();
                    $alt_dates = $alt_result->fetch_assoc();
                    
                    if ($alt_dates && $alt_dates['next_date']) {
                        $error_message = "No flights found on selected date. We have flights on this route from " . 
                                        date('M d, Y', strtotime($alt_dates['next_date'])) . " to " . 
                                        date('M d, Y', strtotime($alt_dates['last_date'])) . ".";
                    } else {
                        // Check departures from this city to anywhere
                        $from_query = "SELECT COUNT(*) as count FROM flights WHERE LOWER(departure_city) = LOWER(?)";
                        $from_stmt = $conn->prepare($from_query);
                        $from_stmt->bind_param("s", $departure_city);
                        $from_stmt->execute();
                        $from_result = $from_stmt->get_result();
                        $from_count = $from_result->fetch_assoc()['count'];
                        
                        // Check arrivals to this city from anywhere
                        $to_query = "SELECT COUNT(*) as count FROM flights WHERE LOWER(arrival_city) = LOWER(?)";
                        $to_stmt = $conn->prepare($to_query);
                        $to_stmt->bind_param("s", $arrival_city);
                        $to_stmt->execute();
                        $to_result = $to_stmt->get_result();
                        $to_count = $to_result->fetch_assoc()['count'];
                        
                        if ($from_count == 0 && $to_count == 0) {
                            $error_message = "We don't currently offer flights to or from these cities. Please try other destinations.";
                        } else if ($from_count == 0) {
                            $error_message = "No flights departing from " . htmlspecialchars($departure_city) . ". Please try another departure city.";
                        } else if ($to_count == 0) {
                            $error_message = "No flights arriving at " . htmlspecialchars($arrival_city) . ". Please try another destination.";
                        } else {
                            $error_message = "No direct flights found between these cities. Please try different dates or destinations.";
                        }
                    }
                }
            }
            
            // Save recent search to session
            if (!isset($_SESSION['recent_searches'])) {
                $_SESSION['recent_searches'] = [];
            }
            
            // Add current search to recent searches
            $current_search = [
                'departure_city' => $departure_city,
                'arrival_city' => $arrival_city,
                'departure_date' => $departure_date,
                'passengers' => $passengers,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Add to the beginning of the array and keep only the last 5 searches
            array_unshift($_SESSION['recent_searches'], $current_search);
            $_SESSION['recent_searches'] = array_slice($_SESSION['recent_searches'], 0, 5);
            
        } catch (Exception $e) {
            $error_message = "An error occurred while searching: " . $e->getMessage();
            // Log the error for administrators
            error_log("Flight search error: " . $e->getMessage());
        }
    } else {
        $error_message = implode("<br>", $validation_errors);
    }
}

/**
 * Add sample flights for testing
 */
function addSampleFlights($conn) {
    // Only add sample data in development environment
    if ($_SERVER['SERVER_NAME'] != 'localhost') {
        return;
    }
    
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
    
    // Today's date
    $today = date('Y-m-d');
    
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
                    $stmt = $conn->prepare("INSERT INTO flights (flight_number, airline, departure_city, arrival_city, 
                                           departure_time, arrival_time, price, total_seats, status) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->bind_param("ssssssdis", $flight_number, $airline, $departure_city, $arrival_city, 
                                     $departure_time, $arrival_time, $price, $total_seats, $status);
                    
                    $stmt->execute();
                }
            }
        }
    }
}

// Define city image mapping with fallbacks
$cityImages = [
    'manila' => 'manila.jpg',
    'singapore' => 'singapore.jpg',
    'cebu' => 'cebu.jpg',
    'dubai' => 'dubai.jpg',
    'tokyo' => 'tokyo.jpg',
    'seoul' => 'seoul.jpg'
];

// Format arrival/departure times
function formatFlightTime($datetime) {
    return date('h:i A', strtotime($datetime));
}

// Calculate flight duration
function calculateDuration($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    $interval = $dep->diff($arr);
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;
    return sprintf('%dh %dm', $hours, $minutes);
}

// Get image for city (with fallback to default)
function getCityImage($city, $cityImages, $baseUrl) {
    $city = strtolower($city);
    
    if (isset($cityImages[$city])) {
        return $baseUrl . 'assets/images/destinations/' . $cityImages[$city];
    }
    
    return $baseUrl . 'assets/images/destinations/default.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Search Results - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Custom styles for search results */
        .flight-card {
            transition: transform 0.3s;
        }
        
        .flight-card:hover {
            transform: translateY(-5px);
        }

        .airline-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .flight-duration {
            position: relative;
            text-align: center;
        }
        
        .flight-duration:before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-bottom: 1px dashed #ccc;
            z-index: 0;
        }
        
        .flight-duration span {
            background-color: #fff;
            padding: 0 10px;
            position: relative;
            z-index: 1;
        }
        
        .flight-path {
            position: relative;
        }
        
        .flight-path i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container my-5">
        <!-- Search Results -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="search.php">Flight Search</a></li>
                        <li class="breadcrumb-item active">Search Results</li>
                    </ol>
                </nav>
                
                <?php if ($search_performed): ?>
                    <h2 class="mb-1">Flights from <?php echo htmlspecialchars($departure_city); ?> to <?php echo htmlspecialchars($arrival_city); ?></h2>
                    <p class="text-muted">
                        <?php echo date('M d, Y', strtotime($departure_date)); ?> · 
                        <?php echo $passengers; ?> <?php echo ($passengers > 1) ? 'Passengers' : 'Passenger'; ?>
                    </p>
                <?php else: ?>
                    <h2 class="mb-1">Flight Search Results</h2>
                    <p class="text-muted">Please use the search form to find flights</p>
                <?php endif; ?>
            </div>
        </div>
            
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
            
            <div class="text-center my-5 py-5">
                <i class="fas fa-plane-slash fa-5x text-muted mb-4"></i>
                <h4>No Flights Found</h4>
                <p class="text-muted mb-4">We couldn't find any flights matching your search criteria.</p>
                <div class="mt-4">
                    <h5>Suggestions:</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check-circle text-success me-2"></i> Try different dates</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Check if city names are spelled correctly</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Try nearby airports or cities</li>
                    </ul>
                </div>
                <a href="search.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search me-2"></i>New Search
                </a>
            </div>
        <?php elseif (empty($flights) && $search_performed): ?>
            <div class="text-center my-5 py-5">
                <i class="fas fa-plane-slash fa-5x text-muted mb-4"></i>
                <h4>No Flights Found</h4>
                <p class="text-muted mb-4">We couldn't find any flights matching your search criteria.</p>
                <a href="search.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search me-2"></i>New Search
                </a>
            </div>
        <?php elseif (!$search_performed): ?>
            <div class="text-center my-5 py-5">
                <i class="fas fa-search fa-5x text-muted mb-4"></i>
                <h4>Search for Flights</h4>
                <p class="text-muted mb-4">Use the search form to find available flights.</p>
                <a href="search.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search me-2"></i>Go to Search
                </a>
            </div>
        <?php else: ?>
            <!-- Flight Results -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Available Flights</h5>
                                <span class="badge bg-primary"><?php echo count($flights); ?> flights found</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Destination Image -->
            <div class="card mb-4">
                <img src="<?php echo getCityImage($arrival_city, $cityImages, $baseUrl); ?>" class="card-img-top destination-img"
                     alt="<?php echo htmlspecialchars($arrival_city); ?>"
                     onerror="this.src='<?php echo $baseUrl; ?>assets/images/destinations/default.jpg'; this.onerror=null;">
                <div class="card-body text-center py-3">
                    <h4 class="card-title mb-0">Discover <?php echo htmlspecialchars($arrival_city); ?></h4>
                </div>
            </div>
                
            <!-- Flight List -->
            <div class="row">
                <?php foreach ($flights as $flight): ?>
                    <div class="col-md-12 mb-4">
                        <div class="card shadow-sm flight-card">
                            <div class="card-body">
                                <div class="row">
                                    <!-- Airline Info -->
                                    <div class="col-md-2 mb-3 mb-md-0 text-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($flight['airline']); ?>&background=0D6EFD&color=fff&size=64&bold=true&format=svg" 
                                             alt="<?php echo htmlspecialchars($flight['airline']); ?>" 
                                             class="airline-logo mb-2">
                                        <div class="fw-bold"><?php echo htmlspecialchars($flight['airline']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                    </div>

                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-md-7 mb-3 mb-md-0">
                                                <!-- Flight Details -->
                                                <span class="d-inline-block me-3">
                                                    <i class="fas fa-plane"></i>
                                                </span>
                                                <div class="flight-path">
                                                    <i class="fas fa-arrow-right"></i>
                                                </div>
                                                <span class="d-inline-block ms-3">
                                                    <i class="fas fa-plane"></i>
                                                </span>
                                            </div>
                                            
                                            <div class="col-md-3 text-center text-md-end">
                                                <!-- Price and Booking -->
                                                <div class="h4 text-primary mb-2">$<?php echo number_format($flight['price'], 2); ?></div>
                                                <div class="text-muted small mb-3">per passenger</div>
                                                
                                                <a href="../booking/select_flight.php?flight_id=<?php echo $flight['flight_id']; ?>&passengers=<?php echo $passengers; ?>" 
                                                   class="btn btn-primary w-100">
                                                    <i class="fas fa-check-circle me-2"></i>Select Flight
                                                </a>
                                                <a href="../flights/flight_details.php?id=<?php echo $flight['flight_id']; ?>" 
                                                   class="btn btn-link text-decoration-none w-100 mt-2">
                                                    Flight Details
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-7">
                                                <!-- Departure Info -->
                                                <div class="text-muted small"><?php echo date('D, M j, Y', strtotime($flight['departure_time'])); ?></div>
                                                <div class="mb-2"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                                <div class="fw-bold"><?php echo formatFlightTime($flight['departure_time']); ?></div>
                                            </div>
                                            
                                            <div class="col-md-5 text-md-end">
                                                <!-- Arrival Info -->
                                                <div class="text-muted small"><?php echo date('D, M j, Y', strtotime($flight['arrival_time'])); ?></div>
                                                <div class="mb-2"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                                <div class="fw-bold"><?php echo formatFlightTime($flight['arrival_time']); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <!-- Flight Duration and Layover -->
                                                <div class="flight-duration">
                                                    <span><?php echo calculateDuration($flight['departure_time'], $flight['arrival_time']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <!-- Inclusions -->
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-wifi me-1"></i> Wi-Fi available
                                                </span>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-utensils me-1"></i> Meal included
                                                </span>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-suitcase me-1"></i> 20kg baggage
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
                
            <!-- Back to Search Button -->
            <div class="text-center mt-4 mb-5">
                <a href="search.php" class="btn btn-outline-primary">
                    <i class="fas fa-search me-2"></i>New Search
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>