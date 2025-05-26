<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Include currency helper
require_once '../includes/currency_helper.php';

// Get currency symbol
$currency_symbol = getCurrencySymbol($conn);

// Common function to get base URL
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}
$baseUrl = getBaseUrl();

// Check if 'status' column exists in flights table
$status_column_exists = true; // Initialize the variable
try {
    $result = $conn->query("SHOW COLUMNS FROM flights LIKE 'status'");
    $status_column_exists = ($result && $result->num_rows > 0);
} catch (Exception $e) {
    $status_column_exists = false;
}

// Initialize filters
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_departure = isset($_GET['departure']) ? $_GET['departure'] : '';
$filter_arrival = isset($_GET['arrival']) ? $_GET['arrival'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Limit for pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Base query - fixed SQL syntax issue
$query = "SELECT f.*, 
         (CASE WHEN f.price > 0 THEN f.price * 0.85 ELSE 200 END) as base_fare,
         (CASE WHEN f.price > 0 THEN f.price * 0.15 ELSE 30 END) as taxes_fees,
         (f.total_seats - COALESCE((SELECT SUM(passengers) FROM bookings WHERE flight_id = f.flight_id AND booking_status != 'cancelled'), 0)) AS available_seats
         FROM flights f 
         WHERE 1=1";

$params = [];
$types = "";

// Add filters
if (!empty($filter_date)) {
    $query .= " AND DATE(departure_time) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if (!empty($filter_airline)) {
    $query .= " AND airline = ?";
    $params[] = $filter_airline;
    $types .= "s";
}

if (!empty($filter_departure)) {
    $query .= " AND departure_city = ?";
    $params[] = $filter_departure;
    $types .= "s";
}

if (!empty($filter_arrival)) {
    $query .= " AND arrival_city = ?";
    $params[] = $filter_arrival;
    $types .= "s";
}

if ($status_column_exists && !empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Count total matching flights
$count_query = $query;
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_flights = $count_result->num_rows;
$total_pages = ceil($total_flights / $limit);

// Add pagination to query
$query .= " ORDER BY departure_time ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$flights = [];
while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
}

// Get distinct airlines for filter dropdown
$airlines_query = "SELECT DISTINCT airline FROM flights ORDER BY airline";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
while ($row = $airlines_result->fetch_assoc()) {
    $airlines[] = $row['airline'];
}

// Get distinct departure cities for filter dropdown
$departures_query = "SELECT DISTINCT departure_city FROM flights ORDER BY departure_city";
$departures_result = $conn->query($departures_query);
$departures = [];
while ($row = $departures_result->fetch_assoc()) {
    $departures[] = $row['departure_city'];
}

// Get distinct arrival cities for filter dropdown
$arrivals_query = "SELECT DISTINCT arrival_city FROM flights ORDER BY arrival_city";
$arrivals_result = $conn->query($arrivals_query);
$arrivals = [];
while ($row = $arrivals_result->fetch_assoc()) {
    $arrivals[] = $row['arrival_city'];
}

// Get statuses for filter dropdown (if column exists)
$statuses = [];
if ($status_column_exists) {
    $statuses_query = "SELECT DISTINCT status FROM flights WHERE status IS NOT NULL ORDER BY status";
    $statuses_result = $conn->query($statuses_query);
    if ($statuses_result) {
        while ($row = $statuses_result->fetch_assoc()) {
            $statuses[] = $row['status'];
        }
    }
}
$statuses = array_filter($statuses); // Remove empty values

// Add default statuses if none found
if (empty($statuses)) {
    $statuses = ['scheduled', 'delayed', 'cancelled', 'departed', 'arrived'];
}

// Format flight date and duration function
function formatFlightDuration($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    $interval = $dep->diff($arr);
    return sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);
}

// Get default number of passengers
$default_passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
if ($default_passengers < 1 || $default_passengers > 9) {
    $default_passengers = 1;
}

// Current date for default filter
$current_date = date('Y-m-d');

// Function to determine CSS class for a flight status
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'scheduled': return 'success';
        case 'delayed': return 'warning';
        case 'cancelled': return 'danger';
        case 'departed': return 'primary';
        case 'arrived': return 'info';
        default: return 'secondary';
    }
}

