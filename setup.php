<?php
// This file helps users set up the Airline Reservation System

// Function to check if a step is complete
function isStepComplete($step) {
    switch ($step) {
        case 'mysql_running':
            try {
                $conn = new mysqli('localhost', 'root', '');
                $result = !$conn->connect_error;
                $conn->close();
                return $result;
            } catch (Exception $e) {
                return false;
            }
        
        case 'database_exists':
            try {
                $conn = new mysqli('localhost', 'root', '');
                $result = $conn->select_db('airline_reservation_system');
                $conn->close();
                return $result;
            } catch (Exception $e) {
                return false;
            }
            
        case 'tables_created':
            try {
                $conn = new mysqli('localhost', 'root', '', 'airline_reservation_system');
                if ($conn->connect_error) return false;
                
                $result = $conn->query("SHOW TABLES");
                $tableCount = $result ? $result->num_rows : 0;
                $conn->close();
                return $tableCount >= 6; // We should have at least 6 tables
            } catch (Exception $e) {
                return false;
            }
            
        case 'sample_data':
            try {
                $conn = new mysqli('localhost', 'root', '', 'airline_reservation_system');
                if ($conn->connect_error) return false;
                
                $result = $conn->query("SELECT COUNT(*) AS count FROM flights");
                $flightCount = 0;
                
                if ($result && $row = $result->fetch_assoc()) {
                    $flightCount = $row['count'];
                }
                
                $conn->close();
                return $flightCount > 0;
            } catch (Exception $e) {
                return false;
            }
            
        default:
            return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .setup-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .setup-steps .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .status-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .status-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #664d03;
        }
        .status-error {
            background-color: #f8d7da;
            color: #842029;
        }
        .step-header {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <i class="fas fa-plane-departure fa-4x text-primary mb-3"></i>
                <h1 class="display-5">SkyWay Airlines</h1>
                <h2 class="mb-4">System Setup Wizard</h2>
                <p class="lead">Welcome to the SkyWay Airlines Reservation System. Let's get your system up and running!</p>
            </div>
        </div>
        
        <div class="setup-steps">
            <!-- Step 1: Check MySQL Connection -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="step-header">
                        <?php if (isStepComplete('mysql_running')): ?>
                            <div class="status-icon status-success">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php else: ?>
                            <div class="status-icon status-error">
                                <i class="fas fa-times"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mb-0">Step 1: MySQL Server</h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isStepComplete('mysql_running')): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> MySQL server is running and accessible.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> Cannot connect to MySQL server.
                        </div>
                        <h5>Troubleshooting:</h5>
                        <ul>
                            <li>Make sure XAMPP is running</li>
                            <li>Start the MySQL service from XAMPP Control Panel</li>
                            <li>Check if MySQL is running on the default port (3306) or custom port</li>
                        </ul>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="db/db_config.php" class="btn btn-primary">Test MySQL Connection</a>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Create Database -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="step-header">
                        <?php if (isStepComplete('database_exists')): ?>
                            <div class="status-icon status-success">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php else: ?>
                            <div class="status-icon status-pending">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mb-0">Step 2: Create Database</h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isStepComplete('database_exists')): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> Database 'airline_reservation_system' exists.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i> The database needs to be created.
                        </div>
                        <p>Click the button below to create the 'airline_reservation_system' database.</p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="db/create_database.php" class="btn btn-primary">Create Database</a>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Create Tables -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="step-header">
                        <?php if (isStepComplete('tables_created')): ?>
                            <div class="status-icon status-success">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php else: ?>
                            <div class="status-icon status-pending">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mb-0">Step 3: Create Database Tables</h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isStepComplete('tables_created')): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> Database tables have been created.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i> Database tables need to be set up.
                        </div>
                        <p>Click the button below to create all required tables for the application.</p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="db/setup_database.php" class="btn btn-primary">Create Tables</a>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Sample Data -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="step-header">
                        <?php if (isStepComplete('sample_data')): ?>
                            <div class="status-icon status-success">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php else: ?>
                            <div class="status-icon status-pending">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mb-0">Step 4: Add Sample Data</h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isStepComplete('sample_data')): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> Sample data has been added to the database.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i> Sample flights and promo codes should be added.
                        </div>
                        <p>Sample data will make it easier to test the system functionality.</p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="db/setup_database.php" class="btn btn-primary">Add Sample Data</a>
                    </div>
                </div>
            </div>
            
            <!-- Step 5: Verify Setup -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="step-header">
                        <?php if (isStepComplete('mysql_running') && isStepComplete('database_exists') && isStepComplete('tables_created') && isStepComplete('sample_data')): ?>
                            <div class="status-icon status-success">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php else: ?>
                            <div class="status-icon status-pending">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mb-0">Step 5: Verify System</h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isStepComplete('mysql_running') && isStepComplete('database_exists') && isStepComplete('tables_created') && isStepComplete('sample_data')): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> All setup steps completed successfully!
                        </div>
                        <p>Your Airline Reservation System is now ready to use.</p>
                        <div class="mt-4 text-center">
                            <a href="index.php" class="btn btn-primary btn-lg">Go to Homepage</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i> Please complete all previous steps first.
                        </div>
                        <p>After completing all setup steps, you can verify your system configuration here.</p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="db/db_test.php" class="btn btn-primary">Run System Check</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4 mb-5">
            <div class="card-body">
                <h5><i class="fas fa-info-circle me-2 text-primary"></i>Need Help?</h5>
                <p>If you're experiencing issues with the setup:</p>
                <ol>
                    <li>Make sure XAMPP is installed and running properly</li>
                    <li>Ensure MySQL server is started in XAMPP Control Panel</li>
                    <li>Check if you have the correct database credentials in db_config.php</li>
                    <li>Try running the setup steps in order from first to last</li>
                </ol>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
