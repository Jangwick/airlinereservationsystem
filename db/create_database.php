<?php
// Get server details from config but handle errors
$server = "localhost:3307";
$username = "root";
$password = "";
$database = "airline_reservation_system";

try {
    // Try to include the real config values
    include_once 'db_config.php';
} catch (Exception $e) {
    // Keep defaults if config file has errors
}

// Try both port 3307 and the default port 3306 to help users
$servers = [
    "localhost:3307" => "Port 3307 (current configuration)",
    "localhost:3306" => "Port 3306 (default MySQL port)",
    "localhost" => "Default port"
];

$message = "";
$success = false;
$connectionDetails = [];

// Test each possible server configuration
foreach ($servers as $testServer => $description) {
    try {
        // Create connection without selecting database
        $testConn = new mysqli($testServer, $username, $password);
        
        // If we get here, connection succeeded
        $connectionDetails[] = [
            'server' => $testServer,
            'status' => 'success',
            'message' => "Connection successful to $testServer ($description)"
        ];
        
        // If this is our first successful connection, use it to create the database
        if (!$success) {
            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS $database";
            if ($testConn->query($sql) === TRUE) {
                $message = "Database '$database' created successfully on $testServer!";
                $success = true;
                $server = $testServer; // Use this successful server
            } else {
                throw new Exception("Error creating database: " . $testConn->error);
            }
        }
        
        $testConn->close();
    } catch (Exception $e) {
        $connectionDetails[] = [
            'server' => $testServer,
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// If all connections failed, use the last error message
if (!$success && !empty($connectionDetails)) {
    $message = "Could not connect to MySQL server. Please check if MySQL is running.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Database - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Create Database</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Success!</h4>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                    <p>The database has been created. Now you can set up the tables.</p>
                    <div class="mt-3">
                        <a href="setup_database.php" class="btn btn-primary">Set Up Database Tables</a>
                        <a href="db_test.php" class="btn btn-secondary ms-2">Test Connection</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Error!</h4>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                    
                    <h5>Detailed Connection Tests:</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Server Configuration</th>
                                    <th>Status</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($connectionDetails as $detail): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail['server']); ?></td>
                                    <td>
                                        <?php if ($detail['status'] === 'success'): ?>
                                            <span class="text-success"><i class="bi bi-check-circle"></i> Connected</span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="bi bi-x-circle"></i> Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($detail['message']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5>Troubleshooting MySQL Connection:</h5>
                    <ol>
                        <li><strong>Check if MySQL is running:</strong>
                            <ul>
                                <li>Open XAMPP Control Panel</li>
                                <li>Check if MySQL is started (green light)</li>
                                <li>If not, click "Start" button next to MySQL</li>
                                <li>If it fails to start, check XAMPP error logs</li>
                            </ul>
                        </li>
                        <li><strong>Verify MySQL Port Configuration:</strong>
                            <ul>
                                <li>Open XAMPP Control Panel</li>
                                <li>Click "Config" button next to MySQL</li>
                                <li>Select "my.ini" to edit the config file</li>
                                <li>Look for "port=3307" and change to "port=3306" if needed</li>
                                <li>Save the file and restart MySQL service</li>
                            </ul>
                        </li>
                        <li><strong>Update your application's database configuration:</strong>
                            <ul>
                                <li>If MySQL is running on port 3306 (default), update db_config.php to use "localhost:3306" or just "localhost"</li>
                            </ul>
                        </li>
                        <li><strong>Check for conflicting services:</strong>
                            <ul>
                                <li>Another application might be using port 3307 or 3306</li>
                                <li>Try stopping other database servers if running</li>
                            </ul>
                        </li>
                    </ol>
                    
                    <div class="mt-4">
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">Try Again</a>
                        <a href="../index.php" class="btn btn-secondary ms-2">Return to Homepage</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and Icons -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
