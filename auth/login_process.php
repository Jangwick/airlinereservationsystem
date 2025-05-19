<?php
session_start();
require_once '../db/db_config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username/email and password from form
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($username_email) || empty($password)) {
        $_SESSION['login_error'] = "Username/email and password are required.";
        header("Location: login.php");
        exit();
    }
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, username, email, password, first_name, last_name, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // If remember me checked, set cookies
            if ($remember) {
                // Generate a unique token
                $token = bin2hex(random_bytes(50));
                
                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                $stmt->bind_param("si", $token, $user['user_id']);
                $stmt->execute();
                
                // Set cookies
                setcookie('remember_user', $user['user_id'], time() + (30 * 24 * 60 * 60), '/'); // 30 days
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
            }
            
            // Update last login timestamp
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                // Redirect regular users to user dashboard instead of homepage
                header("Location: ../user/dashboard.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid username/email or password.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid username/email or password.";
        header("Location: login.php");
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit();
}
