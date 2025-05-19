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
}

// Return response as JSON
echo json_encode($response);
?>
