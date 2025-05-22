<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Debug mode - Log all requests
$debug = true;
if ($debug) {
    error_log("flight_actions.php - Processing request: " . print_r($_POST, true));
}

// Check if action is set
if (!isset($_POST['action'])) {
    $_SESSION['flight_status'] = [
        'type' => 'danger',
        'message' => 'No action specified'
    ];
    header("Location: manage_flights.php");
    exit();
}

$action = $_POST['action'];

// Handle update status action
if ($action === 'update_status') {
    // Get form data
    $flight_id = isset($_POST['flight_id']) ? intval($_POST['flight_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $notify_passengers = isset($_POST['notify_passengers']) && $_POST['notify_passengers'] == 1;
    $from_details = isset($_POST['from_details']) && $_POST['from_details'] == 1;
    
    // Debug info
    if ($debug) {
        error_log("Processing update_status for flight #$flight_id to status '$status'");
    }
    
    // Validate required fields
    if (empty($flight_id) || empty($status)) {
        $_SESSION['flight_status'] = [
            'type' => 'danger',
            'message' => 'Required fields missing'
        ];
        header("Location: " . ($from_details ? "flight_details.php?id=$flight_id" : "manage_flights.php"));
        exit();
    }
    
    // Validate status value
    $valid_statuses = ['scheduled', 'delayed', 'boarding', 'departed', 'arrived', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['flight_status'] = [
            'type' => 'danger',
            'message' => 'Invalid status value: ' . htmlspecialchars($status)
        ];
        header("Location: " . ($from_details ? "flight_details.php?id=$flight_id" : "manage_flights.php"));
        exit();
    }
    
    // Make sure reason is provided for delayed/cancelled
    if (($status === 'delayed' || $status === 'cancelled') && empty($reason)) {
        $_SESSION['flight_status'] = [
            'type' => 'danger',
            'message' => 'Reason is required for delayed or cancelled status'
        ];
        header("Location: " . ($from_details ? "flight_details.php?id=$flight_id" : "manage_flights.php"));
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get current flight data
        $stmt = $conn->prepare("SELECT status, flight_number FROM flights WHERE flight_id = ?");
        $stmt->bind_param("i", $flight_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_flight = $result->fetch_assoc();
        
        if (!$current_flight) {
            throw new Exception("Flight not found");
        }
        
        $old_status = $current_flight['status'];
        $flight_number = $current_flight['flight_number'];
        
        // Check if delay_reason column exists
        $has_delay_reason = $conn->query("SHOW COLUMNS FROM flights LIKE 'delay_reason'")->num_rows > 0;
        
        if ($has_delay_reason) {
            // Update with delay reason
            $stmt = $conn->prepare("UPDATE flights SET status = ?, delay_reason = ? WHERE flight_id = ?");
            $stmt->bind_param("ssi", $status, $reason, $flight_id);
        } else {
            // Update without delay reason
            $stmt = $conn->prepare("UPDATE flights SET status = ? WHERE flight_id = ?");
            $stmt->bind_param("si", $status, $flight_id);
        }
        
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        
        if ($debug) {
            error_log("Status update affected rows: $affected_rows");
        }
        
        // Handle specific status-related actions
        if ($status === 'cancelled') {
            // Update related bookings
            $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' 
                                   WHERE flight_id = ? AND booking_status != 'cancelled'");
            $stmt->bind_param("i", $flight_id);
            $stmt->execute();
            $cancelled_bookings = $stmt->affected_rows;
            
            if ($debug) {
                error_log("Cancelled $cancelled_bookings related bookings");
            }
        }
        
        // Log admin action
        $admin_id = $_SESSION['user_id'];
        $log_details = "Changed flight #$flight_number status from '$old_status' to '$status'" . 
                      ($reason ? " with reason: $reason" : "");
        
        // Check if admin_logs table exists
        if ($conn->query("SHOW TABLES LIKE 'admin_logs'")->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, entity_id, details, ip_address, created_at) 
                                  VALUES (?, 'update_flight_status', ?, ?, ?, NOW())");
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("iiss", $admin_id, $flight_id, $log_details, $ip_address);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['flight_status'] = [
            'type' => 'success',
            'message' => "Flight status updated to " . ucfirst($status) . " successfully"
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        if ($debug) {
            error_log("Error updating flight status: " . $e->getMessage());
        }
        
        $_SESSION['flight_status'] = [
            'type' => 'danger',
            'message' => 'Error updating flight status: ' . $e->getMessage()
        ];
    }
    
    // Redirect to appropriate page
    header("Location: " . ($from_details ? "flight_details.php?id=$flight_id" : "manage_flights.php"));
    exit();
}

// Handle cancel flight action
elseif ($action === 'cancel_flight') {
    // ...existing code...
    // No changes needed for this action, but keep existing implementation
}

// If we reach here, it's an unknown action
$_SESSION['flight_status'] = [
    'type' => 'danger',
    'message' => 'Unknown action specified'
];
header("Location: manage_flights.php");
exit();
?>
