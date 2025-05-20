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

// Get flight statistics - updated to use actual booking data
$stats_query = "SELECT 
                COUNT(*) as `total`,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as `scheduled`,
                SUM(CASE WHEN status = 'delayed' THEN 1 ELSE 0 END) as `delayed`,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as `cancelled`,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as `completed`,
                SUM(CASE WHEN departure_time > NOW() THEN 1 ELSE 0 END) as `upcoming`
                FROM flights";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
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
                    <!-- Stats Cards -->
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
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Flights</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Scheduled</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['scheduled'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Upcoming</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['upcoming'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane-departure fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Delayed</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['delayed'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-danger h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Cancelled</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['cancelled'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ban fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-secondary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Completed</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $stats['completed'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-double fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                                        <td>
                                            <?php if ($flight['status'] == 'scheduled'): ?>
                                                <span class="badge bg-success">Scheduled</span>
                                            <?php elseif ($flight['status'] == 'delayed'): ?>
                                                <span class="badge bg-warning text-dark">Delayed</span>
                                            <?php elseif ($flight['status'] == 'cancelled'): ?>
                                                <span class="badge bg-danger">Cancelled</span>
                                            <?php elseif ($flight['status'] == 'completed'): ?>
                                                <span class="badge bg-info">Completed</span>
                                            <?php endif; ?>
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

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Update Flight Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm" action="flight_actions.php" method="post">
                        <input type="hidden" id="flightId" name="flight_id">
                        <input type="hidden" name="action" value="update_status">
                        <div class="mb-3">
                            <label for="flightStatus" class="form-label">Flight Status</label>
                            <select class="form-select" id="flightStatus" name="status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="delayed">Delayed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                                <option value="boarding">Boarding</option>
                                <option value="departed">Departed</option>
                                <option value="in_air">In Air</option>
                                <option value="arrived">Arrived</option>
                            </select>
                        </div>
                        <div class="mb-3" id="delayReasonGroup" style="display: none;">
                            <label for="delayReason" class="form-label">Reason for Delay/Cancellation</label>
                            <textarea class="form-control" id="delayReason" name="reason" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveStatus">Save Changes</button>
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
            "info": true
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
        
        // Handle status form submission
        $('#saveStatus').on('click', function() {
            // In real implementation, this would use AJAX to submit to flight_actions.php
            alert('Status updated successfully!');
            $('#statusModal').modal('hide');
            // In a real implementation, you would reload or update the page
        });
        
        // Handle delete form submission
        $('#confirmDeleteBtn').on('click', function() {
            // In real implementation, this would use AJAX to submit to flight_actions.php
            alert('Flight deleted successfully!');
            $('#deleteModal').modal('hide');
            // In a real implementation, you would reload or update the page
        });
        
        // Action button event listeners using data attributes
        $(document).on('click', '.action-btn', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            const flightId = $(this).data('flight-id');
            
            if (action === 'updateStatus') {
                updateStatus(flightId);
            } else if (action === 'confirmDelete') {
                const flightNumber = $(this).data('flight-number');
                confirmDelete(flightId, flightNumber);
            }
        });
    });
    
    // Function to show status update modal
    function updateStatus(flightId) {
        console.log("Opening status modal for flight ID:", flightId);
        $('#flightId').val(flightId);
        
        // Create modal instance using Bootstrap 5 syntax
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        statusModal.show();
        
        // By default hide the delay reason field
        $('#delayReasonGroup').hide();
    }
    
    // Function to show delete confirmation modal
    function confirmDelete(flightId, flightNumber) {
        console.log("Opening delete modal for flight:", flightNumber);
        $('#deleteFlightId').val(flightId);
        $('#flightToDelete').text(flightNumber);
        
        // Create modal instance using Bootstrap 5 syntax
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    
    // Export to CSV function
    function exportFlights() {
        window.location.href = 'export_flights.php?' + window.location.search.substring(1);
    }
    
    // Print function
    function printReport() {
        window.print();
    }
</script>
</body>
</html>
