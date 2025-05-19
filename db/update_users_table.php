<?php
// This script adds the account_status column to the users table if it doesn't exist

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Function to display message
function showMessage($type, $message) {
    $bgColor = $type === 'success' ? 'bg-success' : 'bg-danger';
    echo "<div class='alert $bgColor text-white'>$message</div>";
}

// Check if the account_status column exists
$column_exists = false;
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
if ($result->num_rows > 0) {
    $column_exists = true;
}

// Add the column if it doesn't exist
if (!$column_exists) {
    $sql = "ALTER TABLE users ADD COLUMN account_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'";
    
    if ($conn->query($sql) === TRUE) {
        // Update all existing users to active status
        $conn->query("UPDATE users SET account_status = 'active'");
        $success = true;
        $message = "The account_status column has been added successfully to the users table.";
    } else {
        $success = false;
        $message = "Error adding column: " . $conn->error;
    }
} else {
    $success = true;
    $message = "The account_status column already exists in the users table.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database - Admin Dashboard</title>
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
            <?php include '../admin/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Database Update</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="../admin/manage_users.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Update Users Table</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <?php showMessage($success ? 'success' : 'danger', $message); ?>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h6>What was updated?</h6>
                            <p>
                                The <code>account_status</code> column is needed to track whether a user account is:
                            </p>
                            <ul>
                                <li><strong>Active:</strong> User can log in normally</li>
                                <li><strong>Inactive:</strong> Account is dormant but can be reactivated</li>
                                <li><strong>Suspended:</strong> User access has been temporarily blocked</li>
                            </ul>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                This column allows administrators to manage user accounts without deleting them.
                            </div>

                            <h6 class="mt-4">What next?</h6>
                            <p>You can now:</p>
                            <div class="d-flex gap-2 mt-3">
                                <a href="../admin/manage_users.php" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i> Manage Users
                                </a>
                                <a href="../admin/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                                </a>
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
