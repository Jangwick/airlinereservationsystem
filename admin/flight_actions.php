<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Initialize response
$response = ['success' => false, 'message' => 'Invalid action'];

// Check if action is set
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Update flight status
    if ($action === 'update_status' && isset($_POST['flight_id']) && isset($_POST['status'])) {
        $flight_id = $_POST['flight_id'];
        $status = $_POST['status'];
        
        // Update flight status in database (simplified example)
        $stmt = $conn->prepare("UPDATE flights SET status = ? WHERE flight_id = ?");
        $stmt->bind_param("si", $status, $flight_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Flight status updated successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to update flight status: ' . $conn->error
            ];
        }
    }
    
    // Delete flight
    else if ($action === 'delete_flight' && isset($_POST['flight_id'])) {
        $flight_id = $_POST['flight_id'];
        
        // Delete flight from database (simplified example)
        $stmt = $conn->prepare("DELETE FROM flights WHERE flight_id = ?");
        $stmt->bind_param("i", $flight_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Flight deleted successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to delete flight: ' . $conn->error
            ];
        }
    }
    
    // For any action that updates flight prices
    // For example, in a bulk update or price adjustment feature:
    else if ($action === 'update_price' && isset($_POST['flight_id']) && isset($_POST['price'])) {
        $flight_id = $_POST['flight_id'];
        
        // Validate and sanitize price input
        $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
        if ($price === null || $price <= 0) {
            // Get the current price from database if available
            $stmt = $conn->prepare("SELECT price FROM flights WHERE flight_id = ?");
            $stmt->bind_param("i", $flight_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $current = $result->fetch_assoc();
                $price = floatval($current['price']);
            } else {
                $price = 100.00; // Default value if we can't get current price
            }
            
            $_SESSION['flight_status'] = [
                'type' => 'warning',
                'message' => 'Invalid price provided. Current or default price has been used.'
            ];
        }
        
        // Now use $price in your update query
        $stmt = $conn->prepare("UPDATE flights SET price = ? WHERE flight_id = ?");
        $stmt->bind_param("di", $price, $flight_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Flight price updated successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to update flight price: ' . $conn->error
            ];
        }
    }
}

// Return response as JSON
echo json_encode($response);
?>
