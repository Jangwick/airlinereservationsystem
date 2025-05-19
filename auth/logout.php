<?php
session_start();

// Log the logout action if user is admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['user_id'])) {
    // Try to include and use the logging function
    if (file_exists('../includes/admin_functions.php')) {
        require_once '../db/db_config.php';
        require_once '../includes/admin_functions.php';
        
        if (function_exists('logAdminAction')) {
            logAdminAction('logout', $_SESSION['user_id'], "Admin logout");
        }
    }
}

// Unset all of the session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
