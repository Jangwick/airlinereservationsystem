<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Check if settings table exists, create if it doesn't
$settings_table_exists = $conn->query("SHOW TABLES LIKE 'settings'")->num_rows > 0;
if (!$settings_table_exists) {
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
    
    // Insert default settings
    $default_settings = [
        // General Settings
        ['site_title', 'SkyWay Airlines', 'general', 'text', 'Site Title', 'The name of your airline website'],
        ['site_description', 'Book your flights easily, travel comfortably, and explore new destinations.', 'general', 'textarea', 'Site Description', 'A brief description of your airline'],
        ['contact_email', 'info@skywayairlines.com', 'general', 'email', 'Contact Email', 'Public contact email address'],
        ['contact_phone', '+63 (2) 8123 4567', 'general', 'text', 'Contact Phone', 'Public contact phone number'],
        ['contact_address', '123 Airport Road, Metro Manila, Philippines', 'general', 'textarea', 'Contact Address', 'Physical address of the airline'],
        
        // Booking Settings
        ['advance_booking_days', '365', 'booking', 'number', 'Advance Booking Days', 'How many days in advance customers can book flights'],
        ['min_hours_before_departure', '3', 'booking', 'number', 'Minimum Hours Before Departure', 'Minimum hours before departure that bookings can be made'],
        ['cancellation_fee_percentage', '10', 'booking', 'number', 'Cancellation Fee (%)', 'Percentage of ticket price charged as cancellation fee'],
        ['allow_guest_bookings', '1', 'booking', 'boolean', 'Allow Guest Bookings', 'Allow users to book flights without an account'],
        ['booking_expiry_minutes', '30', 'booking', 'number', 'Booking Expiry (Minutes)', 'Number of minutes before an unpaid booking expires'],
        
        // Payment Settings
        ['currency_code', 'USD', 'payment', 'text', 'Currency Code', 'Currency code for payments (e.g., USD, EUR)'],
        ['currency_symbol', '$', 'payment', 'text', 'Currency Symbol', 'Currency symbol for display'],
        ['payment_gateway', 'paypal', 'payment', 'select', 'Payment Gateway', 'Default payment gateway'],
        ['test_mode', '1', 'payment', 'boolean', 'Test Mode', 'Enable test/sandbox mode for payments'],
        ['vat_percentage', '12', 'payment', 'number', 'VAT Percentage', 'VAT tax percentage applied to bookings'],
        
        // Email Settings
        ['admin_email', 'admin@skywayairlines.com', 'email', 'email', 'Admin Email', 'Email address for admin notifications'],
        ['enable_email_notifications', '1', 'email', 'boolean', 'Enable Email Notifications', 'Send email notifications for bookings and updates'],
        ['email_sender_name', 'SkyWay Airlines', 'email', 'text', 'Email Sender Name', 'Name displayed as the sender of emails'],
        
        // System Settings
        ['maintenance_mode', '0', 'system', 'boolean', 'Maintenance Mode', 'Put the website in maintenance mode'],
        ['pagination_limit', '10', 'system', 'number', 'Pagination Limit', 'Number of items to display per page'],
        ['debug_mode', '0', 'system', 'boolean', 'Debug Mode', 'Enable debug information (not recommended for production)'],
        ['log_user_activity', '1', 'system', 'boolean', 'Log User Activity', 'Track user activities in the system'],
        ['session_timeout_minutes', '30', 'system', 'number', 'Session Timeout (Minutes)', 'Minutes of inactivity before user is logged out'],
        ['default_theme', 'default', 'system', 'text', 'Default Theme', 'Default theme for the website']
    ];
    
    $insert_stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group, setting_type, setting_label, setting_description) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($default_settings as $setting) {
        $insert_stmt->bind_param("ssssss", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5]);
        $insert_stmt->execute();
    }
}

// Get all settings grouped by category
$groups = ['general', 'booking', 'payment', 'email', 'system'];
$settings = [];

