<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Initialize variables
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_origin = isset($_GET['origin']) ? $_GET['origin'] : '';
$filter_destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query - updated to handle null values and ensure all flights are retrieved
$query = "SELECT f.*, 
         COALESCE((SELECT COUNT(*) FROM bookings b WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0) AS booked_seats,
         COALESCE((SELECT SUM(b.passengers) FROM bookings b WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0) AS booked_passengers
         FROM flights f
         WHERE 1=1";

$params = [];
$types = "";

// Add filters
if (!empty($filter_airline)) {
    $query .= " AND airline = ?";
    $params[] = $filter_airline;
    $types .= "s";
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_origin)) {
    $query .= " AND departure_city LIKE ?";
    $params[] = "%$filter_origin%";
    $types .= "s";
}

if (!empty($filter_destination)) {
    $query .= " AND arrival_city LIKE ?";
    $params[] = "%$filter_destination%";
    $types .= "s";
}

if (!empty($filter_date_from)) {
    $query .= " AND DATE(departure_time) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $query .= " AND DATE(departure_time) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (flight_number LIKE ? OR airline LIKE ? OR departure_city LIKE ? OR arrival_city LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

// Add order by clause 
$query .= " ORDER BY departure_time ASC";

// If no status filter, make sure we also get flights with NULL status
if (empty($filter_status)) {
    $query = str_replace("WHERE 1=1", "WHERE 1=1 AND (f.status IS NOT NULL OR f.status IS NULL)", $query);
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$flights = [];
while ($row = $result->fetch_assoc()) {
    // Set default status value for NULL status
    if ($row['status'] === null) {
        $row['status'] = 'scheduled'; // Default value for flights without status
    }
    $flights[] = $row;
}

// Get distinct airlines for filter dropdown
$airlines_query = "SELECT DISTINCT airline FROM flights ORDER BY airline";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
// Get distinct cities for filter dropdowns - fix the query and error handling
$cities = [];
try {
    $cities_query = "SELECT DISTINCT departure_city FROM flights WHERE departure_city IS NOT NULL AND departure_city != ''
                    UNION 
                    SELECT DISTINCT arrival_city FROM flights WHERE arrival_city IS NOT NULL AND arrival_city != '' 
                    ORDER BY departure_city";
    $cities_result = $conn->query($cities_query);
    
    if ($cities_result) {
        while ($row = $cities_result->fetch_assoc()) {
            $cities[] = $row['departure_city'];
        }
    } else {
        // Log the error
        error_log("Error fetching cities: " . $conn->error);
    }
    
    // If no cities found, add some default ones for testing
    if (empty($cities)) {
        $cities = ['New York', 'Los Angeles', 'Chicago', 'San Francisco', 'Miami', 
                  'London', 'Paris', 'Tokyo', 'Sydney', 'Dubai'];
    }
} catch (Exception $e) {
    error_log("Exception fetching cities: " . $e->getMessage());
    // Provide some default cities if the query fails
    $cities = ['New York', 'Los Angeles', 'Chicago', 'San Francisco', 'Miami', 
              'London', 'Paris', 'Tokyo', 'Sydney', 'Dubai'];
}

// Get flight statistics
// Total flights
$query_total = "SELECT COUNT(*) as count FROM flights";
$total_flights = $conn->query($query_total)->fetch_assoc()['count'];

// Status counts
$query_scheduled = "SELECT COUNT(*) as count FROM flights WHERE status = 'scheduled'";
$query_delayed = "SELECT COUNT(*) as count FROM flights WHERE status = 'delayed'";
$query_cancelled = "SELECT COUNT(*) as count FROM flights WHERE status = 'cancelled'";
$query_boarding = "SELECT COUNT(*) as count FROM flights WHERE status = 'boarding'";
$query_departed = "SELECT COUNT(*) as count FROM flights WHERE status = 'departed'";
$query_arrived = "SELECT COUNT(*) as count FROM flights WHERE status = 'arrived'";
$query_today = "SELECT COUNT(*) as count FROM flights WHERE DATE(departure_time) = CURDATE()";

$scheduled_flights = $conn->query($query_scheduled)->fetch_assoc()['count'];
$delayed_flights = $conn->query($query_delayed)->fetch_assoc()['count'];
$cancelled_flights = $conn->query($query_cancelled)->fetch_assoc()['count'];
$boarding_flights = $conn->query($query_boarding)->fetch_assoc()['count'];
$departed_flights = $conn->query($query_departed)->fetch_assoc()['count'];
$arrived_flights = $conn->query($query_arrived)->fetch_assoc()['count'];
$today_flights = $conn->query($query_today)->fetch_assoc()['count'];

// Add a utility function to check flight prices
function checkAndFixFlightPrices($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM flights WHERE price = 0 OR price IS NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo '<div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> Found ' . $count . ' flights with zero or missing prices.
                These flights will show $0.00 to users.
                <a href="fix_flight_prices.php" class="btn btn-sm btn-warning ms-2">Fix Prices</a>
              </div>';
    }
}

// Call this function before displaying the flight table
checkAndFixFlightPrices($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flights - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Flights</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printReport()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportFlights()">
                                <i class="fas fa-file-csv"></i> Export
                            </button>
                        </div>
                        <a href="add_flight.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add Flight
                        </a>
                    </div>
                </div>

                <!-- Status Cards Row 1 -->
                <div class="row mb-3">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-primary shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Total Flights</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $total_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-plane fa-2x text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-success shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Scheduled</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $scheduled_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-calendar fa-2x text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-info shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Boarding</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $boarding_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-door-open fa-2x text-info opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-primary shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Departed</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $departed_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-plane-departure fa-2x text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-secondary shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Arrived</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $arrived_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-plane-arrival fa-2x text-secondary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-warning shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Delayed</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $delayed_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Cards Row 2 -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-danger shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Cancelled</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $cancelled_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-ban fa-2x text-danger opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-info shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">Today</div>
                                        <div class="h3 mb-0 fw-bold"><?php echo $today_flights; ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-calendar-day fa-2x text-info opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Future projected card -->
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-4 border-success shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col-8">
                                        <div class="text-xs text-uppercase text-muted fw-bold mb-1">This Week</div>
                                        <div class="h3 mb-0 fw-bold"><?php 
                                            // Query for flights in the current week
                                            $query_week = "SELECT COUNT(*) as count FROM flights WHERE YEARWEEK(departure_time) = YEARWEEK(NOW())";
                                            echo $conn->query($query_week)->fetch_assoc()['count'] ?? 0;
                                        ?></div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-calendar-week fa-2x text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Styles for Stats Cards -->
                <style>
                    /* Improved styles for stats cards */
                    .card {
                        transition: transform 0.2s, box-shadow 0.2s;
                        overflow: hidden;
                    }
                    
                    .card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                    }
                    
                    .card-body {
                        position: relative;
                        z-index: 1;
                    }
                    
                    .card i {
                        position: relative;
                        z-index: 0;
                    }
                    
                    /* Font sizes */
                    .text-xs {
                        font-size: 0.7rem;
                        letter-spacing: 0.05em;
                    }
                    
                    .h3 {
                        font-size: 1.75rem;
                    }
                    
                    /* Responsive adjustments */
                    @media (max-width: 1400px) {
                        .h3 {
                            font-size: 1.5rem;
                        }
                    }
                    
                    @media (max-width: 992px) {
                        .card .col-8 {
                            width: 70%;
                        }
                        .card .col-4 {
                            width: 30%;
                        }
                    }
                </style>

                <!-- Add this right after the cards to fix any display issues -->
                <script>
                    // Fix for Stats Cards display
                    document.addEventListener('DOMContentLoaded', function() {
                        const statusCards = document.querySelectorAll('.card');
                        statusCards.forEach(card => {
                            // Force redraw of cards to fix any potential display issues
                            card.style.display = 'none';
                            card.offsetHeight; // Trigger reflow
                            card.style.display = '';
                        });
                    });
                </script>

                <!-- Flight Status Badges - for displaying statuses in flight table -->
                <script>
                    // Function to get appropriate badge class for flight status
                    function getFlightStatusBadge(status) {
                        let badgeClass = 'secondary';
                        
                        switch (status.toLowerCase()) {
                            case 'scheduled': badgeClass = 'success'; break;
                            case 'delayed': badgeClass = 'warning'; break;
                            case 'boarding': badgeClass = 'info'; break;
                            case 'departed': badgeClass = 'primary'; break;
                            case 'arrived': badgeClass = 'secondary'; break;
                            case 'cancelled': badgeClass = 'danger'; break;
                        }
                        
                        return `bg-${badgeClass}`;
                    }
                    
                    // Apply status badges on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        const statusCells = document.querySelectorAll('td[data-status]');
                        statusCells.forEach(cell => {
                            const status = cell.getAttribute('data-status');
                            const statusText = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
                            cell.innerHTML = `<span class="badge ${getFlightStatusBadge(status)}">${statusText}</span>`;
                        });
                    });
                </script>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Filter Flights</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="manage_flights.php" class="row g-3">
                            <div class="col-md-2">
                                <label for="airline" class="form-label">Airline</label>
                                <select class="form-select" id="airline" name="airline">
                                    <option value="">All Airlines</option>
                                    <?php foreach ($airlines as $airline): ?>
                                        <option value="<?php echo htmlspecialchars($airline); ?>" <?php echo $filter_airline === $airline ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($airline); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="delayed" <?php echo $filter_status === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="origin" class="form-label">Origin</label>
                                <select class="form-select" id="origin" name="origin">
                                    <option value="">All Origins</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_origin === $city ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="destination" class="form-label">Destination</label>
                                <select class="form-select" id="destination" name="destination">
                                    <option value="">All Destinations</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_destination === $city ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>">
                            </div>
                            <div class="col-md-12">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search flight number, airline, cities..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                    <a href="manage_flights.php" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Flights Table -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Flights</h5>
                            <span class="badge bg-primary"><?php echo count($flights); ?> flights found</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="flightsTable">
                                <thead>
                                    <tr>
                                        <th>Flight #</th>
                                        <th>Airline</th>
                                        <th>Route</th>
                                        <th>Departure</th>
                                        <th>Arrival</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Capacity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($flights as $flight): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                        <td><?php echo htmlspecialchars($flight['airline']); ?></td>
                                        <td><?php echo htmlspecialchars($flight['departure_city']); ?> → <?php echo htmlspecialchars($flight['arrival_city']); ?></td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($flight['departure_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($flight['arrival_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('h:i A', strtotime($flight['arrival_time'])); ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                                $departure = new DateTime($flight['departure_time']);
                                                $arrival = new DateTime($flight['arrival_time']);
                                                $duration = $arrival->diff($departure);
                                                echo $duration->h . 'h ' . $duration->i . 'm';
                                            ?>
                                        </td>
                                        <!-- Replace the status display in the flights table -->
                                        <td data-status="<?php echo htmlspecialchars($flight['status']); ?>">
                                            <!-- This will be replaced by the JavaScript function -->
                                            <?php echo htmlspecialchars(ucfirst($flight['status'])); ?>
                                        </td>
                                        <td>
                                            <?php 
                                                // Calculate capacity based on actual bookings
                                                $total = $flight['total_seats'] ?? 0;
                                                $booked_passengers = $flight['booked_passengers'] ?? 0;
                                                $available = max(0, $total - $booked_passengers);
                                                $percent = ($total > 0) ? min(100, round(($booked_passengers / $total) * 100)) : 0;
                                            ?>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-<?php echo $percent > 80 ? 'danger' : ($percent > 60 ? 'warning' : 'success'); ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $percent; ?>%" 
                                                     aria-valuenow="<?php echo $percent; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <div class="small mt-1">
                                                <?php echo $available; ?> available / <?php echo $total; ?> total
                                                <?php if ($booked_passengers > 0): ?>
                                                    <span class="text-muted">(<?php echo $booked_passengers; ?> booked)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $flight['flight_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $flight['flight_id']; ?>">
                                                    <li><a class="dropdown-item" href="edit_flight.php?id=<?php echo $flight['flight_id']; ?>">
                                                        <i class="fas fa-edit me-2"></i> Edit Flight
                                                    </a></li>
                                                    <li><a class="dropdown-item action-btn" href="#" data-action="updateStatus" data-flight-id="<?php echo $flight['flight_id']; ?>">
                                                        <i class="fas fa-plane-arrival me-2"></i> Update Status
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="view_passengers.php?flight_id=<?php echo $flight['flight_id']; ?>">
                                                        <i class="fas fa-users me-2"></i> View Passengers
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger action-btn" href="#" data-action="confirmDelete" data-flight-id="<?php echo $flight['flight_id']; ?>" data-flight-number="<?php echo htmlspecialchars($flight['flight_number']); ?>">
                                                        <i class="fas fa-trash-alt me-2"></i> Delete Flight
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php if (empty($flights)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-plane-slash fa-4x text-muted mb-3"></i>
                    <h5>No Flights Found</h5>
                    <p class="text-muted">No flights match your search criteria.</p>
                    <a href="add_flight.php" class="btn btn-primary mt-2">Add New Flight</a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Near the top of the page, add this to display status messages -->
    <?php if (isset($_SESSION['flight_status'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flight_status']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flight_status']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flight_status']); ?>
    <?php endif; ?>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Update Flight Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm" action="flight_actions.php" method="post">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" id="flightId" name="flight_id" value="">
                        
                        <div class="mb-3">
                            <label for="flightStatus" class="form-label">Flight Status</label>
                            <select class="form-select" id="flightStatus" name="status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="delayed">Delayed</option>
                                <option value="boarding">Boarding</option>
                                <option value="departed">Departed</option>
                                <option value="arrived">Arrived</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="delayReasonGroup" style="display: none;">
                            <label for="delayReason" class="form-label">Reason</label>
                            <textarea class="form-control" id="delayReason" name="reason" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notifyPassengers" name="notify_passengers" value="1" checked>
                            <label class="form-check-label" for="notifyPassengers">
                                Notify passengers via email
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('updateStatusForm').submit()">Update Status</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deleteForm" action="flight_actions.php" method="post">
                        <input type="hidden" id="deleteFlightId" name="flight_id">
                        <input type="hidden" name="action" value="delete_flight">
                    </form>
                    <p>Are you sure you want to delete flight <span id="flightToDelete" class="fw-bold"></span>?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. All bookings associated with this flight will be cancelled.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Flight</button>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#flightsTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "info": true,
            "searching": true
        });
        
        // Toggle delay reason visibility based on status
        $('#flightStatus').on('change', function() {
            const status = $(this).val();
            if (status === 'delayed' || status === 'cancelled') {
                $('#delayReasonGroup').show();
            } else {
                $('#delayReasonGroup').hide();
            }
        });
    });

    // Function to show status update modal
    function updateStatus(flightId, currentStatus = '') {
        document.getElementById('flightId').value = flightId;
        
        // Pre-select current status if provided
        if (currentStatus) {
            document.getElementById('flightStatus').value = currentStatus;
        }
        
        // Show/hide delay reason field based on selected status
        const status = document.getElementById('flightStatus').value;
        const delayReasonGroup = document.getElementById('delayReasonGroup');
        
        if (status === 'delayed' || status === 'cancelled') {
            delayReasonGroup.style.display = 'block';
            document.getElementById('delayReason').required = true;
        } else {
            delayReasonGroup.style.display = 'none';
            document.getElementById('delayReason').required = false;
        }
        
        // Show the modal
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        statusModal.show();
    }
    
    // Function to export flights
    function exportFlights() { 
        window.location.href = 'export_flights.php?' + window.location.search.substring(1); 
    }
    
    // Print function
    function printReport() { 
        window.print(); 
    }
    
    // Add this to ensure the status update functionality works
    document.addEventListener("DOMContentLoaded", function() {
        // Handle data-action attributes in flight management table
        document.querySelectorAll('[data-action="updateStatus"]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const flightId = this.getAttribute('data-flight-id');
                updateStatus(flightId);
            });
        });
        
        // Toggle delay reason visibility based on status selection
        const flightStatus = document.getElementById('flightStatus');
        if (flightStatus) {
            flightStatus.addEventListener('change', function() {
                const delayReasonGroup = document.getElementById('delayReasonGroup');
                if (this.value === 'delayed' || this.value === 'cancelled') {
                    delayReasonGroup.style.display = 'block';
                    document.getElementById('delayReason').required = true;
                } else {
                    delayReasonGroup.style.display = 'none';
                    document.getElementById('delayReason').required = false;
                }
            });
        }
    });
