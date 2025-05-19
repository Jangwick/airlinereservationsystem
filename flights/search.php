<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Initialize search parameters
$departure_city = isset($_GET['departure_city']) ? trim($_GET['departure_city']) : '';
$arrival_city = isset($_GET['arrival_city']) ? trim($_GET['arrival_city']) : '';
$departure_date = isset($_GET['departure_date']) ? trim($_GET['departure_date']) : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$search_performed = isset($_GET['departure_city']) || isset($_GET['arrival_city']);

// Store search in session for recent searches feature
if ($search_performed && !empty($departure_city) && !empty($arrival_city)) {
    // Save this search to session
    $search = [
        'departure_city' => $departure_city,
        'arrival_city' => $arrival_city,
        'date' => $departure_date,
        'timestamp' => time()
    ];
    
    if (!isset($_SESSION['recent_searches'])) {
        $_SESSION['recent_searches'] = [];
    }
    
    // Add to the beginning of the array
    array_unshift($_SESSION['recent_searches'], $search);
    
    // Keep only the 5 most recent searches
    if (count($_SESSION['recent_searches']) > 5) {
        array_pop($_SESSION['recent_searches']);
    }
}

// Get available flights
$flights = [];
if ($search_performed) {
    $query = "SELECT f.* 
             FROM flights f 
             WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($departure_city)) {
        $query .= " AND f.departure_city LIKE ?";
        $params[] = "%$departure_city%";
        $types .= "s";
    }
    
    if (!empty($arrival_city)) {
        $query .= " AND f.arrival_city LIKE ?";
        $params[] = "%$arrival_city%";
        $types .= "s";
    }
    
    if (!empty($departure_date)) {
        $query .= " AND DATE(f.departure_time) = ?";
        $params[] = $departure_date;
        $types .= "s";
    } else {
        // If no date specified, show only future flights
        $query .= " AND f.departure_time > NOW()";
    }
    
    // Filter flights with enough available seats
    $query .= " AND f.available_seats >= ?";
    $params[] = $passengers;
    $types .= "i";
    
    // Order by departure time
    $query .= " ORDER BY f.departure_time ASC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($flight = $result->fetch_assoc()) {
        $flights[] = $flight;
    }
}

// Get popular destinations
$popular_destinations = [];
$destinations_query = "SELECT DISTINCT arrival_city, COUNT(*) as count 
                      FROM flights 
                      GROUP BY arrival_city 
                      ORDER BY count DESC 
                      LIMIT 6";

$result = $conn->query($destinations_query);
while ($row = $result->fetch_assoc()) {
    $popular_destinations[] = $row;
}

