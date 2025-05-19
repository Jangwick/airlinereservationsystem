<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $airline = $_POST['airline'];
        $flight_number = $_POST['flight_number'];
        $departure_city = $_POST['departure_city'];
        $arrival_city = $_POST['arrival_city'];
        $departure_airport = $_POST['departure_airport'] ?? '';
        $arrival_airport = $_POST['arrival_airport'] ?? '';
        $departure_time = $_POST['departure_date'] . ' ' . $_POST['departure_time'];
        $arrival_time = $_POST['arrival_date'] . ' ' . $_POST['arrival_time'];
        $status = $_POST['status'];
        $aircraft = $_POST['aircraft'] ?? '';
        $total_seats = $_POST['total_seats'];
        $economy_seats = $_POST['economy_seats'] ?? 0;
        $business_seats = $_POST['business_seats'] ?? 0;
        $first_class_seats = $_POST['first_class_seats'] ?? 0;
        $base_price = $_POST['base_price'];
        
        // Validate data
        if (strtotime($departure_time) >= strtotime($arrival_time)) {
            throw new Exception("Departure time must be before arrival time.");
        }
        
        if ($total_seats <= 0) {
            throw new Exception("Total seats must be greater than zero.");
        }
        
        // Check if flight number already exists
        $stmt = $conn->prepare("SELECT flight_id FROM flights WHERE flight_number = ? AND airline = ?");
        $stmt->bind_param("ss", $flight_number, $airline);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Flight number already exists for this airline.");
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO flights (airline, flight_number, departure_city, arrival_city, departure_airport, arrival_airport, departure_time, arrival_time, status, aircraft, total_seats, available_seats, economy_seats, business_seats, first_class_seats, base_price, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssssssssssiiiidi", $airline, $flight_number, $departure_city, $arrival_city, $departure_airport, $arrival_airport, $departure_time, $arrival_time, $status, $aircraft, $total_seats, $total_seats, $economy_seats, $business_seats, $first_class_seats, $base_price);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $success_message = "Flight added successfully!";
        } else {
            throw new Exception("Failed to add flight. Please try again.");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get airlines for dropdown
$airlines_query = "SELECT DISTINCT airline FROM flights ORDER BY airline";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
while ($row = $airlines_result->fetch_assoc()) {
    $airlines[] = $row['airline'];
}

// If no airlines exist yet, provide some common ones
if (empty($airlines)) {
    $airlines = ['Philippine Airlines', 'Cebu Pacific', 'AirAsia', 'Emirates', 'Singapore Airlines', 'Qatar Airways'];
}

// Get cities for dropdowns
$cities_query = "SELECT DISTINCT departure_city FROM flights UNION SELECT DISTINCT arrival_city FROM flights ORDER BY departure_city";
$cities_result = $conn->query($cities_query);
$cities = [];
while ($row = $cities_result->fetch_assoc()) {
    $cities[] = $row['departure_city'];
}

// If no cities exist yet, provide some common ones
if (empty($cities)) {
    $cities = ['Manila', 'Cebu', 'Davao', 'Singapore', 'Hong Kong', 'Tokyo', 'Dubai', 'New York', 'London', 'Sydney'];
}

// Get airports for dropdowns (if available)
$airports = [];
$airports_query = "SELECT DISTINCT departure_airport, arrival_airport FROM flights WHERE departure_airport != '' OR arrival_airport != ''";
$airports_result = $conn->query($airports_query);
if ($airports_result) {
    while ($row = $airports_result->fetch_assoc()) {
        if (!empty($row['departure_airport'])) $airports[] = $row['departure_airport'];
        if (!empty($row['arrival_airport'])) $airports[] = $row['arrival_airport'];
    }
    $airports = array_unique($airports);
    sort($airports);
}

// If no airports exist yet, provide some common ones
if (empty($airports)) {
    $airports = ['NAIA', 'Mactan-Cebu Int\'l', 'Clark Int\'l', 'Changi', 'Hong Kong Int\'l', 'Narita', 'Dubai Int\'l', 'JFK', 'Heathrow', 'Sydney Int\'l'];
}

// Get aircraft types (if available)
$aircraft_types = [];
$aircraft_query = "SELECT DISTINCT aircraft FROM flights WHERE aircraft != ''";
$aircraft_result = $conn->query($aircraft_query);
if ($aircraft_result) {
    while ($row = $aircraft_result->fetch_assoc()) {
        if (!empty($row['aircraft'])) $aircraft_types[] = $row['aircraft'];
    }
    sort($aircraft_types);
}

// If no aircraft exist yet, provide some common ones
if (empty($aircraft_types)) {
    $aircraft_types = ['Boeing 737', 'Boeing 747', 'Boeing 777', 'Boeing 787', 'Airbus A320', 'Airbus A330', 'Airbus A350', 'Airbus A380'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Flight - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h1 class="h2">Add New Flight</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_flights.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Flights
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="add_flight.php" method="post" id="flightForm">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Basic Information</h6>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="airline" class="form-label">Airline <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="airline" name="airline" list="airline-list" required>
                                            <datalist id="airline-list">
                                                <?php foreach ($airlines as $airline): ?>
                                                    <option value="<?php echo htmlspecialchars($airline); ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="flight_number" class="form-label">Flight Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="flight_number" name="flight_number" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="departure_city" class="form-label">Departure City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="departure_city" name="departure_city" list="cities-list" required>
                                            <datalist id="cities-list">
                                                <?php foreach ($cities as $city): ?>
                                                    <option value="<?php echo htmlspecialchars($city); ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="arrival_city" class="form-label">Arrival City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="arrival_city" name="arrival_city" list="cities-list" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="departure_airport" class="form-label">Departure Airport</label>
                                            <input type="text" class="form-control" id="departure_airport" name="departure_airport" list="airports-list">
                                            <datalist id="airports-list">
                                                <?php foreach ($airports as $airport): ?>
                                                    <option value="<?php echo htmlspecialchars($airport); ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="arrival_airport" class="form-label">Arrival Airport</label>
                                            <input type="text" class="form-control" id="arrival_airport" name="arrival_airport" list="airports-list">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Schedule Information</h6>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="departure_date" class="form-label">Departure Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="departure_time" class="form-label">Departure Time <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="departure_time" name="departure_time" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="arrival_date" class="form-label">Arrival Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="arrival_date" name="arrival_date" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="arrival_time" class="form-label">Arrival Time <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="arrival_time" name="arrival_time" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="scheduled">Scheduled</option>
                                                <option value="delayed">Delayed</option>
                                                <option value="cancelled">Cancelled</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="aircraft" class="form-label">Aircraft</label>
                                            <input type="text" class="form-control" id="aircraft" name="aircraft" list="aircraft-list">
                                            <datalist id="aircraft-list">
                                                <?php foreach ($aircraft_types as $aircraft): ?>
                                                    <option value="<?php echo htmlspecialchars($aircraft); ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Capacity & Pricing</h6>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="total_seats" class="form-label">Total Seats <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="total_seats" name="total_seats" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="economy_seats" class="form-label">Economy Seats</label>
                                            <input type="number" class="form-control" id="economy_seats" name="economy_seats" min="0" value="0">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="business_seats" class="form-label">Business Seats</label>
                                            <input type="number" class="form-control" id="business_seats" name="business_seats" min="0" value="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="first_class_seats" class="form-label">First Class Seats</label>
                                            <input type="number" class="form-control" id="first_class_seats" name="first_class_seats" min="0" value="0">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="base_price" class="form-label">Base Price ($) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="base_price" name="base_price" min="0.01" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary">Add Flight</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default values for date inputs
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };
            
            document.getElementById('departure_date').value = formatDate(today);
            document.getElementById('arrival_date').value = formatDate(tomorrow);
            
            // Validate form before submission
            document.getElementById('flightForm').addEventListener('submit', function(event) {
                const totalSeats = parseInt(document.getElementById('total_seats').value) || 0;
                const economySeats = parseInt(document.getElementById('economy_seats').value) || 0;
                const businessSeats = parseInt(document.getElementById('business_seats').value) || 0;
                const firstClassSeats = parseInt(document.getElementById('first_class_seats').value) || 0;
                
                // Check if sum of seat classes equals total seats
                if (economySeats + businessSeats + firstClassSeats > 0 && 
                    economySeats + businessSeats + firstClassSeats !== totalSeats) {
                    alert('The sum of Economy, Business, and First Class seats must equal the Total Seats');
                    event.preventDefault();
                    return false;
                }
                
                // Check if departure and arrival cities are different
                const departureCity = document.getElementById('departure_city').value;
                const arrivalCity = document.getElementById('arrival_city').value;
                
                if (departureCity === arrivalCity) {
                    alert('Departure and Arrival cities cannot be the same');
                    event.preventDefault();
                    return false;
                }
                
                // Validate flight times
                const departureDate = document.getElementById('departure_date').value;
                const departureTime = document.getElementById('departure_time').value;
                const arrivalDate = document.getElementById('arrival_date').value;
                const arrivalTime = document.getElementById('arrival_time').value;
                
                const departure = new Date(`${departureDate}T${departureTime}`);
                const arrival = new Date(`${arrivalDate}T${arrivalTime}`);
                
                if (departure >= arrival) {
                    alert('Departure time must be before arrival time');
                    event.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
