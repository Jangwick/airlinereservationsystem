<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Function to display messages
function showMessage($type, $message) {
    $bgColor = ($type === 'success') ? 'success' : 'danger';
    echo "<div class='alert alert-$bgColor'>$message</div>";
}

// Check if admin_logs table exists
$logs_table_exists = $conn->query("SHOW TABLES LIKE 'admin_logs'")->num_rows > 0;

// Initialize or update the admin_logs table
if (!$logs_table_exists) {
    $create_table_sql = "CREATE TABLE admin_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (admin_id),
        INDEX (action),
        INDEX (created_at)
    )";
    
    if ($conn->query($create_table_sql)) {
        $success = true;
        $init_message = "Admin logs table has been successfully created.";
        
        // Add an initial log entry
        $admin_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'system_init', 'Activity logs system initialized', ?)");
        $stmt->bind_param("is", $admin_id, $ip);
        $stmt->execute();
    } else {
        $success = false;
        $init_message = "Error creating admin logs table: " . $conn->error;
    }
} else {
    $success = true;
    $init_message = "Admin logs table already exists.";
    
    // Check if any columns need to be added/modified
    $alter_needed = false;
    
    // Example: Check if ip_address column exists
    $column_check = $conn->query("SHOW COLUMNS FROM admin_logs LIKE 'ip_address'");
    if ($column_check->num_rows == 0) {
        $conn->query("ALTER TABLE admin_logs ADD COLUMN ip_address VARCHAR(45) AFTER details");
        $alter_message = "Added ip_address column to admin_logs table.";
        $alter_needed = true;
    }
    
    if ($alter_needed) {
        $init_message .= " Table structure has been updated.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Activity Logs - Admin Dashboard</title>
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
                    <h1 class="h2">Initialize Activity Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="admin_logs.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Activity Logs
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">System Status</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($success)): ?>
                                    <?php showMessage($success ? 'success' : 'danger', $init_message); ?>
                                <?php endif; ?>
                                
                                <h6 class="mt-4">Activity Logs System</h6>
                                <p>The activity logs system tracks all administrative actions performed in the system. This helps with security auditing and troubleshooting.</p>
                                
                                <div class="mt-4">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Automatic tracking of all admin actions</li>
                                        <li>Recording of IP addresses for security purposes</li>
                                        <li>Detailed logs of what changed and when</li>
                                        <li>Export capabilities for compliance reporting</li>
                                    </ul>
                                </div>
                                
                                <div class="mt-4">
                                    <h6>Actions Tracked:</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check-circle text-success me-2"></i> User Management</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Flight Management</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Booking Management</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4">
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check-circle text-success me-2"></i> System Settings</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Admin Logins/Logouts</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Data Exports</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4">
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Security Events</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Cache Operations</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Report Generation</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h6>Next Steps:</h6>
                                    <p>You can now:</p>
                                    <a href="admin_logs.php" class="btn btn-primary">
                                        <i class="fas fa-list me-1"></i> View Activity Logs
                                    </a>
                                </div>
                            </div>
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
