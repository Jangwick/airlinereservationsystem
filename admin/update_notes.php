<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    // Validate booking ID
    if ($booking_id <= 0) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Invalid booking ID'
        ];
        header("Location: manage_bookings.php");
        exit();
    }
    
    try {
        // Update admin notes
        $stmt = $conn->prepare("UPDATE bookings SET admin_notes = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $admin_notes, $booking_id);
        $stmt->execute();
        
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        try {
            $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
            if ($table_check->num_rows > 0) {
                $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, entity_id, details, ip_address, created_at) 
                                         VALUES (?, 'update_notes', ?, 'Updated admin notes', ?, NOW())");
                $log_stmt->bind_param("iis", $admin_id, $booking_id, $ip_address);
                $log_stmt->execute();
            }
        } catch (Exception $e) {
            // Ignore if admin_logs table doesn't exist
        }
        
        $_SESSION['booking_status'] = [
            'type' => 'success',
            'message' => 'Admin notes updated successfully'
        ];
        
        // Redirect back to booking details
        header("Location: booking_details.php?id=" . $booking_id);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Error updating notes: ' . $e->getMessage()
        ];
        header("Location: booking_details.php?id=" . $booking_id);
        exit();
    }
} else {
    header("Location: manage_bookings.php");
    exit();
}
?>
