<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if ($booking_id > 0) {
        try {
            // Check if the admin_notes column exists
            $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'admin_notes'");
            $admin_notes_exists = ($column_check->num_rows > 0);
            
            if ($admin_notes_exists) {
                // If admin_notes column exists, update it
                $stmt = $conn->prepare("UPDATE bookings SET admin_notes = ? WHERE booking_id = ?");
                $stmt->bind_param("si", $admin_notes, $booking_id);
                $stmt->execute();
            } else {
                // If admin_notes column doesn't exist, redirect to the column update script
                $_SESSION['redirect_after_update'] = "booking_details.php?id=$booking_id";
                header("Location: ../db/update_admin_notes.php");
                exit();
            }
            
            $_SESSION['success_message'] = "Notes updated successfully.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating notes: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid booking ID.";
    }
    
    // Redirect back to booking details
    header("Location: booking_details.php?id=$booking_id");
    exit();
}

// If not a POST request, redirect to manage bookings
header("Location: manage_bookings.php");
exit();
?>
