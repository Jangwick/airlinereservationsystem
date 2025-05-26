<?php
/**
 * Currency Helper Functions
 * 
 * Contains functions for handling currency display and formatting throughout the application.
 */

/**
 * Get the currency symbol based on system settings
 * 
 * @param mysqli $conn Database connection
 * @return string Currency symbol
 */
function getCurrencySymbol($conn = null) {
    // Default to US Dollar if database isn't accessible
    $default_symbol = '$';
    
    if (!$conn) {
        return $default_symbol;
    }
    
    try {
        // Check if settings table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($table_check->num_rows > 0) {
            // Check if currency_symbol setting exists
            $query = "SELECT value FROM settings WHERE name = 'currency_symbol' LIMIT 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $symbol = $result->fetch_assoc()['value'];
                return $symbol ?: $default_symbol;
            }
        }
        
        return $default_symbol;
    } catch (Exception $e) {
        error_log("Error getting currency symbol: " . $e->getMessage());
        return $default_symbol;
    }
}

/**
 * Get the currency code (USD, EUR, etc.)
 * 
 * @param mysqli $conn Database connection
 * @return string Currency code
 */
function getCurrencyCode($conn = null) {
    // Default to USD if database isn't accessible
    $default_code = 'USD';
    
    if (!$conn) {
        return $default_code;
    }
    
    try {
        // Check if settings table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($table_check->num_rows > 0) {
            // Check if currency_code setting exists
            $query = "SELECT value FROM settings WHERE name = 'currency_code' LIMIT 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $code = $result->fetch_assoc()['value'];
                return $code ?: $default_code;
            }
        }
        
        return $default_code;
    } catch (Exception $e) {
        error_log("Error getting currency code: " . $e->getMessage());
        return $default_code;
    }
}

/**
 * Format amount with currency symbol
 * 
 * @param float $amount Amount to format
 * @param mysqli $conn Database connection
 * @return string Formatted amount with currency symbol
 */
function formatMoney($amount, $conn = null) {
    if ($conn) {
        $symbol = getCurrencySymbol($conn);
    } else {
        // If no connection provided, use default symbol
        $symbol = '$';
    }
    
    return $symbol . number_format($amount, 2);
}

/**
 * Convert amount from one currency to another (placeholder)
 * 
 * @param float $amount Amount to convert
 * @param string $from_currency Currency code to convert from
 * @param string $to_currency Currency code to convert to
 * @return float Converted amount
 */
function convertCurrency($amount, $from_currency, $to_currency) {
    // This would be implemented with a real currency conversion API
    // For now, just return the original amount
    return $amount;
}
?>
