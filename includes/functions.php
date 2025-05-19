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
    $conn = getDBConnection();
    $cache_key = md5($sql . serialize($params));
    $cache_file = sys_get_temp_dir() . '/sql_cache_' . $cache_key;
    
    // Check if we have a valid cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $ttl)) {
        return unserialize(file_get_contents($cache_file));
    }
    
    // No cache or expired, run the query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $bindParams[] = $param;
        }
        
        // Create the full array of parameters for bind_param
        $bindParamsRef = [];
        $bindParamsRef[] = $types;
        
        foreach ($bindParams as $key => $value) {
            $bindParamsRef[] = &$bindParams[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all results
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Store in cache
    file_put_contents($cache_file, serialize($data));
    
    return $data;
}

/**
 * Optimize image URLs for faster loading
 */
function optimizeImageUrl($url, $width = null, $height = null, $quality = 80) {
    // Check if we have a URL to optimize
    if (empty($url)) {
        return $url;
    }
    
    // Only optimize local images in the assets directory
    if (strpos($url, 'assets/images') === false) {
        return $url;
    }
    
    // TODO: Implement actual image optimization with width, height and quality
    // This would typically use a library like Intervention Image or a service like Cloudinary
    return $url;
}

/**
 * Clean and optimize output buffer
 */
function startOutputBuffer() {
    ob_start("optimizeOutput");
}

/**
 * Optimize HTML output by removing whitespace and comments
 */
function optimizeOutput($buffer) {
    // Don't optimize in development mode
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        return $buffer;
    }
    
    // Remove comments (but not IE conditional comments)
    $buffer = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $buffer);
    
    // Remove whitespace
    $buffer = preg_replace('/\s+/', ' ', $buffer);
    $buffer = preg_replace('/>\s+</', '><', $buffer);
    
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
    // Enable gzip compression
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'On');
        ini_set('zlib.output_compression_level', '5');
    }
    
    // Set caching headers for browsers
    header('Cache-Control: private, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($_SERVER['SCRIPT_FILENAME'])) . ' GMT');
}
?>
