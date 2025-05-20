<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$updated = 0;
$failed = 0;
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prices'])) {
    // Get all flights with zero or null prices
    $stmt = $conn->prepare("SELECT flight_id FROM flights WHERE price = 0 OR price IS NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($flight = $result->fetch_assoc()) {
        $flight_id = $flight['flight_id'];
        $price = isset($_POST['price'][$flight_id]) ? floatval($_POST['price'][$flight_id]) : 0;
        
        if ($price > 0) {
            $update_stmt = $conn->prepare("UPDATE flights SET price = ? WHERE flight_id = ?");
            $update_stmt->bind_param("di", $price, $flight_id);
            
            if ($update_stmt->execute()) {
                $updated++;
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
    }
    
    $message = '<div class="alert alert-success">' . $updated . ' flight prices updated successfully. ' . 
               ($failed > 0 ? $failed . ' updates failed.' : '') . '</div>';
}

// Get all flights with zero or null prices
$stmt = $conn->prepare("SELECT f.*, 
                      (f.price * 0.85) as base_fare,
                      (f.price * 0.15) as taxes_fees
                      FROM flights f 
                      WHERE f.price = 0 OR f.price IS NULL 
                      ORDER BY f.departure_time");
$stmt->execute();
$result = $stmt->get_result();
$flights = [];

while ($flight = $result->fetch_assoc()) {
    $flights[] = $flight;
}

// Calculate suggested prices based on distance/cities
function getSuggestedPrice($origin, $destination) {
    // Simplified calculation - in production, use more sophisticated logic
    $major_cities = ['New York', 'London', 'Tokyo', 'Sydney', 'Paris', 'Dubai', 'Singapore', 'Manila', 'Hong Kong'];
    $domestic_base = 150;
    $international_base = 400;
    
    $origin_major = in_array($origin, $major_cities);
    $destination_major = in_array($destination, $major_cities);
    
    if ($origin_major && $destination_major) {
        return $international_base + mt_rand(100, 300);
    } else if ($origin_major || $destination_major) {
        return $international_base - mt_rand(50, 100);
    } else {
        return $domestic_base + mt_rand(25, 75);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Flight Prices - Admin Dashboard</title>
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
                    <div>
                        <h1 class="h2 mb-0">Fix Flight Prices</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 small text-muted">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage_flights.php">Flights</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Fix Prices</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_flights.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Flights
                        </a>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <?php if (count($flights) > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Found <?php echo count($flights); ?> flights with zero or missing prices. 
                    These flights will appear with $0.00 price to users. Please set appropriate prices below.
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Update Flight Prices</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="update_prices" value="1">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Flight #</th>
                                            <th>Airline</th>
                                            <th>Route</th>
                                            <th>Departure</th>
                                            <th>Suggested Price</th>
                                            <th>New Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($flights as $flight): 
                                            $suggested_price = getSuggestedPrice($flight['departure_city'], $flight['arrival_city']);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                            <td><?php echo htmlspecialchars($flight['airline']); ?></td>
                                            <td><?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($flight['departure_time'])); ?></td>
                                            <td>$<?php echo number_format($suggested_price, 2); ?></td>
                                            <td>
                                                <div class="input-group" style="width: 150px;">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" min="1" class="form-control" 
                                                           name="price[<?php echo $flight['flight_id']; ?>]" 
                                                           value="<?php echo $suggested_price; ?>" required>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Flight Prices
                                </button>
                                <a href="manage_flights.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    All flights have valid prices. No updates needed.
                </div>
                <div class="text-center py-5">
                    <a href="manage_flights.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i> Return to Flight Management
                    </a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
