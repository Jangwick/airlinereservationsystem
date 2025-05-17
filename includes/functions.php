<?php
/**
 * Global functions for the Airline Reservation System
 * These functions help optimize performance across the application
 */

// Database connection cache
$GLOBALS['db_connections'] = [];

/**
 * Get database connection with caching
 */
function getDBConnection($force_new = false) {
    global $conn;
    
    if (!$force_new && isset($conn) && $conn && !$conn->connect_error) {
        return $conn;
    }
    
    require_once __DIR__ . '/../db/db_config.php';
    return $conn;
}

/**
 * Cache query results to avoid repeated database calls
 */
function cachedQuery($sql, $params = [], $ttl = 300) {
    static $cache = [];
    
    // Generate cache key based on query and parameters
    $cache_key = md5($sql . serialize($params));
    
    // Check if we have a cached result that hasn't expired
    if (isset($cache[$cache_key]) && $cache[$cache_key]['expires'] > time()) {
        return $cache[$cache_key]['data'];
    }
    
    // No valid cache, run the actual query
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $values[] = $param;
        }
        
        $stmt->bind_param($types, ...$values);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Cache the result
    $cache[$cache_key] = [
        'data' => $data,
        'expires' => time() + $ttl
    ];
    
    return $data;
}

/**
 * Optimize image URLs for faster loading
 */
function optimizeImageUrl($url, $width = null, $height = null, $quality = 80) {
    // If using a CDN or image optimization service, you could implement here
    // For now, let's just prepare the image for lazy loading
    
    // Return the optimized URL (simply the original for now)
    return $url;
}

/**
 * Clean and optimize output buffer
 */
function startOutputBuffer() {
    ob_start('optimizeOutput');
}

/**
 * Optimize HTML output by removing whitespace and comments
 */
function optimizeOutput($buffer) {
    // Remove comments (except IE conditional comments)
    $buffer = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $buffer);
    
    // Remove whitespace
    $buffer = preg_replace('/\s+/', ' ', $buffer);
    
    return $buffer;
}

/**
 * Get absolute URL for the application
 * Only define if it doesn't already exist
 */
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}

/**
 * Create a minified URL (absolute path)
 */
function url($path = '') {
    return getBaseUrl() . ltrim($path, '/');
}

/**
 * Optimize page loading by setting appropriate headers
 */
function setPerformanceHeaders() {
    // Set caching headers
    $cache_time = 60 * 60; // 1 hour
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
    header('Cache-Control: max-age=' . $cache_time . ', public');
    
    // Enable gzip compression if not already handled by Apache
    if (!in_array('gzip', array_map('trim', explode(',', $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')))) {
        ob_start('ob_gzhandler');
    }
}
?>