foreach ($groups as $group) {
    $result = $conn->query("SELECT * FROM settings WHERE setting_group = '$group' ORDER BY setting_id ASC");
    $settings[$group] = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$group][] = $row;
    }
}

// Handle status messages
$status_message = '';
$status_type = '';

if (isset($_SESSION['settings_status'])) {
    $status_message = $_SESSION['settings_status']['message'];
    $status_type = $_SESSION['settings_status']['type'];
    unset($_SESSION['settings_status']);
}

// Get active tab from session or default to general
$active_tab = isset($_SESSION['settings_active_tab']) ? $_SESSION['settings_active_tab'] : 'general';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportSettings">
                            <i class="fas fa-download me-1"></i> Export Settings
                        </button>
                    </div>
                </div>
                
                <!-- Status Messages -->
                <?php if (!empty($status_message)): ?>
                    <div class="alert alert-<?php echo $status_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $status_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'general') ? 'active' : ''; ?>" 
                                        id="general-tab" data-bs-toggle="tab" data-bs-target="#general" 
                                        type="button" role="tab" aria-selected="<?php echo ($active_tab == 'general') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-cog me-1"></i> General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'booking') ? 'active' : ''; ?>" 
                                        id="booking-tab" data-bs-toggle="tab" data-bs-target="#booking" 
                                        type="button" role="tab" aria-selected="<?php echo ($active_tab == 'booking') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-ticket-alt me-1"></i> Booking
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'payment') ? 'active' : ''; ?>" 
                                        id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" 
                                        type="button" role="tab" aria-selected="<?php echo ($active_tab == 'payment') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-credit-card me-1"></i> Payment
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'email') ? 'active' : ''; ?>" 
                                        id="email-tab" data-bs-toggle="tab" data-bs-target="#email" 
                                        type="button" role="tab" aria-selected="<?php echo ($active_tab == 'email') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-envelope me-1"></i> Email
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'system') ? 'active' : ''; ?>" 
                                        id="system-tab" data-bs-toggle="tab" data-bs-target="#system" 
                                        type="button" role="tab" aria-selected="<?php echo ($active_tab == 'system') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-server me-1"></i> System
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-4 border border-top-0 rounded-bottom" id="settingsTabsContent">
                            <!-- General Settings -->
                            <div class="tab-pane fade <?php echo ($active_tab == 'general') ? 'show active' : ''; ?>" id="general" role="tabpanel">
                                <h4 class="mb-4">General Settings</h4>
                                <form action="settings_actions.php" method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <input type="hidden" name="setting_group" value="general">
                                    
                                    <?php foreach ($settings['general'] as $setting): ?>
                                    <div class="mb-3">
                                        <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($setting['setting_label']); ?>
                                        </label>
                                        
                                        <?php if ($setting['setting_type'] == 'text'): ?>
                                            <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'textarea'): ?>
                                            <textarea class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                        
                                        <?php elseif ($setting['setting_type'] == 'email'): ?>
                                            <input type="email" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'number'): ?>
                                            <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" 
                                                    name="settings[<?php echo $setting['setting_key']; ?>]" value="1" 
                                                    <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                    Enable
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($setting['setting_description'])): ?>
                                            <div class="form-text"><?php echo htmlspecialchars($setting['setting_description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mt-4 text-end">
                                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Booking Settings -->
                            <div class="tab-pane fade <?php echo ($active_tab == 'booking') ? 'show active' : ''; ?>" id="booking" role="tabpanel">
                                <h4 class="mb-4">Booking Settings</h4>
                                <form action="settings_actions.php" method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <input type="hidden" name="setting_group" value="booking">
                                    
                                    <?php foreach ($settings['booking'] as $setting): ?>
                                    <div class="mb-3">
                                        <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($setting['setting_label']); ?>
                                        </label>
                                        
                                        <?php if ($setting['setting_type'] == 'text'): ?>
                                            <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'textarea'): ?>
                                            <textarea class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                        
                                        <?php elseif ($setting['setting_type'] == 'number'): ?>
                                            <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" 
                                                    name="settings[<?php echo $setting['setting_key']; ?>]" value="1" 
                                                    <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                    Enable
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($setting['setting_description'])): ?>
                                            <div class="form-text"><?php echo htmlspecialchars($setting['setting_description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mt-4 text-end">
                                        <button type="submit" class="btn btn-primary">Save Booking Settings</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Payment Settings -->
                            <div class="tab-pane fade <?php echo ($active_tab == 'payment') ? 'show active' : ''; ?>" id="payment" role="tabpanel">
                                <h4 class="mb-4">Payment Settings</h4>
                                <form action="settings_actions.php" method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <input type="hidden" name="setting_group" value="payment">
                                    
                                    <?php foreach ($settings['payment'] as $setting): ?>
                                    <div class="mb-3">
                                        <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($setting['setting_label']); ?>
                                        </label>
                                        
                                        <?php if ($setting['setting_type'] == 'text'): ?>
                                            <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'select' && $setting['setting_key'] == 'payment_gateway'): ?>
                                            <select class="form-select" id="<?php echo $setting['setting_key']; ?>" 
                                                    name="settings[<?php echo $setting['setting_key']; ?>]">
                                                <option value="paypal" <?php echo $setting['setting_value'] == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                                <option value="stripe" <?php echo $setting['setting_value'] == 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                                <option value="bank_transfer" <?php echo $setting['setting_value'] == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                                <option value="cash" <?php echo $setting['setting_value'] == 'cash' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                            </select>
                                        
                                        <?php elseif ($setting['setting_type'] == 'number'): ?>
                                            <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" 
                                                    name="settings[<?php echo $setting['setting_key']; ?>]" value="1" 
                                                    <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                    Enable
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($setting['setting_description'])): ?>
                                            <div class="form-text"><?php echo htmlspecialchars($setting['setting_description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mt-4 text-end">
                                        <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Email Settings -->
                            <div class="tab-pane fade <?php echo ($active_tab == 'email') ? 'show active' : ''; ?>" id="email" role="tabpanel">
                                <h4 class="mb-4">Email Settings</h4>
                                <form action="settings_actions.php" method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <input type="hidden" name="setting_group" value="email">
                                    
                                    <?php foreach ($settings['email'] as $setting): ?>
                                    <div class="mb-3">
                                        <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($setting['setting_label']); ?>
                                        </label>
                                        
                                        <?php if ($setting['setting_type'] == 'text'): ?>
                                            <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'email'): ?>
                                            <input type="email" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" 
                                                    name="settings[<?php echo $setting['setting_key']; ?>]" value="1" 
                                                    <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                    Enable
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($setting['setting_description'])): ?>
                                            <div class="form-text"><?php echo htmlspecialchars($setting['setting_description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mt-4">
                                        <h5>Additional Email Configuration</h5>
                                        <p class="text-muted">Configure SMTP settings for sending emails. Leave blank to use default PHP mail().</p>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_host" class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtp_host" name="settings[smtp_host]" 
                                                value="<?php echo getSetting('smtp_host'); ?>" placeholder="e.g., smtp.gmail.com">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_port" class="form-label">SMTP Port</label>
                                            <input type="text" class="form-control" id="smtp_port" name="settings[smtp_port]" 
                                                value="<?php echo getSetting('smtp_port'); ?>" placeholder="e.g., 587">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_username" class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" id="smtp_username" name="settings[smtp_username]" 
                                                value="<?php echo getSetting('smtp_username'); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_password" class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" id="smtp_password" name="settings[smtp_password]" 
                                                value="<?php echo getSetting('smtp_password'); ?>">
                                            <div class="form-text">Leave blank to keep existing password.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_encryption" class="form-label">Encryption</label>
                                            <select class="form-select" id="smtp_encryption" name="settings[smtp_encryption]">
                                                <option value="" <?php echo getSetting('smtp_encryption') == '' ? 'selected' : ''; ?>>None</option>
                                                <option value="tls" <?php echo getSetting('smtp_encryption') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo getSetting('smtp_encryption') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 text-end">
                                        <button type="button" class="btn btn-outline-secondary me-2" onclick="testEmailSettings()">
                                            Test Email Settings
                                        </button>
                                        <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- System Settings -->
                            <div class="tab-pane fade <?php echo ($active_tab == 'system') ? 'show active' : ''; ?>" id="system" role="tabpanel">
                                <h4 class="mb-4">System Settings</h4>
                                <form action="settings_actions.php" method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <input type="hidden" name="setting_group" value="system">
                                    
                                    <?php foreach ($settings['system'] as $setting): ?>
                                    <div class="mb-3">
                                        <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($setting['setting_label']); ?>
                                        </label>
                                        
                                        <?php if ($setting['setting_type'] == 'text'): ?>
                                            <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'textarea'): ?>
                                            <textarea class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                        
                                        <?php elseif ($setting['setting_type'] == 'number'): ?>
                                            <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        
                                        <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" 
                                                    name="settings[<?php echo $setting['setting_key']; ?>]" value="1" 
                                                    <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                    Enable
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($setting['setting_description'])): ?>
                                            <div class="form-text"><?php echo htmlspecialchars($setting['setting_description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mt-4">
                                        <h5>Maintenance Mode Message</h5>
                                        <textarea class="form-control" id="maintenance_message" name="settings[maintenance_message]" rows="3"><?php echo getSetting('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon!'); ?></textarea>
                                        <div class="form-text">Message to display when maintenance mode is enabled.</div>
                                    </div>
                                    
                                    <div class="mt-4 text-end">
                                        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#clearCacheModal">
                                            Clear Cache
                                        </button>
                                        <button type="submit" class="btn btn-primary">Save System Settings</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Test Email Modal -->
    <div class="modal fade" id="testEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Email Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_email" class="form-label">Send test email to:</label>
                        <input type="email" class="form-control" id="test_email" name="test_email">
                    </div>
                    <div id="testEmailResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="sendTestEmail">Send Test Email</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clear Cache Modal -->
    <div class="modal fade" id="clearCacheModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clear System Cache</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to clear the system cache? This will reset all temporary data and might cause a momentary performance impact.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmCacheClear">Clear Cache</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Track active tab
        document.querySelectorAll('#settingsTabs button').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-bs-target').replace('#', '');
                fetch('settings_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=set_active_tab&tab=' + tabId
                });
            });
        });
        
        // Test email settings
        function testEmailSettings() {
            const testEmailModal = new bootstrap.Modal(document.getElementById('testEmailModal'));
            testEmailModal.show();
        }
        
        document.getElementById('sendTestEmail').addEventListener('click', function() {
            const testEmail = document.getElementById('test_email').value;
            const resultDiv = document.getElementById('testEmailResult');
            
            resultDiv.innerHTML = '<div class="alert alert-info">Sending test email...</div>';
            
            fetch('settings_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test_email&email=' + encodeURIComponent(testEmail)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">Email sent successfully!</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger">An error occurred: ' + error + '</div>';
            });
        });
        
        // Export settings
        document.getElementById('exportSettings').addEventListener('click', function() {
            window.location.href = 'settings_actions.php?action=export_settings';
        });
        
        // Clear cache
        document.getElementById('confirmCacheClear').addEventListener('click', function() {
            fetch('settings_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_cache'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cache cleared successfully!');
                } else {
                    alert('Error clearing cache: ' + data.message);
                }
                
                // Close the modal
                bootstrap.Modal.getInstance(document.getElementById('clearCacheModal')).hide();
            });
        });
    </script>

<?php
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
?>
</body>
</html>
