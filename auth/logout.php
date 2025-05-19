<?php
session_start();

// Check if the user is an admin before logging out
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['user_id'])) {
    // Include database connection
    require_once '../db/db_config.php';
    
    // Log the logout action
    $admin_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check if admin_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
    if ($table_check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'logout', ?, ?)");
        $details = "Admin logout from IP: $ip";
        $stmt->bind_param("iss", $admin_id, $details, $ip);
        $stmt->execute();
    }
}

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
header("Location: ../auth/login.php");
exit;
?>
