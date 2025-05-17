<?php
// Set error handling to catch connection issues
$connection_error = null;
$conn = null;

try {
    // Include the database configuration
    require_once 'db_config.php';
} catch (Exception $e) {
    $connection_error = $e->getMessage();
}

// Get server details even if connection fails
$server = isset($server) ? $server : "localhost:3307";
$username = isset($username) ? $username : "root";
$database = isset($database) ? $database : "airline_reservation_system";

// Send proper content type header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px 0;
            background-color: #f8f9fa;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .test-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #198754;
        }
        .error {
            color: #dc3545;
        }
        .table-heading {
            background-color: #f1f8ff;
        }
    </style>
</head>
<body>
    <div class="container test-container">
        <div class="card test-card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Database Connection Test</h3>
            </div>
            <div class="card-body">
                <?php if ($connection_error): ?>
                    <div class="alert alert-danger mb-4">
                        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Connection Failed!</h4>
                        <p>Failed to connect to the database. Error message: <strong><?php echo htmlspecialchars($connection_error); ?></strong></p>
                    </div>
                    
                    <h5 class="mb-3">Troubleshooting Steps:</h5>
                    <ol class="mb-4">
                        <li>Check if MySQL is running in XAMPP Control Panel</li>
                        <li>Verify that MySQL is running on port 3307 (check your XAMPP configuration)</li>
                        <li>Make sure the database '<?php echo htmlspecialchars($database); ?>' exists</li>
                        <li>Confirm your username and password are correct</li>
                        <li>Check for any firewall or security software blocking connections</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <h5>Common Solutions:</h5>
                        <ul>
                            <li><strong>Start MySQL:</strong> Open XAMPP Control Panel and click "Start" next to MySQL</li>
                            <li><strong>Change Port:</strong> If MySQL is actually running on a different port, update the port in db_config.php</li>
                            <li><strong>Create Database:</strong> If the database doesn't exist, click the "Create Database" button below</li>
                        </ul>
                    </div>
                    
                    <h5 class="mb-3">Connection Details:</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th class="table-heading">Server</th>
                            <td><?php echo htmlspecialchars($server); ?></td>
                        </tr>
                        <tr>
                            <th class="table-heading">Username</th>
                            <td><?php echo htmlspecialchars($username); ?></td>
                        </tr>
                        <tr>
                            <th class="table-heading">Database</th>
                            <td><?php echo htmlspecialchars($database); ?></td>
                        </tr>
                        <tr>
                            <th class="table-heading">Connection Status</th>
                            <td><span class="error">Failed</span></td>
                        </tr>
                    </table>
                    
                    <div class="mt-4">
                        <h5>Available Actions:</h5>
                        <a href="create_database.php" class="btn btn-primary">Create Database</a>
                        <a href="../index.php" class="btn btn-secondary ms-2">Return to Homepage</a>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-info ms-2">Retry Connection</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-4">
                        <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Connected Successfully!</h4>
                        <p>Successfully connected to the <strong><?php echo htmlspecialchars($database); ?></strong> database on <strong><?php echo htmlspecialchars($server); ?></strong></p>
                    </div>

                    <h5 class="mb-3">Connection Details:</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th class="table-heading">Server</th>
                            <td><?php echo htmlspecialchars($server); ?></td>
                        </tr>
                        <tr>
                            <th class="table-heading">Username</th>
                            <td><?php echo htmlspecialchars($username); ?></td>
                        </tr>
                        <tr>
                            <th class="table-heading">Database</th>
                            <td><?php echo htmlspecialchars($database); ?></td>
                        </tr>
                        <tr>
                            <th class="table-heading">Connection Status</th>
                            <td><span class="success">Connected</span></td>
                        </tr>
                    </table>

                    <h5 class="mb-3 mt-4">Database Tables:</h5>
                    <?php
                    // Check if tables exist
                    $tables = ['users', 'flights', 'bookings', 'payments', 'tickets', 'promos'];
                    $table_results = [];

                    foreach ($tables as $table) {
                        $query = "SHOW TABLES LIKE '$table'";
                        $result = $conn->query($query);
                        $exists = ($result && $result->num_rows > 0);
                        
                        if ($exists) {
                            // Get row count
                            $count_query = "SELECT COUNT(*) as count FROM $table";
                            $count_result = $conn->query($count_query);
                            $count = 0;
                            if ($count_result) {
                                $count = $count_result->fetch_assoc()['count'];
                            }
                            
                            $table_results[$table] = [
                                'exists' => true,
                                'count' => $count
                            ];
                        } else {
                            $table_results[$table] = [
                                'exists' => false,
                                'count' => 0
                            ];
                        }
                    }
                    ?>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="table-heading">Table Name</th>
                                <th class="table-heading">Status</th>
                                <th class="table-heading">Records</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_results as $table => $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($table); ?></td>
                                <td>
                                    <?php if ($data['exists']): ?>
                                    <span class="success"><i class="bi bi-check-circle"></i> Exists</span>
                                    <?php else: ?>
                                    <span class="error"><i class="bi bi-x-circle"></i> Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $data['count']; ?> records</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-4">
                        <h5>Database Setup Actions:</h5>
                        <a href="setup_database.php" class="btn btn-primary">Run Database Setup</a>
                        <a href="../index.php" class="btn btn-secondary ms-2">Return to Homepage</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                <div class="row">
                    <div class="col-md-6">
                        <small>PHP Version: <?php echo phpversion(); ?></small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small>MySQL Version: <?php echo $conn ? $conn->server_info : 'Not connected'; ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Icons -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