// Check if flights are available for future dates
$future_flights_available = false;
$future_flights_query = "SELECT 1 FROM flights WHERE departure_time > NOW() LIMIT 1";
$future_flights_result = $conn->query($future_flights_query);
if ($future_flights_result && $future_flights_result->num_rows > 0) {
    $future_flights_available = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Flights - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-2">Available Flights</h1>
                <p class="text-muted">Select from our available flights and book your next journey</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Filter Flights</h5>
            </div>
            <div class="card-body">
                <form class="row g-3" method="get" action="list.php">
                    <div class="col-md-3">
                        <label for="departure" class="form-label">Departure City</label>
                        <select class="form-select" id="departure" name="departure">
                            <option value="">All Departure Cities</option>
                            <?php foreach ($departures as $city): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($filter_departure == $city) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="arrival" class="form-label">Arrival City</label>
                        <select class="form-select" id="arrival" name="arrival">
                            <option value="">All Arrival Cities</option>
                            <?php foreach ($arrivals as $city): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($filter_arrival == $city) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date" class="form-label">Departure Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="airline" class="form-label">Airline</label>
                        <select class="form-select" id="airline" name="airline">
                            <option value="">All Airlines</option>
                            <?php foreach ($airlines as $airline): ?>
                                <option value="<?php echo htmlspecialchars($airline); ?>" <?php echo ($filter_airline == $airline) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($airline); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filter_status == $status) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sync me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Flight List -->
        <div class="row">
            <?php if (count($flights) > 0): ?>
                <?php foreach ($flights as $flight): ?>
                    <?php
                    // Calculate flight duration
                    $departure = new DateTime($flight['departure_time']);
                    $arrival = new DateTime($flight['arrival_time']);
                    $interval = $departure->diff($arrival);
                    $duration = sprintf('%dh %dm', $interval->h, $interval->i);
                    
                    // Format dates
                    $departure_date = $departure->format('l, M j, Y');
                    $departure_time = $departure->format('h:i A');
                    $arrival_time = $arrival->format('h:i A');
                    ?>

                    <!-- Flight Card -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Airline Info -->
                                <div class="col-md-2 text-center mb-3 mb-md-0">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($flight['airline']); ?>&background=0D6EFD&color=fff&size=60&bold=true&format=svg" 
                                         alt="<?php echo htmlspecialchars($flight['airline']); ?>" 
                                         class="mb-2" width="60" height="60">
                                    <div class="fw-bold"><?php echo htmlspecialchars($flight['airline']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                </div>
                                
                                <!-- Flight Details -->
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="fw-bold text-primary"><?php echo $departure_time; ?></div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($flight['departure_airport']); ?></div>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <div class="text-muted small"><?php echo $duration; ?></div>
                                            <div class="flight-path">
                                                <i class="fas fa-plane text-primary"></i>
                                            </div>
                                            <div class="text-muted small">Direct</div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="fw-bold text-primary"><?php echo $arrival_time; ?></div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($flight['arrival_airport']); ?></div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center text-md-start">
                                        <div class="small"><strong>Date:</strong> <?php echo $departure_date; ?></div>
                                        <?php if (isset($flight['seats_available']) && $flight['seats_available'] > 0): ?>
                                            <div class="small text-success"><?php echo $flight['seats_available']; ?> seats available</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Pricing and Action -->
                                <div class="col-md-2 text-md-end">
                                    <div class="fs-5 fw-bold"><?php echo $currency_symbol . number_format($flight['price'], 2); ?></div>
                                    <small class="text-muted"><?php echo $flight['available_seats']; ?> seats left</small>
                                    <div class="mt-2">
                                        <a href="flight_details.php?id=<?php echo $flight['flight_id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h4>No Flights Found</h4>
                        <p>There are no flights matching your search criteria. Please try different filters or dates.</p>
                        <a href="list.php" class="btn btn-primary mt-3">View All Flights</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="row">
            <div class="col-12">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
    .flight-path {
        position: relative;
        height: 2px;
        background-color: #e9ecef;
        margin: 15px 0;
    }
    
    .flight-path i {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(90deg);
        background-color: white;
        padding: 0 5px;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default date to today if not set
        const dateInput = document.getElementById('date');
        if (!dateInput.value) {
            const today = new Date();
            dateInput.value = today.toISOString().split('T')[0];
        }
    });
    </script>
</body>
</html>
