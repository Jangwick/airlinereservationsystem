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

// Check if account_status column exists
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
$account_status_exists = ($column_check->num_rows > 0);

// Ensure we have an action specified
if (!isset($_POST['action'])) {
    $_SESSION['user_status'] = [
        'type' => 'danger',
        'message' => 'No action specified'
    ];
    header("Location: manage_users.php");
    exit();
}

$action = $_POST['action'];

// Handle add user
if ($action === 'add') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'All required fields must be filled out'
        ];
        header("Location: add_user.php");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Passwords do not match'
        ];
        header("Location: add_user.php");
        exit();
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Username or email already exists'
        ];
        header("Location: add_user.php");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into database - with or without account_status
    try {
        if ($account_status_exists) {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, account_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssss", $username, $email, $hashed_password, $first_name, $last_name, $role, $status);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $username, $email, $hashed_password, $first_name, $last_name, $role);
        }
        
        if ($stmt->execute()) {
            $_SESSION['user_status'] = [
                'type' => 'success',
                'message' => 'User added successfully'
            ];
            
            // Log admin action
            $new_user_id = $conn->insert_id;
            logAdminAction('add_user', $new_user_id, "Added new user: $username");
            
            header("Location: manage_users.php");
            exit();
        }
        
        throw new Exception($conn->error);
    } catch (Exception $e) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Error adding user: ' . $e->getMessage()
        ];
        header("Location: add_user.php");
        exit();
    }
}

// Handle edit user
elseif ($action === 'edit') {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'All required fields must be filled out'
        ];
        header("Location: edit_user.php?id=$user_id");
        exit();
    }
    
    // Check if username or email already exists for another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Username or email already exists for another user'
        ];
        header("Location: edit_user.php?id=$user_id");
        exit();
    }
    
    try {
        // Prepare update statement based on whether we have a new password and account_status column
        if (!empty($new_password) && $account_status_exists) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, password = ?, role = ?, account_status = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $username, $email, $first_name, $last_name, $hashed_password, $role, $status, $user_id);
        } elseif (!empty($new_password) && !$account_status_exists) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, password = ?, role = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $username, $email, $first_name, $last_name, $hashed_password, $role, $user_id);
        } elseif (empty($new_password) && $account_status_exists) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, account_status = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $username, $email, $first_name, $last_name, $role, $status, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $username, $email, $first_name, $last_name, $role, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['user_status'] = [
                'type' => 'success',
                'message' => 'User updated successfully'
            ];
            
            // Log admin action
            logAdminAction('edit_user', $user_id, "Updated user: $username");
            
            header("Location: manage_users.php");
            exit();
        }
        
        throw new Exception($conn->error);
    } catch (Exception $e) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Error updating user: ' . $e->getMessage()
        ];
        header("Location: edit_user.php?id=$user_id");
        exit();
    }
}

// Handle delete user
elseif ($action === 'delete') {
    $user_id = $_POST['user_id'];
    
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'You cannot delete your own account'
        ];
        header("Location: manage_users.php");
        exit();
    }
    
    // Get user info for logging
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if (!$user) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'User not found'
        ];
        header("Location: manage_users.php");
        exit();
    }
    
    try {
        // Start transaction for safe deletion
        $conn->begin_transaction();
        
        // Check if bookings table exists and delete user bookings if it does
        $stmt = $conn->prepare("SHOW TABLES LIKE 'bookings'");
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows <= 0) {
            throw new Exception("Failed to delete user");
        }
        
        // Commit the transaction
        $conn->commit();
        
        $_SESSION['user_status'] = [
            'type' => 'success',
            'message' => 'User deleted successfully'
        ];
        
        // Log admin action
        logAdminAction('delete_user', $user_id, "Deleted user: " . $user['username']);
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Error deleting user: ' . $e->getMessage()
        ];
    }
    
    header("Location: manage_users.php");
    exit();
}

// Handle change user status (only if account_status exists)
elseif ($action === 'change_status' && $account_status_exists) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $notify_user = isset($_POST['notify_user']) && $_POST['notify_user'] == '1';
    
    // Prevent changing your own account to suspended
    if ($user_id == $_SESSION['user_id'] && $status == 'suspended') {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'You cannot suspend your own account'
        ];
        header("Location: manage_users.php");
        exit();
    }
    
    try {
        // Get user info for notification
        $stmt = $conn->prepare("SELECT username, email, first_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user = $user_result->fetch_assoc();
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $status, $user_id);
        
        if ($stmt->execute()) {
            // Notify user by email if selected
            if ($notify_user && !empty($user['email'])) {
                $subject = "SkyWay Airlines - Account Status Change";
                $message = "Dear " . $user['first_name'] . ",\n\n";
                
                switch ($status) {
                    case 'active':
                        $message .= "Your SkyWay Airlines account has been activated.\n";
                        break;
                    case 'suspended':
                        $message .= "Your SkyWay Airlines account has been suspended.\n";
                        break;
                    case 'inactive':
                        $message .= "Your SkyWay Airlines account has been marked as inactive.\n";
                        break;
                }
                
                if (!empty($reason)) {
                    $message .= "\nReason: " . $reason . "\n";
                }
                
                $message .= "\nIf you have any questions, please contact our customer support.\n\n";
                $message .= "Best Regards,\nSkyWay Airlines Team";
                
                // For demonstration purposes - in production, uncomment this line:
                // mail($user['email'], $subject, $message);
            }
            
            $_SESSION['user_status'] = [
                'type' => 'success',
                'message' => 'User status updated successfully'
            ];
            
            // Log admin action
            logAdminAction('change_user_status', $user_id, "Changed user status: " . $user['username'] . " to " . $status);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['user_status'] = [
            'type' => 'danger',
            'message' => 'Error changing user status: ' . $e->getMessage()
        ];
    }
    
    header("Location: manage_users.php");
    exit();
}

// Unknown or invalid action
else {
    $_SESSION['user_status'] = [
        'type' => 'danger',
        'message' => 'Unknown or invalid action'
    ];
    header("Location: manage_users.php");
    exit();
}
