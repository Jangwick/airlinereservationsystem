<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Process form data
$user_id = $_SESSION['user_id'];
$booking_ref = isset($_POST['booking_ref']) ? $_POST['booking_ref'] : '';
$subject = isset($_POST['subject']) ? $_POST['subject'] : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';
$phone = isset($_POST['phone']) ? $_POST['phone'] : '';

// Validate required fields
if (empty($subject) || empty($message)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
    exit();
}

// Check if support_requests table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'support_requests'");
if ($table_check->num_rows == 0) {
    $create_table = "CREATE TABLE support_requests (
        request_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        booking_ref VARCHAR(20),
        subject VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        phone VARCHAR(20),
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    
    if (!$conn->query($create_table)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating support table']);
        exit();
    }
}

// Insert the support request
$stmt = $conn->prepare("INSERT INTO support_requests (user_id, booking_ref, subject, message, phone) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $booking_ref, $subject, $message, $phone);

if ($stmt->execute()) {
    $request_id = $conn->insert_id;
    
    // Try to send email to admin (in a real application)
    // mail('support@skywayairlines.com', 'New Support Request: ' . $subject, $message);
    
    // Create notification for admin if applicable
    $check_notifications = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check_notifications->num_rows > 0) {
        // Get admin user
        $admin_query = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
        $admin_result = $conn->query($admin_query);
        
        if ($admin_result->num_rows > 0) {
            $admin_id = $admin_result->fetch_assoc()['user_id'];
            
            // Create notification
            $note_message = "New support request received for booking {$booking_ref}: {$subject}";
            $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                                  VALUES (?, 'New Support Request', ?, 'support')";
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("is", $admin_id, $note_message);
            $stmt->execute();
        }
    }
    
    // Return success
    echo json_encode([
        'success' => true, 
        'message' => 'Support request submitted successfully',
        'request_id' => $request_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit support request']);
}
?>
