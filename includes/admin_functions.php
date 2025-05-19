<?php
/**
 * Admin Helper Functions
 * 
 * This file contains functions specific to admin functionality
 */

/**
 * Log an admin action
 * 
 * @param string $action The action performed (e.g. 'add_user', 'edit_flight')
 * @param int $entity_id ID of the affected entity (optional)
 * @param string $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logAdminAction($action, $entity_id = null, $details = '') {
    global $conn;
    
    // If not logged in as admin or no connection, return false
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($conn) || !$conn) {
        return false;
    }
    
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check if admin_logs table exists, create if it doesn't
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
        if ($table_check->num_rows == 0) {
            $create_table_sql = "CREATE TABLE admin_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                entity_id INT,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (admin_id),
                INDEX (action),
                INDEX (created_at)
            )";
            $conn->query($create_table_sql);
        }
        
        // Insert log entry
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $admin_id, $action, $entity_id, $details, $ip_address);
        return $stmt->execute();
    } catch (Exception $e) {
        // Silently fail - logging should not interrupt normal flow
        return false;
    }
}

/**
 * Get action description for display
 * 
 * @param string $action Raw action name from database
 * @return string Formatted action name for display
 */
function getActionDescription($action) {
    $actions = [
        'add_user' => 'Added User',
        'edit_user' => 'Updated User',
        'delete_user' => 'Deleted User',
        'change_user_status' => 'Changed User Status',
        'add_flight' => 'Added Flight',
        'edit_flight' => 'Updated Flight',
        'delete_flight' => 'Deleted Flight',
        'update_flight_status' => 'Updated Flight Status',
        'add_booking' => 'Created Booking',
        'cancel_booking' => 'Cancelled Booking',
        'update_booking' => 'Updated Booking',
        'update_settings' => 'Updated System Settings',
        'clear_cache' => 'Cleared System Cache',
        'system_init' => 'System Initialization',
        'login' => 'Admin Login',
        'logout' => 'Admin Logout',
        'failed_login' => 'Failed Login Attempt'
    ];
    
    return isset($actions[$action]) ? $actions[$action] : ucwords(str_replace('_', ' ', $action));
}

/**
 * Get admin summary statistics
 * 
 * @param int $admin_id ID of admin user (optional)
 * @param string $timeframe Timeframe - 'today', 'week', 'month', 'all' (default)
 * @return array Array of statistics
 */
function getAdminStats($admin_id = null, $timeframe = 'all') {
    global $conn;
    $stats = [
        'total_actions' => 0,
        'most_common_action' => 'None',
        'last_activity' => 'Never',
        'actions_by_type' => []
    ];
    
    if (!isset($conn) || !$conn) {
        return $stats;
    }
    
    // Check if admin_logs table exists
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
        if ($table_check->num_rows == 0) {
            return $stats;
        }
        
        // Build the timeframe condition
        $time_condition = "";
        switch ($timeframe) {
            case 'today':
                $time_condition = "AND DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $time_condition = "AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $time_condition = "AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
                break;
            default:
                $time_condition = "";
        }
        
        // Build the admin condition
        $admin_condition = "";
        $params = [];
        $types = "";
        
        if ($admin_id !== null) {
            $admin_condition = "AND admin_id = ?";
            $params[] = $admin_id;
            $types .= "i";
        }
        
        // Get total actions
        $query = "SELECT COUNT(*) as count FROM admin_logs WHERE 1=1 $admin_condition $time_condition";
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_actions'] = $result->fetch_assoc()['count'];
        
        // Get most common action
        if ($stats['total_actions'] > 0) {
            $query = "SELECT action, COUNT(*) as count FROM admin_logs 
                     WHERE 1=1 $admin_condition $time_condition 
                     GROUP BY action 
                     ORDER BY count DESC 
                     LIMIT 1";
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $action = $result->fetch_assoc()['action'];
                $stats['most_common_action'] = getActionDescription($action);
            }
            
            // Get last activity
            $query = "SELECT created_at FROM admin_logs 
                     WHERE 1=1 $admin_condition 
                     ORDER BY created_at DESC 
                     LIMIT 1";
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stats['last_activity'] = $result->fetch_assoc()['created_at'];
            }
            
            // Get actions by type
            $query = "SELECT action, COUNT(*) as count FROM admin_logs 
                     WHERE 1=1 $admin_condition $time_condition 
                     GROUP BY action 
                     ORDER BY count DESC";
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $stats['actions_by_type'][getActionDescription($row['action'])] = $row['count'];
            }
        }
        
        return $stats;
    } catch (Exception $e) {
        return $stats;
    }
}
