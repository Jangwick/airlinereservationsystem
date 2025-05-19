<?php
// Start output buffering for optimization
if (!ob_get_level()) {
    ob_start("ob_gzhandler");
}

// Include required functions
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/asset_loader.php';

// Set performance headers
setPerformanceHeaders();

// Get base URL for assets
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'SkyWay Airlines'; ?></title>
    
    <!-- Performance optimization: Preload critical assets -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Critical CSS -->
    <?php load_css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css', true); ?>
    <?php load_css('assets/css/style.css', true); ?>
    
    <!-- Non-critical CSS -->
    <?php 
    // Load additional CSS files if defined
    if (isset($additional_css) && is_array($additional_css)) {
        load_css($additional_css, false);
    }
    ?>
    
    <!-- Font Awesome loaded with reduced priority -->
    <?php load_css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', false); ?>
    
    <!-- Page-specific header content -->
    <?php if (isset($header_content)) echo $header_content; ?>
</head>
<body class="page-transition">
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>
