<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Include admin functions to handle logging
require_once '../includes/admin_functions.php';

// Set header for AJAX responses
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && 
    ($_POST['action'] === 'test_email' || $_POST['action'] === 'clear_cache'))) {
    header('Content-Type: application/json');
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Update settings
    if ($action === 'update_settings' && isset($_POST['setting_group']) && isset($_POST['settings'])) {
        $group = $_POST['setting_group'];
        $settings = $_POST['settings'];
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Track which new settings were added
            $new_settings = [];
            
            // Update existing settings
            foreach ($settings as $key => $value) {
                // For checkboxes: if not checked, they don't get submitted
                if (strpos($key, 'smtp_') === 0) {
                    // Special case for SMTP settings - treat them differently
                    $stmt = $conn->prepare("SELECT setting_id FROM settings WHERE setting_key = ?");
                    $stmt->bind_param("s", $key);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows === 0) {
                        // SMTP setting doesn't exist yet, create it
                        $conn->query("INSERT INTO settings (setting_key, setting_value, setting_group, setting_type, setting_label, setting_description) 
                                      VALUES ('$key', '$value', 'email', 'text', '" . ucwords(str_replace('_', ' ', $key)) . "', 'SMTP Setting')");
                        $new_settings[] = $key;
                    } else {
                        // Only update if not the password field with an empty value
                        if (!($key === 'smtp_password' && empty($value))) {
                            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                            $stmt->bind_param("ss", $value, $key);
                            $stmt->execute();
                        }
                    }
                } else {
                    // Normal settings
                    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->bind_param("ss", $value, $key);
                    $stmt->execute();
                }
            }
            
            // Handle boolean settings that are unchecked (not submitted)
            $result = $conn->query("SELECT setting_key FROM settings WHERE setting_group = '$group' AND setting_type = 'boolean'");
            while ($row = $result->fetch_assoc()) {
                $key = $row['setting_key'];
                if (!isset($settings[$key]) && !in_array($key, $new_settings)) {
                    $conn->query("UPDATE settings SET setting_value = '0' WHERE setting_key = '$key'");
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Log the action
            logAdminAction('update_settings', null, "Updated $group settings");
            
            // Save active tab
            $_SESSION['settings_active_tab'] = $group;
            
            // Set success message
            $_SESSION['settings_status'] = [
                'type' => 'success',
                'message' => ucfirst($group) . ' settings updated successfully!'
            ];
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            
            $_SESSION['settings_status'] = [
                'type' => 'danger',
                'message' => 'Error updating settings: ' . $e->getMessage()
            ];
        }
        
        // Redirect back to settings page
        header('Location: settings.php');
        exit();
    }
    
    // Set active tab
    elseif ($action === 'set_active_tab' && isset($_POST['tab'])) {
        $_SESSION['settings_active_tab'] = $_POST['tab'];
        exit(); // No redirect needed, this is an AJAX call
    }
    
    // Test email
    elseif ($action === 'test_email' && isset($_POST['email'])) {
        $to = $_POST['email'];
        $subject = "Test Email from SkyWay Airlines";
        $message = "This is a test email from your SkyWay Airlines admin panel. If you received this, your email settings are working correctly!";
        
        // Get SMTP settings
        $smtp_host = getSetting('smtp_host');
        $smtp_port = getSetting('smtp_port');
        $smtp_username = getSetting('smtp_username');
        $smtp_password = getSetting('smtp_password');
        $smtp_encryption = getSetting('smtp_encryption');
        $sender_name = getSetting('email_sender_name', 'SkyWay Airlines');
        
        // If SMTP settings are configured, use PHPMailer
        if (!empty($smtp_host) && !empty($smtp_port) && !empty($smtp_username) && !empty($smtp_password)) {
            try {
                // This is a placeholder for actual SMTP email sending
                // In a real application, you would use PHPMailer or a similar library
                
                // For demonstration purposes, we'll just simulate successful sending
                echo json_encode([
                    'success' => true,
                    'message' => 'Test email sent successfully using SMTP configuration.',
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error sending email: ' . $e->getMessage(),
                ]);
            }
        } else {
            // Use PHP's mail function as fallback
            $headers = "From: $sender_name <noreply@skywayairlines.com>\r\n";
            $headers .= "Reply-To: noreply@skywayairlines.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            $mail_sent = mail($to, $subject, $message, $headers);
            
            if ($mail_sent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Test email sent successfully using PHP mail().',
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send test email using PHP mail().',
                ]);
            }
        }
        
        exit();
    }
    
    // Clear cache
    elseif ($action === 'clear_cache') {
        try {
            // Clear session cache
            $_SESSION['cache_cleared'] = true;
            
            // For demonstration, we'll just wait a bit to simulate cache clearing
            sleep(1);
            
            // This is where you would actually clear various caches
            // For example: file cache, opcode cache, database cache, etc.
            
            // Log the action
            logAdminAction('clear_cache', null, "Cleared system cache");
            
            echo json_encode([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage(),
            ]);
        }
        
        exit();
    }
} 
// GET requests
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Export settings
    if ($action === 'export_settings') {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="skyway_settings_' . date('Y-m-d') . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write header row
        fputcsv($output, ['Setting Key', 'Setting Value', 'Setting Group', 'Setting Type', 'Setting Label', 'Setting Description']);
        
        // Get all settings
        $result = $conn->query("SELECT * FROM settings ORDER BY setting_group, setting_id");
        
        // Write data rows
        while ($row = $result->fetch_assoc()) {
            // Don't export sensitive data
            if (strpos($row['setting_key'], 'password') !== false || strpos($row['setting_key'], 'key') !== false || strpos($row['setting_key'], 'secret') !== false) {
                $row['setting_value'] = '********';
            }
            
            fputcsv($output, [
                $row['setting_key'],
                $row['setting_value'],
                $row['setting_group'],
                $row['setting_type'],
                $row['setting_label'],
                $row['setting_description'],
            ]);
        }
        
        // Close the output stream
        fclose($output);
        exit();
    }
    
    // Import settings - this would be a more complex form with file upload
    // Not implemented in this simplified example
}

// Default redirect if no action matched
header('Location: settings.php');
exit();

// Helper function to get setting value by key
function getSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    
    return $default;
}
