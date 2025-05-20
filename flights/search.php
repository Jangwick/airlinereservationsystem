<?php
session_start();
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
$search_performed = false;
$error_message = '';
$city_list = [];

// Get available cities for autocomplete
try {
    // Get departure cities
    $departures = $conn->query("SELECT DISTINCT departure_city FROM flights ORDER BY departure_city");
    // Get arrival cities
    $arrivals = $conn->query("SELECT DISTINCT arrival_city FROM flights ORDER BY arrival_city");
    
    if ($departures && $arrivals) {
        while ($row = $departures->fetch_assoc()) {
            if (!in_array($row['departure_city'], $city_list)) {
                $city_list[] = $row['departure_city'];
            }
        }
        
        while ($row = $arrivals->fetch_assoc()) {
            if (!in_array($row['arrival_city'], $city_list)) {
                $city_list[] = $row['arrival_city'];
            }
        }
        
        // Sort cities alphabetically
        sort($city_list);
    }
} catch (Exception $e) {
    $error_message = "Error loading city list: " . $e->getMessage();
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

// Process search if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['departure_city']) || isset($_GET['arrival_city']))) {
    $search_performed = true;
    
    // Get and sanitize search parameters
    $departure_city = isset($_GET['departure_city']) ? trim($conn->real_escape_string($_GET['departure_city'])) : '';
    $arrival_city = isset($_GET['arrival_city']) ? trim($conn->real_escape_string($_GET['arrival_city'])) : '';
    $departure_date = isset($_GET['departure_date']) ? trim($conn->real_escape_string($_GET['departure_date'])) : '';
    $return_date = isset($_GET['return_date']) ? trim($conn->real_escape_string($_GET['return_date'])) : '';
    $passengers = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
    $trip_type = isset($_GET['trip_type']) ? trim($conn->real_escape_string($_GET['trip_type'])) : 'one-way';
    
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
    
    if ($trip_type == 'round-trip' && empty($return_date)) {
        $validation_errors[] = "Return date is required for round-trip";
    }
    
    // Only proceed if no validation errors
    if (empty($validation_errors)) {
        try {
            // Build the search query WITHOUT joining the airlines table
            $query = "SELECT f.* 
                      FROM flights f 
                      WHERE (LOWER(f.departure_city) = LOWER(?) OR SOUNDEX(f.departure_city) = SOUNDEX(?))
                      AND (LOWER(f.arrival_city) = LOWER(?) OR SOUNDEX(f.arrival_city) = SOUNDEX(?))
                      AND f.seats_available >= ?";
            
            // Add date filtering
            if (!empty($departure_date)) {
                // Match the date part only, not the time
                $query .= " AND DATE(f.departure_time) = ?";
            }
            
            // Add status check (only show active flights)
            $query .= " AND f.status = 'active'";
            
            // Order by departure time
            $query .= " ORDER BY f.departure_time ASC";
            
            // Prepare and execute the query
            $stmt = $conn->prepare($query);
            
            if (!empty($departure_date)) {
                $stmt->bind_param("ssssss", $departure_city, $departure_city, $arrival_city, $arrival_city, $passengers, $departure_date);
            } else {
                $stmt->bind_param("ssssi", $departure_city, $departure_city, $arrival_city, $arrival_city, $passengers);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $flights[] = $row;
                }
            } else {
                $error_message = "No flights found matching your search criteria. Try different dates or destinations.";
            }
            
            // If round-trip, also search for return flights WITHOUT joining airlines table
            if ($trip_type == 'round-trip' && !empty($return_date)) {
                $return_flights = [];
                
                $return_query = "SELECT f.* 
                                FROM flights f 
                                WHERE (LOWER(f.departure_city) = LOWER(?) OR SOUNDEX(f.departure_city) = SOUNDEX(?))
                                AND (LOWER(f.arrival_city) = LOWER(?) OR SOUNDEX(f.arrival_city) = SOUNDEX(?))
                                AND f.seats_available >= ?
                                AND DATE(f.departure_time) = ?
                                AND f.status = 'active'
                                ORDER BY f.departure_time ASC";
                
                $return_stmt = $conn->prepare($return_query);
                $return_stmt->bind_param("ssssss", $arrival_city, $arrival_city, $departure_city, $departure_city, $passengers, $return_date);
                $return_stmt->execute();
                $return_result = $return_stmt->get_result();
                
                if ($return_result->num_rows > 0) {
                    while ($row = $return_result->fetch_assoc()) {
                        $return_flights[] = $row;
                    }
                }
                
                // Store return flights in session for later use
                $_SESSION['return_flights'] = $return_flights;
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
        }
    } else {
        $error_message = implode("<br>", $validation_errors);
    }
}

