<?php
/**
 * Currency Helper Functions
 * 
 * Provides standardized functions for currency formatting and handling
 */

// Get currency symbol from settings or use default
function getCurrencySymbol($conn = null) {
    // Default currency symbol for Philippine Peso
    $symbol = '₱';
    
    // If database connection is provided, try to get from settings
    if ($conn) {
        try {
            // Check if settings table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'settings'");
            if ($table_check->num_rows > 0) {
                // Try to get currency symbol from settings
                $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'currency_symbol' LIMIT 1");
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $symbol = $result->fetch_assoc()['value'];
                }
            }
        } catch (Exception $e) {
            // If any error occurs, use default
            error_log("Error getting currency symbol: " . $e->getMessage());
        }
    }
    
    return $symbol;
}

// Format amount with currency symbol
function formatCurrency($amount, $conn = null) {
    $symbol = getCurrencySymbol($conn);
    return $symbol . number_format($amount, 2);
}
?>