// Get today's date for minimum date value
$today = date('Y-m-d');
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .flight-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .flight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .flight-path {
            position: relative;
            height: 2px;
            background-color: #e0e0e0;
            margin: 15px 0;
        }
        
        .flight-path i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            color: #3b71ca;
            padding: 5px;
        }
        
        .destination-card {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            height: 200px;
            transition: transform 0.3s ease;
        }
        
        .destination-card:hover {
            transform: scale(1.03);
        }
        
        .destination-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .destination-card:hover img {
            transform: scale(1.1);
        }
        
        .destination-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
            color: white;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <!-- Search Form -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4">Search Flights</h4>
                        <form action="search.php" method="get">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="departure_city" class="form-label">From</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-plane-departure"></i></span>
                                        <input type="text" class="form-control" id="departure_city" name="departure_city" placeholder="Departure City" value="<?php echo htmlspecialchars($departure_city); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="arrival_city" class="form-label">To</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-plane-arrival"></i></span>
                                        <input type="text" class="form-control" id="arrival_city" name="arrival_city" placeholder="Arrival City" value="<?php echo htmlspecialchars($arrival_city); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="departure_date" class="form-label">Departure Date</label>
                                    <input type="date" class="form-control" id="departure_date" name="departure_date" value="<?php echo htmlspecialchars($departure_date); ?>" min="<?php echo $today; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="passengers" class="form-label">Passengers</label>
                                    <select class="form-select" id="passengers" name="passengers">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $passengers == $i ? 'selected' : ''; ?>><?php echo $i; ?> Passenger<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-4 d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Search Flights</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Results -->
        <?php if ($search_performed): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <?php if (!empty($departure_city) && !empty($arrival_city)): ?>
                                        Flights from <?php echo htmlspecialchars($departure_city); ?> to <?php echo htmlspecialchars($arrival_city); ?>
                                    <?php else: ?>
                                        Search Results
                                    <?php endif; ?>
                                </h5>
                                <span class="badge bg-primary"><?php echo count($flights); ?> flights found</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($flights) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($flights as $flight): ?>
                                        <div class="list-group-item p-4 flight-card">
                                            <div class="row align-items-center">
                                                <div class="col-md-2 text-center mb-3 mb-md-0">
                                                    <img src="../assets/images/airlines/<?php echo strtolower($flight['airline']); ?>.png" alt="Airline Logo" 
                                                         class="airline-logo mb-2" onerror="this.src='../assets/images/airlines/default.png'" width="40">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($flight['airline']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                                </div>
                                                <div class="col-md-4 mb-3 mb-md-0">
                                                    <div class="row">
                                                        <div class="col-5">
                                                            <div class="fw-bold fs-5"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></div>
                                                            <div class="text-muted"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                                        </div>
                                                        <div class="col-2 px-0 d-flex flex-column justify-content-center">
                                                            <div class="flight-path">
                                                                <i class="fas fa-plane"></i>
                                                            </div>
                                                            <div class="text-center small">
                                                                <?php
                                                                $departure = new DateTime($flight['departure_time']);
                                                                $arrival = new DateTime($flight['arrival_time']);
                                                                $interval = $departure->diff($arrival);
                                                                echo sprintf('%dh %dm', $interval->h, $interval->i);
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-5">
                                                            <div class="fw-bold fs-5"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></div>
                                                            <div class="text-muted"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                                    <div class="d-flex flex-column h-100 justify-content-center">
                                                        <div class="small text-muted mb-1">Date</div>
                                                        <div class="fw-bold"><?php echo date('M d, Y', strtotime($flight['departure_time'])); ?></div>
                                                        <div class="small text-muted mt-1">
                                                            <i class="fas fa-users me-1"></i> <?php echo $flight['available_seats']; ?> seats available
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 text-center text-md-end">
                                                    <div class="h4 mb-2 text-success">$<?php echo number_format($flight['price'], 2); ?></div>
                                                    <div class="text-muted small mb-3">per passenger</div>
                                                    <a href="../booking/book.php?flight_id=<?php echo $flight['flight_id']; ?>&passengers=<?php echo $passengers; ?>" class="btn btn-primary">
                                                        Select Flight
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-plane-slash text-muted fa-3x mb-3"></i>
                                    <h5>No Flights Found</h5>
                                    <p class="text-muted">We couldn't find any flights matching your search criteria.</p>
                                    <p class="text-muted">Please try different dates or destinations.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Popular Destinations -->
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">Popular Destinations</h4>
                </div>
                
                <?php foreach ($popular_destinations as $destination): ?>
                    <div class="col-md-4 mb-4">
                        <div class="destination-card shadow">
                            <img src="../assets/images/destinations/<?php echo strtolower(str_replace(' ', '_', $destination['arrival_city'])); ?>.jpg" 
                                 onerror="this.src='../assets/images/destinations/default.jpg'" alt="<?php echo htmlspecialchars($destination['arrival_city']); ?>">
                            <div class="destination-overlay">
                                <h5 class="mb-0"><?php echo htmlspecialchars($destination['arrival_city']); ?></h5>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span>Flights: <?php echo $destination['count']; ?></span>
                                    <a href="?arrival_city=<?php echo urlencode($destination['arrival_city']); ?>" class="btn btn-sm btn-light">
                                        View Flights
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Recent Searches -->
            <?php if (isset($_SESSION['recent_searches']) && !empty($_SESSION['recent_searches'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Recent Searches</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($_SESSION['recent_searches'] as $search): ?>
                                        <div class="col-md-4 mb-2">
                                            <a href="?departure_city=<?php echo urlencode($search['departure_city']); ?>&arrival_city=<?php echo urlencode($search['arrival_city']); ?>&departure_date=<?php echo urlencode($search['date']); ?>" class="text-decoration-none">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-grow-1">
                                                                <div class="fw-bold text-primary">
                                                                    <?php echo htmlspecialchars($search['departure_city']); ?> → <?php echo htmlspecialchars($search['arrival_city']); ?>
                                                                </div>
                                                                <div class="small text-muted">
                                                                    <?php echo !empty($search['date']) ? date('M d, Y', strtotime($search['date'])) : 'Any date'; ?>
                                                                </div>
                                                            </div>
                                                            <i class="fas fa-search text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>f; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Handle broken destination images -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all destination images
            const destImages = document.querySelectorAll('.destination-img, .popular-destination-img');
            
            // For each image, add an error handler
            destImages.forEach(img => {
                img.onerror = function() {
                    // Try with different fallback options
                    const destination = img.getAttribute('data-destination') || 'destination';
                    
                    // Option 1: Try using a placeholder image service
                    this.src = `https://source.unsplash.com/300x200/?${destination},travel,city`;
                    
                    // Option 2: If unsplash fails, use a placeholder.com image
                    this.onerror = function() {
                        this.src = `https://via.placeholder.com/300x200/DEDEDE/333333?text=${destination}`;
                        
                        // Final fallback if all else fails
                        this.onerror = function() {
                            this.src = '../assets/images/placeholder-destination.jpg';
                            this.onerror = null; // Prevent infinite loop
                        };
                    };
                };
            });
        });
    </script>
</body>
</html>