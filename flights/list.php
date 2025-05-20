<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Initialize filter variables
$filter_departure = isset($_GET['departure']) ? $_GET['departure'] : '';
$filter_arrival = isset($_GET['arrival']) ? $_GET['arrival'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';

// Base query
$query = "SELECT f.*, f.price as flight_price FROM flights f WHERE f.departure_time > NOW()";

// If status column exists, add condition
if ($status_column_exists) {
    $query .= " AND (f.status = 'scheduled' OR f.status = 'delayed')";
}

// Add sorting
$query .= " ORDER BY f.departure_time ASC";

$params = [];
$types = "";

// Add filters
if (!empty($filter_departure)) {
    $query .= " AND (departure_city LIKE ? OR departure_airport LIKE ?)";
    $params[] = "%$filter_departure%";
    $params[] = "%$filter_departure%";
    $types .= "ss";
}

if (!empty($filter_arrival)) {
    $query .= " AND (arrival_city LIKE ? OR arrival_airport LIKE ?)";
    $params[] = "%$filter_arrival%";
    $params[] = "%$filter_arrival%";
    $types .= "ss";
}

if (!empty($filter_date)) {
    $query .= " AND DATE(departure_time) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if (!empty($filter_airline)) {
    $query .= " AND airline LIKE ?";
    $params[] = "%$filter_airline%";
    $types .= "s";
}

// Prepare and execute query
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

// Get distinct departure cities for filter dropdown
$departure_cities_query = "SELECT DISTINCT departure_city FROM flights WHERE departure_time > NOW() ORDER BY departure_city";
$departure_cities_result = $conn->query($departure_cities_query);
$departure_cities = [];
while ($row = $departure_cities_result->fetch_assoc()) {
    $departure_cities[] = $row['departure_city'];
}

// Get distinct arrival cities for filter dropdown
$arrival_cities_query = "SELECT DISTINCT arrival_city FROM flights WHERE departure_time > NOW() ORDER BY arrival_city";
$arrival_cities_result = $conn->query($arrival_cities_query);
$arrival_cities = [];
while ($row = $arrival_cities_result->fetch_assoc()) {
    $arrival_cities[] = $row['arrival_city'];
}

// Get distinct airlines for filter dropdown
$airlines_query = "SELECT DISTINCT airline FROM flights WHERE departure_time > NOW() ORDER BY airline";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
while ($row = $airlines_result->fetch_assoc()) {
    $airlines[] = $row['airline'];
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
                            <?php foreach ($departure_cities as $city): ?>
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
                            <?php foreach ($arrival_cities as $city): ?>
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

                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
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
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                            <div class="mb-3 mb-md-0 text-center text-md-start">
                                                <div class="small text-muted">Starting from</div>
                                                <div class="h3 mb-0 text-primary">$<?php echo number_format($flight['price'], 2); ?></div>
                                                <div class="small text-muted">per person</div>
                                            </div>
                                            <div class="text-center">
                                                <a href="../booking/book.php?flight_id=<?php echo $flight['flight_id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-ticket-alt me-2"></i>Book Now
                                                </a>
                                                <a href="details.php?id=<?php echo $flight['flight_id']; ?>" class="btn btn-link text-decoration-none d-block mt-2">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
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
