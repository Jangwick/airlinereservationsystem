<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check if the admin_notes column already exists
$column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'admin_notes'");
$admin_notes_exists = ($column_check->num_rows > 0);

$message = "";

// Add the admin_notes column if it doesn't exist
if (!$admin_notes_exists) {
    $sql = "ALTER TABLE bookings ADD COLUMN admin_notes TEXT NULL AFTER booking_status";
    
    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert alert-success'><strong>Success!</strong> The admin_notes column has been added to the bookings table.</div>";
    } else {
        $message = "<div class='alert alert-danger'><strong>Error!</strong> Failed to add admin_notes column: " . $conn->error . "</div>";
    }
} else {
    $message = "<div class='alert alert-info'><strong>Info:</strong> The admin_notes column already exists in the bookings table.</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - Admin Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Database Update: Admin Notes Column</h5>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <div class="mt-3">
                            <h6>What this update does:</h6>
                            <p>This script adds an 'admin_notes' column to the bookings table. This column is used to store administrative notes about bookings, especially when cancelling bookings or processing refunds.</p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="../admin/dashboard.php" class="btn btn-primary">Return to Admin Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
