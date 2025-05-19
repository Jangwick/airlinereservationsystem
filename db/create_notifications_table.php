<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check if the notifications table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows > 0) {
    $table_exists = true;
}

// Create the table if it doesn't exist
if (!$table_exists) {
    $sql = "CREATE TABLE notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        $success = true;
        $message = "The notifications table has been created successfully.";
    } else {
        $success = false;
        $message = "Error creating table: " . $conn->error;
    }
} else {
    $success = true;
    $message = "The notifications table already exists.";
}

// Display result
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='p-5'>
    <div class='container'>
        <h1>Database Update</h1>
        <div class='alert alert-" . ($success ? 'success' : 'danger') . "'>
            $message
        </div>
        <a href='../admin/manage_bookings.php' class='btn btn-primary'>Return to Booking Management</a>
    </div>
</body>
</html>";
?>