</script>
<!-- Flight Status Badges - add this right before the flights table where the status badges are displayed -->
<script>
    // Function to get appropriate badge class and text for flight status
    function getFlightStatusBadge(status) {
        let badgeClass, statusText;
        
        switch (status.toLowerCase()) {
            case 'scheduled':
                badgeClass = 'success';
                statusText = 'Scheduled';
                break;
            case 'delayed':
                badgeClass = 'warning';
                statusText = 'Delayed';
                break;
            case 'boarding':
                badgeClass = 'info';
                statusText = 'Boarding';
                break;
            case 'departed':
                badgeClass = 'primary';
                statusText = 'Departed';
                break;
            case 'arrived':
                badgeClass = 'secondary';
                statusText = 'Arrived';
                break;
            case 'cancelled':
                badgeClass = 'danger';
                statusText = 'Cancelled';
                break;
            default:
                badgeClass = 'secondary';
                statusText = status || 'Unknown';
        }
        
        return `<span class="badge bg-${badgeClass}">${statusText}</span>`;
    }
    
    // Apply this to all status cells when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('td[data-status]').forEach(function(cell) {
            const status = cell.getAttribute('data-status');
            cell.innerHTML = getFlightStatusBadge(status);
        });
    });
</script>
<!-- Add CSS to support the stats cards design -->
<style>
    /* Lighter versions of Bootstrap colors for icons */
    .text-primary-lighter { color: #6495ED; }
    .text-success-lighter { color: #8BC34A; }
    .text-info-lighter { color: #4FC3F7; }
    .text-warning-lighter { color: #FFD54F; }
    .text-danger-lighter { color: #FF8A80; }
    .text-secondary-lighter { color: #9E9E9E; }

    /* Improve card appearance */
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        overflow: hidden;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    /* Adjust font sizes for responsiveness */
    .display-6 {
        font-size: 1.8rem;
    }
    
    @media (max-width: 1400px) {
        .display-6 {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 992px) {
        .display-6 {
            font-size: 1.8rem;
        }
    }
</style>
</body>
</html>
