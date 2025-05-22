<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check for various database issues
$issues = [];

// Check if admin_notes column exists
$column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'admin_notes'");
if ($column_check->num_rows == 0) {
    $issues[] = [
        'issue' => 'Missing admin_notes column in bookings table',
        'description' => 'This column is needed to store administrator notes about bookings.',
        'fix_url' => 'update_admin_notes.php',
        'fix_text' => 'Add admin_notes Column'
    ];
}

// Check if bookings table has price_per_passenger column
$column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'price_per_passenger'");
if ($column_check->num_rows == 0) {
    $issues[] = [
        'issue' => 'Missing price_per_passenger column in bookings table',
        'description' => 'This column is needed to store the price per passenger for each booking.',
        'fix_url' => 'update_bookings_table.php',
        'fix_text' => 'Update Bookings Table'
    ];
}

// Check if bookings table has base_fare and taxes_fees columns
$column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'base_fare'");
if ($column_check->num_rows == 0) {
    $issues[] = [
        'issue' => 'Missing base_fare column in bookings table',
        'description' => 'This column is needed to store the base fare breakdown.',
        'fix_url' => 'update_bookings_table.php',
        'fix_text' => 'Update Bookings Table'
    ];
}

// Check for flights with zero prices
$zero_price_count = $conn->query("SELECT COUNT(*) as count FROM flights WHERE price IS NULL OR price = 0")->fetch_assoc()['count'];
if ($zero_price_count > 0) {
    $issues[] = [
        'issue' => $zero_price_count . ' flights with zero or missing prices',
        'description' => 'These flights will show $0.00 to users. This can affect bookings and revenue calculations.',
        'fix_url' => '../admin/fix_flight_prices.php',
        'fix_text' => 'Fix Flight Prices'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Utilities - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../admin/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Database Utilities</h1>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold">Database Health Check</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($issues) > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong><?php echo count($issues); ?> issue(s) found</strong> that need your attention.
                            </div>
                            
                            <div class="list-group mt-3">
                                <?php foreach ($issues as $issue): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($issue['issue']); ?></h5>
                                        <a href="<?php echo $issue['fix_url']; ?>" class="btn btn-sm btn-primary"><?php echo $issue['fix_text']; ?></a>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($issue['description']); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>All good!</strong> No database issues detected.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <h5 class="card-title">Update Bookings Table</h5>
                                <p class="card-text">Add missing columns to the bookings table (price_per_passenger, base_fare, taxes_fees).</p>
                                <a href="update_bookings_table.php" class="btn btn-primary">Run Update</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <h5 class="card-title">Add Admin Notes Column</h5>
                                <p class="card-text">Add the admin_notes column to the bookings table for storing administrative notes.</p>
                                <a href="update_admin_notes.php" class="btn btn-primary">Run Update</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <h5 class="card-title">Fix Flight Prices</h5>
                                <p class="card-text">Find and update flights with zero or missing prices.</p>
                                <a href="../admin/fix_flight_prices.php" class="btn btn-primary">Run Update</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
