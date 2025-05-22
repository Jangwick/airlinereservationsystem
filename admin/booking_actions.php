<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Include currency helper
require_once '../includes/currency_helper.php';

// Check if action is set
if (!isset($_POST['action']) && !isset($_GET['action'])) {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'No action specified'
    ];
    header("Location: manage_bookings.php");
    exit();
}

// Get action from POST or GET
$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];

// Add this function at the top of the file after the database connection
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result->num_rows > 0;
}

// Function to ensure admin_notes column exists - called before operations that need it
function ensureAdminNotesColumn($conn) {
    if (!columnExists($conn, 'bookings', 'admin_notes')) {
        // Add the column if it doesn't exist
        $conn->query("ALTER TABLE bookings ADD COLUMN admin_notes TEXT NULL AFTER payment_status");
        return $conn->affected_rows > 0;
    }
    return true;
}

// Handle view booking action
if ($action === 'view') {
    $booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Validate booking ID
    if ($booking_id <= 0) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Invalid booking ID'
        ];
        header("Location: manage_bookings.php");
        exit();
    }
    
    // Redirect to booking details page
    header("Location: booking_details.php?id=" . $booking_id);
    exit();
}

// Handle update status action
elseif ($action === 'update_status') {
    // Get form data
    $booking_id = $_POST['booking_id'] ?? 0;
    $booking_status = $_POST['booking_status'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $admin_notes = $_POST['admin_notes'] ?? '';
    $notify_customer = isset($_POST['notify_customer']) && $_POST['notify_customer'] == 1;
    
    // Validate required fields
    if (empty($booking_id) || empty($booking_status) || empty($payment_status)) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Required fields missing'
        ];
        header("Location: manage_bookings.php");
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Ensure admin_notes column exists
        ensureAdminNotesColumn($conn);
        
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, payment_status = ?, admin_notes = ? WHERE booking_id = ?");
        $stmt->bind_param("sssi", $booking_status, $payment_status, $admin_notes, $booking_id);
        $stmt->execute();
        
        // Get booking details for notification
        $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                                f.departure_time, u.email, u.first_name, u.last_name 
                                FROM bookings b 
                                JOIN flights f ON b.flight_id = f.flight_id 
                                JOIN users u ON b.user_id = u.user_id 
                                WHERE b.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        
        // Update ticket status if needed
        if ($booking_status === 'cancelled') {
            $stmt = $conn->prepare("UPDATE tickets SET status = 'cancelled' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            
            // Also update flight available seats
            $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats + ? 
                                    WHERE flight_id = ?");
            $stmt->bind_param("ii", $booking['passengers'], $booking['flight_id']);
            $stmt->execute();
        }
        
        // Log admin action
        logAdminAction('update_booking_status', $booking_id, 
                      "Updated booking status to $booking_status and payment status to $payment_status");
        
        // Commit transaction
        $conn->commit();
        
        // Notify customer if requested
        if ($notify_customer && isset($booking['email'])) {
            sendBookingStatusEmail($booking, $booking_status, $payment_status, $admin_notes);
        }
        
        $_SESSION['booking_status'] = [
            'type' => 'success',
            'message' => 'Booking status updated successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Error updating booking status: ' . $e->getMessage()
        ];
    }
    
    header("Location: manage_bookings.php");
    exit();
}