// Fetch popular destinations for homepage
$popular_destinations = [];
try {
    $popular_query = "SELECT destination, COUNT(*) as flight_count 
                      FROM (
                          SELECT arrival_city as destination FROM flights 
                          GROUP BY arrival_city
                      ) as destinations 
                      GROUP BY destination 
                      ORDER BY flight_count DESC
                      LIMIT 6";
    
    $popular_result = $conn->query($popular_query);
    
    if ($popular_result && $popular_result->num_rows > 0) {
        while ($row = $popular_result->fetch_assoc()) {
            $popular_destinations[] = $row['destination'];
        }
    }
} catch (Exception $e) {
    // Silently fail for popular destinations
}

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
    <title>Search Flights - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery UI CSS for autocomplete -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
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
        
        .destination-img {
            height: 200px;
            object-fit: cover;
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

    <!-- Hero Section with Search Form -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto text-center mb-4">
                    <h1 class="display-5 fw-bold mb-3">Find Your Perfect Flight</h1>
                    <p class="lead mb-0">Search, compare, and book flights to destinations around the world</p>
                </div>
            </div>
            
            <!-- Search Form -->
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <form action="search_results.php" method="get" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="departure_city" class="form-label">From</label>
                                <select class="form-select" id="departure_city" name="departure_city" required>
                                    <option value="">Select departure city</option>
                                    <?php foreach ($city_list as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="arrival_city" class="form-label">To</label>
                                <select class="form-select" id="arrival_city" name="arrival_city" required>
                                    <option value="">Select arrival city</option>
                                    <?php foreach ($city_list as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="departure_date" class="form-label">Departure Date</label>
                                <input type="date" class="form-control" id="departure_date" name="departure_date" required 
                                       min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="passengers" class="form-label">Passengers</label>
                                <input type="number" class="form-control" id="passengers" name="passengers" min="1" max="9" value="1" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search Flights
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if ($search_performed): ?>
        
            <!-- Search Results -->
            <div class="row mb-4">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>">Home</a></li>
                            <li class="breadcrumb-item active">Flight Search Results</li>
                        </ol>
                    </nav>
                    
                    <h2 class="mb-1">Flights from <?php echo htmlspecialchars($departure_city); ?> to <?php echo htmlspecialchars($arrival_city); ?></h2>
                    <p class="text-muted">
                        <?php echo date('M d, Y', strtotime($departure_date)); ?> · <?php echo isset($_GET['passengers']) ? htmlspecialchars($_GET['passengers']) : '1'; ?> 
                        <?php echo (isset($_GET['passengers']) && $_GET['passengers'] > 1) ? 'Passengers' : 'Passenger'; ?>
                    </p>
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
                </div>
            <?php elseif (empty($flights)): ?>
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
                </div>
            <?php else: ?>
                <!-- Flight Results -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Found <?php echo count($flights); ?> flights from <?php echo htmlspecialchars($departure_city); ?> to <?php echo htmlspecialchars($arrival_city); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Destination Image -->
                <div class="card mb-4">
                    <img src="<?php echo getCityImage($arrival_city, $cityImages, $baseUrl); ?>" 
                         alt="<?php echo htmlspecialchars($arrival_city); ?>" 
                         class="card-img-top destination-img"
                         onerror="this.src='<?php echo $baseUrl; ?>assets/images/destinations/default.jpg'; this.onerror=null;">
                    <div class="card-body text-center py-3">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($arrival_city); ?></h5>
                    </div>
                </div>
                
                <!-- Flight List -->
                <div class="row">
                    <?php foreach ($flights as $flight): ?>
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm flight-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Airline Info -->
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <div class="d-flex align-items-center">
                                            <?php /* Removed logo_url reference, using an airline avatar instead */ ?>
                                            <div class="bg-light p-2 rounded-circle me-3">
                                                <i class="fas fa-plane text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($flight['airline']); ?></h6>
                                                <span class="text-muted small"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Flight Times -->
                                    <div class="col-md-5 mb-3 mb-md-0">
                                        <div class="row">
                                            <div class="col-5 text-center">
                                                <h5 class="mb-0"><?php echo formatFlightTime($flight['departure_time']); ?></h5>
                                                <div class="small text-muted"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                            </div>
                                            
                                            <div class="col-2 flight-duration">
                                                <span class="small text-muted"><?php echo calculateDuration($flight['departure_time'], $flight['arrival_time']); ?></span>
                                                <div class="flight-path">
                                                    <i class="fas fa-plane small text-primary"></i>
                                                </div>
                                            </div>
                                            
                                            <div class="col-5 text-center">
                                                <h5 class="mb-0"><?php echo formatFlightTime($flight['arrival_time']); ?></h5>
                                                <div class="small text-muted"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Price and Book Button -->
                                    <div class="col-md-2 text-md-center mb-3 mb-md-0">
                                        <div class="fw-bold text-primary">$<?php echo number_format($flight['price'], 2); ?></div>
                                        <div class="small text-muted">per passenger</div>
                                    </div>
                                    
                                    <div class="col-md-2 text-md-end">
                                        <a href="../booking/book.php?flight_id=<?php echo $flight['flight_id']; ?>&passengers=<?php echo isset($_GET['passengers']) ? htmlspecialchars($_GET['passengers']) : '1'; ?>" class="btn btn-primary">
                                            Select <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Expandable Details -->
                                <div class="mt-3 pt-3 border-top">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <span class="small text-muted me-2">Date:</span>
                                            <span class="small"><?php echo date('l, M d, Y', strtotime($flight['departure_time'])); ?></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <span class="small text-muted me-2">Aircraft:</span>
                                            <span class="small"><?php echo !empty($flight['aircraft']) ? htmlspecialchars($flight['aircraft']) : 'Standard Aircraft'; ?></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <span class="small text-muted me-2">Available Seats:</span>
                                            <span class="small"><?php echo $flight['seats_available']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Return Flight Section (if applicable) -->
                <?php if (isset($_SESSION['return_flights']) && !empty($_SESSION['return_flights']) && isset($_GET['trip_type']) && $_GET['trip_type'] == 'round-trip'): ?>
                    <div class="mt-5">
                        <h3 class="mb-4">Return Flights</h3>
                        <p class="text-muted">
                            Flights from <?php echo htmlspecialchars($arrival_city); ?> to <?php echo htmlspecialchars($departure_city); ?> · 
                            <?php echo date('M d, Y', strtotime($_GET['return_date'])); ?>
                        </p>
                        
                        <!-- Return Flight List -->
                        <div class="row">
                            <?php foreach ($_SESSION['return_flights'] as $flight): ?>
                            <div class="col-12 mb-4">
                                <div class="card shadow-sm flight-card">
                                    <div class="card-body">
                                        <!-- Similar structure as outbound flights -->
                                        <div class="row align-items-center">
                                            <!-- Airline Info -->
                                            <div class="col-md-3 mb-3 mb-md-0">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light p-2 rounded-circle me-3">
                                                        <i class="fas fa-plane text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($flight['airline']); ?></h6>
                                                        <span class="text-muted small"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Flight Times -->
                                            <div class="col-md-5 mb-3 mb-md-0">
                                                <div class="row">
                                                    <div class="col-5 text-center">
                                                        <h5 class="mb-0"><?php echo formatFlightTime($flight['departure_time']); ?></h5>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                                    </div>
                                                    
                                                    <div class="col-2 flight-duration">
                                                        <span class="small text-muted"><?php echo calculateDuration($flight['departure_time'], $flight['arrival_time']); ?></span>
                                                        <div class="flight-path">
                                                            <i class="fas fa-plane small text-primary"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-5 text-center">
                                                        <h5 class="mb-0"><?php echo formatFlightTime($flight['arrival_time']); ?></h5>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Price and Book Button -->
                                            <div class="col-md-2 text-md-center mb-3 mb-md-0">
                                                <div class="fw-bold text-primary">$<?php echo number_format($flight['price'], 2); ?></div>
                                                <div class="small text-muted">per passenger</div>
                                            </div>
                                            
                                            <div class="col-md-2 text-md-end">
                                                <a href="../booking/book.php?flight_id=<?php echo $flight['flight_id']; ?>&return=1&passengers=<?php echo isset($_GET['passengers']) ? htmlspecialchars($_GET['passengers']) : '1'; ?>" class="btn btn-primary">
                                                    Select <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Expandable Details -->
                                        <div class="mt-3 pt-3 border-top">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <span class="small text-muted me-2">Date:</span>
                                                    <span class="small"><?php echo date('l, M d, Y', strtotime($flight['departure_time'])); ?></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="small text-muted me-2">Aircraft:</span>
                                                    <span class="small"><?php echo !empty($flight['aircraft']) ? htmlspecialchars($flight['aircraft']) : 'Standard Aircraft'; ?></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="small text-muted me-2">Available Seats:</span>
                                                    <span class="small"><?php echo $flight['seats_available']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($_SESSION['return_flights'])): ?>
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        No return flights found for the selected date. Please try a different date.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <!-- Popular Destinations -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-4">Popular Destinations</h2>
                </div>
            </div>
            
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <?php foreach ($popular_destinations as $destination): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo getCityImage($destination, $cityImages, $baseUrl); ?>" 
                                 class="card-img-top destination-img" 
                                 alt="<?php echo htmlspecialchars($destination); ?>"
                                 onerror="this.src='<?php echo $baseUrl; ?>assets/images/destinations/default.jpg'; this.onerror=null;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($destination); ?></h5>
                                <p class="card-text">Discover amazing deals on flights to <?php echo htmlspecialchars($destination); ?>.</p>
                                <a href="?departure_city=&arrival_city=<?php echo urlencode($destination); ?>&departure_date=<?php echo date('Y-m-d'); ?>&passengers=1" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- City card with most popularities -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card bg-dark text-white">
                        <img src="../assets/images/destinations/default.jpg" class="card-img destination-img" alt="Explore the World" style="height: 400px; object-fit: cover; opacity: 0.6;">
                        <div class="card-img-overlay d-flex flex-column justify-content-center text-center">
                            <h2 class="card-title display-4 fw-bold">Ready to Explore?</h2>
                            <p class="card-text lead">Find your perfect flight with SkyWay Airlines</p>
                            <div>
                                <a href="#" class="btn btn-primary btn-lg">Start Your Journey</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle return date field based on trip type
            $('input[name="trip_type"]').change(function() {
                var tripType = $('input[name="trip_type"]:checked').val();
                if (tripType === 'round-trip') {
                    $('#return_date_container').show();
                    $('#return_date').prop('required', true);
                } else {
                    $('#return_date_container').hide();
                    $('#return_date').prop('required', false);
                }
            }).change(); // Trigger change on page load
            
            // Initialize autocomplete for city fields
            var cities = <?php echo json_encode($city_list); ?>;
            $(".city-autocomplete").autocomplete({
                source: cities,
                minLength: 1
            });
            
            // Set min value for return date based on departure date
            $('#departure_date').change(function() {
                var departureDate = $(this).val();
                $('#return_date').attr('min', departureDate);
                
                // If return date is earlier than departure date, update it
                var returnDate = $('#return_date').val();
                if (returnDate < departureDate) {
                    $('#return_date').val(departureDate);
                }
            });
        });
    </script>
</body>
</html>