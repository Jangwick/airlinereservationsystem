<?php
session_start();

// Include functions file to avoid duplicate function declarations
require_once '../includes/functions.php';

// Get base URL using the function from functions.php
$baseUrl = getBaseUrl();

// Check if we have search parameters
$has_search = !empty($_GET);

// Get search parameters (if any)
$departure_city = $_GET['departure_city'] ?? '';
$arrival_city = $_GET['arrival_city'] ?? '';
$departure_date = $_GET['departure_date'] ?? '';
$return_date = $_GET['return_date'] ?? '';
$passengers = $_GET['passengers'] ?? 1;
$class = $_GET['class'] ?? 'economy';
$trip_type = $_GET['trip_type'] ?? 'one_way';

// Initialize flight results array (normally would come from database)
$flights = [];

// If we have search parameters, we'd normally query the database
if ($has_search) {
    // This is a placeholder - in a real app, you'd query the database
    // For now, let's create some sample flights
    $flights = [
        [
            'flight_id' => 1,
            'flight_number' => 'SK101',
            'airline' => 'SkyWay Airlines',
            'departure_city' => 'Manila',
            'arrival_city' => 'Tokyo',
            'departure_time' => '2023-07-15 08:00:00',
            'arrival_time' => '2023-07-15 13:30:00',
            'duration' => '5h 30m',
            'price' => 450.00,
            'available_seats' => 150,
        ],
        [
            'flight_id' => 2,
            'flight_number' => 'SK103',
            'airline' => 'SkyWay Airlines',
            'departure_city' => 'Manila',
            'arrival_city' => 'Singapore',
            'departure_time' => '2023-07-15 10:15:00',
            'arrival_time' => '2023-07-15 14:00:00',
            'duration' => '3h 45m',
            'price' => 320.00,
            'available_seats' => 180,
        ],
        [
            'flight_id' => 3,
            'flight_number' => 'SK105',
            'airline' => 'SkyWay Airlines',
            'departure_city' => 'Manila',
            'arrival_city' => 'Dubai',
            'departure_time' => '2023-07-15 23:15:00',
            'arrival_time' => '2023-07-16 05:45:00',
            'duration' => '9h 30m',
            'price' => 550.00,
            'available_seats' => 160,
        ],
    ];
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <!-- Search Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Search Flights</h5>
                    </div>
                    <div class="card-body">
                        <form action="search.php" method="GET">
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="trip_type" id="one_way" value="one_way" <?php echo $trip_type === 'one_way' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="one_way">One Way</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="trip_type" id="round_trip" value="round_trip" <?php echo $trip_type === 'round_trip' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="round_trip">Round Trip</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="departure_city" class="form-label">From</label>
                                <select class="form-select" id="departure_city" name="departure_city" required>
                                    <option value="">Select departure city</option>
                                    <option value="Manila" <?php echo $departure_city === 'Manila' ? 'selected' : ''; ?>>Manila, Philippines</option>
                                    <option value="Cebu" <?php echo $departure_city === 'Cebu' ? 'selected' : ''; ?>>Cebu, Philippines</option>
                                    <option value="Singapore" <?php echo $departure_city === 'Singapore' ? 'selected' : ''; ?>>Singapore</option>
                                    <option value="Tokyo" <?php echo $departure_city === 'Tokyo' ? 'selected' : ''; ?>>Tokyo, Japan</option>
                                    <option value="Hong Kong" <?php echo $departure_city === 'Hong Kong' ? 'selected' : ''; ?>>Hong Kong</option>
                                    <option value="Dubai" <?php echo $departure_city === 'Dubai' ? 'selected' : ''; ?>>Dubai, UAE</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="arrival_city" class="form-label">To</label>
                                <select class="form-select" id="arrival_city" name="arrival_city" required>
                                    <option value="">Select arrival city</option>
                                    <option value="Manila" <?php echo $arrival_city === 'Manila' ? 'selected' : ''; ?>>Manila, Philippines</option>
                                    <option value="Cebu" <?php echo $arrival_city === 'Cebu' ? 'selected' : ''; ?>>Cebu, Philippines</option>
                                    <option value="Singapore" <?php echo $arrival_city === 'Singapore' ? 'selected' : ''; ?>>Singapore</option>
                                    <option value="Tokyo" <?php echo $arrival_city === 'Tokyo' ? 'selected' : ''; ?>>Tokyo, Japan</option>
                                    <option value="Hong Kong" <?php echo $arrival_city === 'Hong Kong' ? 'selected' : ''; ?>>Hong Kong</option>
                                    <option value="Dubai" <?php echo $arrival_city === 'Dubai' ? 'selected' : ''; ?>>Dubai, UAE</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="departure_date" class="form-label">Departure Date</label>
                                <input type="date" class="form-control" id="departure_date" name="departure_date" required value="<?php echo $departure_date; ?>">
                            </div>
                            
                            <div class="mb-3 return-date-container" <?php echo $trip_type !== 'round_trip' ? 'style="display: none;"' : ''; ?>>
                                <label for="return_date" class="form-label">Return Date</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo $return_date; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="passengers" class="form-label">Passengers</label>
                                <select class="form-select" id="passengers" name="passengers">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (int)$passengers === $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Passenger<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="class" class="form-label">Class</label>
                                <select class="form-select" id="class" name="class">
                                    <option value="economy" <?php echo $class === 'economy' ? 'selected' : ''; ?>>Economy</option>
                                    <option value="business" <?php echo $class === 'business' ? 'selected' : ''; ?>>Business</option>
                                    <option value="first" <?php echo $class === 'first' ? 'selected' : ''; ?>>First Class</option>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Search Flights</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <?php if ($has_search): ?>
                    <h2 class="mb-4">Flight Search Results</h2>
                    
                    <?php if (!empty($flights)): ?>
                        <div class="mb-4">
                            <p>Showing flights from <strong><?php echo htmlspecialchars($departure_city); ?></strong> to <strong><?php echo htmlspecialchars($arrival_city); ?></strong> on <strong><?php echo htmlspecialchars($departure_date); ?></strong></p>
                        </div>
                        
                        <?php foreach ($flights as $flight): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold"><?php echo htmlspecialchars($flight['airline']); ?></span>
                                    <span class="text-muted"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="text-center me-4">
                                                    <p class="mb-0 fw-bold"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></p>
                                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                                </div>
                                                <div class="flex-grow-1 text-center">
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($flight['duration']); ?></p>
                                                    <div class="flight-path">
                                                        <i class="fas fa-plane"></i>
                                                    </div>
                                                </div>
                                                <div class="text-center ms-4">
                                                    <p class="mb-0 fw-bold"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></p>
                                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-1"><i class="fas fa-calendar-alt me-2"></i><?php echo date('F j, Y', strtotime($flight['departure_time'])); ?></p>
                                                <p class="mb-1"><i class="fas fa-users me-2"></i>Available Seats: <?php echo $flight['available_seats']; ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                            <div class="mb-2">
                                                <span class="d-block text-primary fw-bold fs-4">$<?php echo number_format($flight['price'], 2); ?></span>
                                                <span class="text-muted small">per passenger</span>
                                            </div>
                                            <a href="../booking/book.php?flight_id=<?php echo $flight['flight_id']; ?>&passengers=<?php echo urlencode($passengers); ?>&class=<?php echo urlencode($class); ?>" class="btn btn-primary">
                                                Select Flight
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>No Flights Found</h4>
                            <p>Sorry, no flights were found matching your search criteria. Please try different dates or destinations.</p>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="card text-center p-5">
                        <div class="py-5">
                            <i class="fas fa-plane-departure fa-4x text-primary mb-4"></i>
                            <h2>Search for Flights</h2>
                            <p class="lead">Please use the search form to find available flights.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle return date based on trip type
        document.addEventListener('DOMContentLoaded', function() {
            const tripTypeRadios = document.querySelectorAll('input[name="trip_type"]');
            const returnDateContainer = document.querySelector('.return-date-container');
            const returnDateInput = document.getElementById('return_date');
            
            tripTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'round_trip') {
                        returnDateContainer.style.display = 'block';
                        returnDateInput.setAttribute('required', 'required');
                    } else {
                        returnDateContainer.style.display = 'none';
                        returnDateInput.removeAttribute('required');
                    }
                });
            });
        });
    </script>
</body>
</html>