// Handle cancel booking action
elseif ($action === 'cancel_booking') {
    // Get form data
    $booking_id = $_POST['booking_id'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $refund_amount = $_POST['refund_amount'] ?? 0;
    $notify_customer = isset($_POST['notify_customer']) && $_POST['notify_customer'] == 1;
    
    // Validate required fields
    if (empty($booking_id) || empty($reason)) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Required fields missing'
        ];
        header("Location: manage_bookings.php");
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get booking details
        $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                                f.departure_time, u.email, u.first_name, u.last_name 
                                FROM bookings b 
                                JOIN flights f ON b.flight_id = f.flight_id 
                                JOIN users u ON b.user_id = u.user_id 
                                WHERE b.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        // Check if admin_notes column exists
        $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'admin_notes'");
        $has_admin_notes = ($column_check->num_rows > 0);
        
        // Update booking status to cancelled with appropriate query based on column existence
        if ($has_admin_notes) {
            $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled', 
                                  admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?) 
                                  WHERE booking_id = ?");
            $cancel_note = "Cancelled by admin: $reason";
            $stmt->bind_param("si", $cancel_note, $booking_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' 
                                  WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }
        
        // Update tickets to cancelled
        $stmt = $conn->prepare("UPDATE tickets SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Update flight available seats
        $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats + ? 
                                WHERE flight_id = ?");
        $stmt->bind_param("ii", $booking['passengers'], $booking['flight_id']);
        $stmt->execute();
        
        // Process refund if amount > 0
        $payment_status = $booking['payment_status'];
        if ($refund_amount > 0) {
            try {
                // First check if refunds table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'refunds'");
                
                if ($table_check->num_rows > 0) {
                    // Only try to insert if the table exists
                    $stmt = $conn->prepare("INSERT INTO refunds (booking_id, amount, reason, processed_by, created_at) 
                                        VALUES (?, ?, ?, ?, NOW())");
                    $admin_id = $_SESSION['user_id'];
                    $stmt->bind_param("idsi", $booking_id, $refund_amount, $reason, $admin_id);
                    $stmt->execute();
                } else {
                    // Log that we couldn't record the refund due to missing table
                    error_log("Refund of $refund_amount processed for booking #$booking_id but couldn't be recorded because 'refunds' table doesn't exist");
                }
                
                // Always update the payment status regardless of whether refunds table exists
                $payment_status = 'refunded';
                $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                
            } catch (Exception $e) {
                // Only log the error but don't stop the cancellation process for this
                error_log("Failed to record refund: " . $e->getMessage());
                
                // Still update payment status
                $payment_status = 'refunded';
                $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
            }
        }
        
        // Log admin action
        logAdminAction('cancel_booking', $booking_id, "Cancelled booking with reason: $reason");
        
        // Commit transaction
        $conn->commit();
        
        // Notify customer if requested
        if ($notify_customer && isset($booking['email'])) {
            sendBookingCancellationEmail($booking, $reason, $refund_amount);
        }
        
        $_SESSION['booking_status'] = [
            'type' => 'success',
            'message' => 'Booking cancelled successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Special handling for refunds table not existing error
        if (strpos($e->getMessage(), "refunds' doesn't exist") !== false) {
            // Create refunds table
            try {
                $conn->query("CREATE TABLE IF NOT EXISTS `refunds` (
                  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
                  `booking_id` int(11) NOT NULL,
                  `amount` decimal(10,2) NOT NULL,
                  `reason` text DEFAULT NULL,
                  `processed_by` int(11) NOT NULL,
                  `created_at` datetime NOT NULL,
                  PRIMARY KEY (`refund_id`),
                  KEY `booking_id` (`booking_id`),
                  KEY `processed_by` (`processed_by`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                
                // Try the operation again
                $_SESSION['booking_status'] = [
                    'type' => 'info',
                    'message' => 'Refunds table was created. Please try cancelling the booking again.'
                ];
            } catch (Exception $createEx) {
                $_SESSION['booking_status'] = [
                    'type' => 'danger',
                    'message' => 'Error cancelling booking. Could not create refunds table: ' . $createEx->getMessage()
                ];
            }
        } else {
            $_SESSION['booking_status'] = [
                'type' => 'danger',
                'message' => 'Error cancelling booking: ' . $e->getMessage()
            ];
        }
        
        header("Location: manage_bookings.php");
        exit();
    }
    
    header("Location: manage_bookings.php");
    exit();
}

// Handle delete booking action
elseif ($action === 'delete_booking') {
    $booking_id = $_POST['booking_id'] ?? 0;
    
    // Verify booking exists
    $stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Booking not found'
        ];
        header("Location: manage_bookings.php");
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete related tickets
        $stmt = $conn->prepare("DELETE FROM tickets WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Delete related payments
        $stmt = $conn->prepare("DELETE FROM payments WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Try to delete refunds if table exists
        try {
            $stmt = $conn->prepare("DELETE FROM refunds WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        } catch (Exception $e) {
            // Ignore if table doesn't exist
        }
        
        // Delete the booking
        $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Log admin action
        logAdminAction('delete_booking', $booking_id, "Permanently deleted booking");
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['booking_status'] = [
            'type' => 'success',
            'message' => 'Booking permanently deleted'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Error deleting booking: ' . $e->getMessage()
        ];
    }
    
    header("Location: manage_bookings.php");
    exit();
}

// Handle process refund action
elseif ($action === 'process_refund') {
    $booking_id = $_POST['booking_id'] ?? 0;
    $refund_type = $_POST['refund_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $notify_customer = isset($_POST['notify_customer']) && $_POST['notify_customer'] == 1;
    
    // Validate required fields
    if (empty($booking_id) || empty($refund_type) || empty($reason)) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Required fields missing'
        ];
        header("Location: manage_bookings.php");
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get booking details
        $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                                f.departure_time, u.email, u.first_name, u.last_name 
                                FROM bookings b 
                                JOIN flights f ON b.flight_id = f.flight_id 
                                JOIN users u ON b.user_id = u.user_id 
                                WHERE b.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        // Determine refund amount based on type
        $refund_amount = 0;
        if ($refund_type === 'full') {
            $refund_amount = $booking['total_amount'];
        } else {
            $refund_amount = floatval($amount);
        }
        
        // Insert refund record if table exists
        try {
            $stmt = $conn->prepare("INSERT INTO refunds (booking_id, amount, reason, processed_by, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
            $admin_id = $_SESSION['user_id'];
            $stmt->bind_param("idsi", $booking_id, $refund_amount, $reason, $admin_id);
            $stmt->execute();
        } catch (Exception $e) {
            // Ignore if table doesn't exist, we'll update the booking record anyway
        }
        
        // Update booking payment status
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'refunded', 
                               admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?) 
                               WHERE booking_id = ?");
        $refund_note = "Refund processed: $refund_amount, Reason: $reason";
        $stmt->bind_param("si", $refund_note, $booking_id);
        $stmt->execute();
        
        // Log admin action
        logAdminAction('process_refund', $booking_id, 
                      "Processed refund of $refund_amount for booking. Reason: $reason");
        
        // Commit transaction
        $conn->commit();
        
        // Notify customer if requested
        if ($notify_customer && isset($booking['email'])) {
            sendRefundEmail($booking, $refund_amount, $reason);
        }
        
        $_SESSION['booking_status'] = [
            'type' => 'success',
            'message' => 'Refund processed successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Error processing refund: ' . $e->getMessage()
        ];
    }
    
    header("Location: manage_bookings.php");
    exit();
}

// Unknown action
else {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Unknown action specified'
    ];
    header("Location: manage_bookings.php");
    exit();
}

// Helper Functions

// Function to log admin actions
function logAdminAction($action, $entity_id, $details) {
    global $conn;
    
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check if admin_logs table exists, if not silently continue
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
        if ($table_check->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, entity_id, details, ip_address, created_at) 
                                  VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isiss", $admin_id, $action, $entity_id, $details, $ip_address);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Silently ignore if admin_logs table doesn't exist
    }
}

// Function to send booking status update email
function sendBookingStatusEmail($booking, $booking_status, $payment_status, $notes) {
    // In a production environment, you would use a proper email library
    // For this example, we'll just simulate the email sending
    
    $to = $booking['email'];
    $subject = "SkyWay Airlines - Booking Status Update";
    
    $message = "Dear " . $booking['first_name'] . " " . $booking['last_name'] . ",\n\n";
    $message .= "Your booking (ID: " . $booking['booking_id'] . ") has been updated:\n\n";
    $message .= "Flight: " . $booking['flight_number'] . "\n";
    $message .= "Route: " . $booking['departure_city'] . " to " . $booking['arrival_city'] . "\n";
    $message .= "Departure: " . date('F j, Y, g:i a', strtotime($booking['departure_time'])) . "\n\n";
    $message .= "Booking Status: " . ucfirst($booking_status) . "\n";
    $message .= "Payment Status: " . ucfirst($payment_status) . "\n\n";
    
    if (!empty($notes)) {
        $message .= "Additional Notes:\n" . $notes . "\n\n";
    }
    
    $message .= "You can view your booking details by logging into your account on our website.\n\n";
    $message .= "Thank you for choosing SkyWay Airlines.\n";
    $message .= "SkyWay Airlines Customer Service";
    
    $headers = "From: noreply@skywayairlines.com\r\n";
    
    // Comment out actual mail sending for demo purposes
    // mail($to, $subject, $message, $headers);
    
    // Log email content for debugging
    error_log("Email would be sent to $to with subject: $subject and message: $message");
}

// Function to send booking cancellation email
function sendBookingCancellationEmail($booking, $reason, $refund_amount) {
    $to = $booking['email'];
    $subject = "SkyWay Airlines - Booking Cancellation";
    
    $message = "Dear " . $booking['first_name'] . " " . $booking['last_name'] . ",\n\n";
    $message .= "Your booking (ID: " . $booking['booking_id'] . ") has been cancelled.\n\n";
    $message .= "Flight: " . $booking['flight_number'] . "\n";
    $message .= "Route: " . $booking['departure_city'] . " to " . $booking['arrival_city'] . "\n";
    $message .= "Departure: " . date('F j, Y, g:i a', strtotime($booking['departure_time'])) . "\n\n";
    
    if ($reason) {
        $message .= "Reason for cancellation: " . $reason . "\n\n";
    }
    
    if ($refund_amount > 0) {
        // Use the global currency symbol instead of hardcoded currency
        global $conn;
        $currency_symbol = getCurrencySymbol($conn);
        $message .= "A refund of " . $currency_symbol . number_format($refund_amount, 2) . " has been processed and will be credited to your original payment method within 5-10 business days.\n\n";
    }
    
    $message .= "If you have any questions regarding this cancellation, please contact our customer service.\n\n";
    $message .= "Thank you for choosing SkyWay Airlines.\n";
    $message .= "SkyWay Airlines Customer Service";
    
    $headers = "From: noreply@skywayairlines.com\r\n";
    
    // Comment out actual mail sending for demo purposes
    // mail($to, $subject, $message, $headers);
    
    // Log email content for debugging
    error_log("Email would be sent to $to with subject: $subject and message: $message");
}

// Function to send refund email
function sendRefundEmail($booking, $refund_amount, $reason) {
    $to = $booking['email'];
    $subject = "SkyWay Airlines - Refund Processed";
    
    $message = "Dear " . $booking['first_name'] . " " . $booking['last_name'] . ",\n\n";
    $message .= "A refund has been processed for your booking (ID: " . $booking['booking_id'] . ").\n\n";
    $message .= "Flight: " . $booking['flight_number'] . "\n";
    $message .= "Route: " . $booking['departure_city'] . " to " . $booking['arrival_city'] . "\n";
    
    // Use the global currency symbol instead of hardcoded currency
    global $conn;
    $currency_symbol = getCurrencySymbol($conn);
    $message .= "Refund Amount: " . $currency_symbol . number_format($refund_amount, 2) . "\n\n";
    
    if ($reason) {
        $message .= "Reason for refund: " . $reason . "\n\n";
    }
    
    $message .= "The refund will be credited to your original payment method within 5-10 business days.\n\n";
    $message .= "If you have any questions about this refund, please contact our customer service.\n\n";
    $message .= "Thank you for choosing SkyWay Airlines.\n";
    $message .= "SkyWay Airlines Customer Service";
    
    $headers = "From: noreply@skywayairlines.com\r\n";
    
    // Comment out actual mail sending for demo purposes
    // mail($to, $subject, $message, $headers);
    
    // Log email content for debugging
    error_log("Email would be sent to $to with subject: $subject and message: $message");
}
?>
