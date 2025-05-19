<?php
/**
 * Settings Helper Functions
 * 
 * This file contains functions for working with system settings.
 */

/**
 * Get setting value by key
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed The setting value
 */
function get_setting($key, $default = '') {
    global $conn;
    
    // If no connection is available, return default
    if (!isset($conn) || !$conn) {
        return $default;
    }
    
    // Check if settings table exists
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($table_check->num_rows == 0) {
            return $default;
        }
    } catch (Exception $e) {
        return $default;
    }
    
    // Try to get the setting
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['setting_value'];
        }
    } catch (Exception $e) {
        // In case of error, return default
    }
    
    return $default;
}

/**
 * Update or create a setting
 * 
 * @param string $key The setting key
 * @param string $value The setting value
 * @param string $group The setting group (optional)
 * @return boolean True on success, false on failure
 */
function update_setting($key, $value, $group = 'general') {
    global $conn;
    
    // If no connection is available, return false
    if (!isset($conn) || !$conn) {
        return false;
    }
    
    // Check if settings table exists, create if it doesn't
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($table_check->num_rows == 0) {
            // Create the settings table
            $create_table_sql = "CREATE TABLE settings (
                setting_id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_group VARCHAR(50) NOT NULL,
                setting_type VARCHAR(50) NOT NULL DEFAULT 'text',
                setting_label VARCHAR(100) NOT NULL,
                setting_description TEXT,
                is_public TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($create_table_sql);
        }
    } catch (Exception $e) {
        return false;
    }
    
    // Try to update or insert the setting
    try {
        // Check if setting exists
        $stmt = $conn->prepare("SELECT setting_id FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing setting
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            return $stmt->execute();
        } else {
            // Insert new setting
            $label = ucwords(str_replace('_', ' ', $key));
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group, setting_label) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $key, $value, $group, $label);
            return $stmt->execute();
        }
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a setting
 * 
 * @param string $key The setting key
 * @return boolean True on success, false on failure
 */
function delete_setting($key) {
    global $conn;
    
    // If no connection is available, return false
    if (!isset($conn) || !$conn) {
        return false;
    }
    
    // Try to delete the setting
    try {
        $stmt = $conn->prepare("DELETE FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all settings in a specific group
 * 
 * @param string $group The setting group
 * @return array Array of settings
 */
function get_settings_group($group) {
    global $conn;
    $settings = [];
    
    // If no connection is available, return empty array
    if (!isset($conn) || !$conn) {
        return $settings;
    }
    
    // Check if settings table exists
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($table_check->num_rows == 0) {
            return $settings;
        }
    } catch (Exception $e) {
        return $settings;
    }
    
    // Try to get settings in the group
    try {
        $stmt = $conn->prepare("SELECT * FROM settings WHERE setting_group = ? ORDER BY setting_id ASC");
        $stmt->bind_param("s", $group);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $settings[] = $row;
        }
    } catch (Exception $e) {
        // In case of error, return empty array
    }
    
    return $settings;
}

/**
 * Import settings from a CSV file
 * 
 * @param string $file_path Path to the CSV file
 * @return array Result with success status and message
 */
function import_settings($file_path) {
    global $conn;
    $result = ['success' => false, 'message' => 'Unknown error'];
    
    // If no connection is available, return error
    if (!isset($conn) || !$conn) {
        $result['message'] = 'Database connection not available';
        return $result;
    }
    
    // Check if file exists and is readable
    if (!file_exists($file_path) || !is_readable($file_path)) {
        $result['message'] = 'File not found or not readable';
        return $result;
    }
    
    // Start a transaction
    $conn->begin_transaction();
    
    try {
        // Open the CSV file
        $file = fopen($file_path, 'r');
        
        // Skip the header row
        fgetcsv($file);
        
        // Process each row
        $count = 0;
        while (($data = fgetcsv($file)) !== false) {
            // Check if we have at least the required columns
            if (count($data) >= 6) {
                $key = $data[0];
                $value = $data[1];
                $group = $data[2];
                $type = $data[3];
                $label = $data[4];
                $description = $data[5];
                
                // Skip sensitive data that was masked during export
                if ($value === '********') {
                    continue;
                }
                
                // Check if setting exists
                $stmt = $conn->prepare("SELECT setting_id FROM settings WHERE setting_key = ?");
                $stmt->bind_param("s", $key);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    // Update existing setting
                    $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, setting_group = ?, setting_type = ?, setting_label = ?, setting_description = ? WHERE setting_key = ?");
                    $stmt->bind_param("ssssss", $value, $group, $type, $label, $description, $key);
                } else {
                    // Insert new setting
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group, setting_type, setting_label, setting_description) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $key, $value, $group, $type, $label, $description);
                }
                
                $stmt->execute();
                $count++;
            }
        }
        
        // Close the file
        fclose($file);
        
        // Commit the transaction
        $conn->commit();
        
        $result['success'] = true;
        $result['message'] = "Successfully imported $count settings";
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        $result['message'] = 'Error importing settings: ' . $e->getMessage();
    }
    
    return $result;
}
