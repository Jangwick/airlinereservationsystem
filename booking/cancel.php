<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../user/bookings.php");
    exit();
}

// Get booking ID and user ID
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$user_id = $_SESSION['user_id'];

// Get cancellation reason
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';
if ($reason == 'Other' && !empty($_POST['other_reason'])) {
    $reason = $_POST['other_reason'];
}

// Validate booking ID
if ($booking_id <= 0) {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Invalid booking ID.'
    ];
    header("Location: ../user/bookings.php");
    exit();
}

// Check if booking exists and belongs to the user
$stmt = $conn->prepare("SELECT b.*, f.flight_id, f.available_seats FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Booking not found or you don\'t have permission to cancel it.'
    ];
    header("Location: ../user/bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Check if booking can be cancelled
if ($booking['booking_status'] === 'cancelled') {
    $_SESSION['booking_status'] = [
        'type' => 'warning',
        'message' => 'This booking has already been cancelled.'
    ];
    header("Location: ../user/bookings.php");
    exit();
}

if ($booking['booking_status'] === 'completed') {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Cannot cancel a completed booking.'
    ];
    header("Location: ../user/bookings.php");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update booking status
    $update_query = "UPDATE bookings SET booking_status = 'cancelled', cancellation_reason = ? WHERE booking_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $reason, $booking_id);
    $update_stmt->execute();
    
    // Free up flight seats
    $passenger_count = $booking['passenger_count'] ?? 1;
    $new_available_seats = $booking['available_seats'] + $passenger_count;
    
    $seats_query = "UPDATE flights SET available_seats = ? WHERE flight_id = ?";
    $seats_stmt = $conn->prepare($seats_query);
    $seats_stmt->bind_param("ii", $new_available_seats, $booking['flight_id']);
    $seats_stmt->execute();
    
    // Check if booking_history table exists
    $history_check = $conn->query("SHOW TABLES LIKE 'booking_history'");
    
    // If it exists, insert a history record
    if ($history_check->num_rows > 0) {
        $history_query = "INSERT INTO booking_history (booking_id, status, status_change, notes, updated_by) 
                         VALUES (?, 'cancelled', 'User cancelled booking', ?, ?)";
        $history_stmt = $conn->prepare($history_query);
        $history_stmt->bind_param("isi", $booking_id, $reason, $user_id);
        $history_stmt->execute();
    }
    
    // Check if notifications table exists
    $notifications_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    
    // If it exists, create notifications
    if ($notifications_check->num_rows > 0) {
        // Notify admin about cancellation
        $admin_note = "Booking #" . $booking_id . " has been cancelled by the user.";
        
        // Get admin user ID (assuming there is at least one admin)
        $admin_query = $conn->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin_query->num_rows > 0) {
            $admin_id = $admin_query->fetch_assoc()['user_id'];
            
            $admin_notification = "INSERT INTO notifications (user_id, title, message, type) 
                                  VALUES (?, 'Booking Cancelled', ?, 'booking')";
            $admin_stmt = $conn->prepare($admin_notification);
            $admin_stmt->bind_param("is", $admin_id, $admin_note);
            $admin_stmt->execute();
        }
        
        // Notify user about cancellation
        $user_note = "Your booking #" . $booking_id . " has been cancelled successfully.";
        $user_notification = "INSERT INTO notifications (user_id, title, message, type) 
                             VALUES (?, 'Booking Cancelled', ?, 'booking')";
        $user_stmt = $conn->prepare($user_notification);
        $user_stmt->bind_param("is", $user_id, $user_note);
        $user_stmt->execute();
    }
    
    // Update payment status to "refunded" if payment was completed
    if ($booking['payment_status'] == 'completed') {
        $payment_query = "UPDATE bookings SET payment_status = 'refunded' WHERE booking_id = ?";
        $payment_stmt = $conn->prepare($payment_query);
        $payment_stmt->bind_param("i", $booking_id);
        $payment_stmt->execute();
    }
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['booking_status'] = [
        'type' => 'success',
        'message' => 'Your booking has been cancelled successfully.'
    ];
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Error cancelling booking: ' . $e->getMessage()
    ];
}

header("Location: ../user/bookings.php");
exit();
