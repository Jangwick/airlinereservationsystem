<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$message = '';
$updated_count = 0;
$error_count = 0;

// Process form submission for bulk update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all_prices'])) {
    // Begin transaction for safety
    $conn->begin_transaction();
    
    try {
        // Get all flights
        $stmt = $conn->prepare("SELECT flight_id, price FROM flights");
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Prepare update statement
        $update_stmt = $conn->prepare("UPDATE flights SET 
                                    base_fare = ?, 
                                    taxes_fees = ?, 
                                    updated_at = ? 
                                    WHERE flight_id = ?");
        
        // Current timestamp
        $current_timestamp = date('Y-m-d H:i:s');
        
        // Process each flight
        while ($flight = $result->fetch_assoc()) {
            $flight_id = $flight['flight_id'];
            $price = floatval($flight['price']);
            
            // Calculate base fare and taxes
            $base_fare = $price * 0.85; // 85% of price
            $taxes_fees = $price * 0.15; // 15% of price
            
            // Update the flight
            $update_stmt->bind_param("ddsi", $base_fare, $taxes_fees, $current_timestamp, $flight_id);
            if ($update_stmt->execute()) {
                $updated_count++;
            } else {
                $error_count++;
            }
        }
        
        // Commit transaction if no errors
        $conn->commit();
        
        $message = '<div class="alert alert-success">Successfully updated pricing for ' . $updated_count . ' flights. ' . 
                  ($error_count > 0 ? $error_count . ' flights could not be updated.' : '') . '</div>';
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $message = '<div class="alert alert-danger">Error updating flight prices: ' . $e->getMessage() . '</div>';
    }
}

// Get statistics for pricing
$stmt = $conn->prepare("SELECT 
                     COUNT(*) as total_flights,
                     COUNT(CASE WHEN base_fare IS NULL OR base_fare = 0 THEN 1 END) as missing_base_fare,
                     COUNT(CASE WHEN taxes_fees IS NULL OR taxes_fees = 0 THEN 1 END) as missing_taxes
                     FROM flights");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Update Flight Prices - Admin Dashboard</title>
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
                        <h1 class="h2 mb-0">Bulk Update Flight Prices</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 small text-muted">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage_flights.php">Flights</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Bulk Update Prices</li>
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
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Price Calculation</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Price Calculation Procedure</h5>
                            <p>All flights should follow the same pricing procedure:</p>
                            <ul>
                                <li><strong>Base Fare:</strong> 85% of the total price</li>
                                <li><strong>Taxes & Fees:</strong> 15% of the total price</li>
                                <li><strong>Total Price:</strong> Base Fare + Taxes & Fees</li>
                            </ul>
                            <p class="mb-0">Use this tool to update all flights to follow this pricing structure.</p>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Flight Price Statistics</h6>
                                        <ul class="list-unstyled">
                                            <li>Total Flights: <strong><?php echo $stats['total_flights']; ?></strong></li>
                                            <li>Flights Missing Base Fare: <strong><?php echo $stats['missing_base_fare']; ?></strong></li>
                                            <li>Flights Missing Taxes & Fees: <strong><?php echo $stats['missing_taxes']; ?></strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Example Calculation</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td>Total Price:</td>
                                                <td>$100.00</td>
                                            </tr>
                                            <tr>
                                                <td>Base Fare (85%):</td>
                                                <td>$85.00</td>
                                            </tr>
                                            <tr>
                                                <td>Taxes & Fees (15%):</td>
                                                <td>$15.00</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <?php if ($stats['missing_base_fare'] > 0 || $stats['missing_taxes'] > 0): ?>
                                <p class="text-warning mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Some flights need price structure updates.</p>
                                <form method="post" action="">
                                    <button type="submit" name="update_all_prices" class="btn btn-primary">
                                        <i class="fas fa-sync-alt me-2"></i>Update All Flight Prices
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>All flights have the correct price structure!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">What This Tool Does</h5>
                    </div>
                    <div class="card-body">
                        <p>This utility ensures that all flights in the database follow a consistent pricing structure, like the flight SK107:</p>
                        <ol>
                            <li>It calculates the Base Fare as 85% of the total price</li>
                            <li>It calculates the Taxes & Fees as 15% of the total price</li>
                            <li>It updates all flights in the database with these calculated values</li>
                        </ol>
                        <p>This ensures that passengers see consistent pricing breakdowns for all flights.</p>
                        <div class="alert alert-light">
                            <strong>Note:</strong> This doesn't change the total price of any flight, it only ensures the base fare and taxes are properly calculated and stored.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
