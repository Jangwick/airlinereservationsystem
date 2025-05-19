<?php
/**
 * Optimized Asset Loader
 * This file provides functions to efficiently load CSS and JS assets
 */

// Initialize asset arrays
$loaded_css = [];
$loaded_js = [];

/**
 * Load CSS stylesheets efficiently
 * @param string|array $files CSS file(s) to load
 * @param bool $critical Whether this is critical CSS that should be loaded synchronously
 */
function load_css($files, $critical = false) {
    global $loaded_css, $baseUrl;
    
    if (!is_array($files)) {
        $files = [$files];
    }
    
    foreach ($files as $file) {
        // Skip if already loaded
        if (in_array($file, $loaded_css)) {
            continue;
        }
        
        // Add to loaded list
        $loaded_css[] = $file;
        
        // Determine if it's an external URL or local file
        $isExternal = (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0);
        $url = $isExternal ? $file : $baseUrl . $file;
        
        // Add version timestamp to local files to bust cache when files change
        if (!$isExternal && file_exists($_SERVER['DOCUMENT_ROOT'] . '/airlinereservationsystem/' . $file)) {
            $url .= '?v=' . filemtime($_SERVER['DOCUMENT_ROOT'] . '/airlinereservationsystem/' . $file);
        }
        
        // Output the appropriate link tag
        if ($critical) {
            echo "<link rel=\"stylesheet\" href=\"$url\">\n";
        } else {
            echo "<link rel=\"stylesheet\" href=\"$url\" media=\"print\" onload=\"this.media='all'\">\n";
            echo "<noscript><link rel=\"stylesheet\" href=\"$url\"></noscript>\n";
        }
    }
}

/**
 * Load JavaScript files efficiently
 * @param string|array $files JS file(s) to load
 * @param bool $defer Whether to use defer attribute
 * @param bool $async Whether to use async attribute
 * @param bool $inFooter Whether to mark this file for footer loading
 */
function load_js($files, $defer = true, $async = false, $inFooter = true) {
    global $loaded_js, $baseUrl, $footer_js;
    
    if (!is_array($files)) {
        $files = [$files];
    }
    
    $attrs = ($defer ? ' defer' : '') . ($async ? ' async' : '');
    
    foreach ($files as $file) {
        // Skip if already loaded
        if (in_array($file, $loaded_js)) {
            continue;
        }
        
        // Add to loaded list
        $loaded_js[] = $file;
        
        // Determine if it's an external URL or local file
        $isExternal = (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0);
        $url = $isExternal ? $file : $baseUrl . $file;
        
        // Add version timestamp to local files
        if (!$isExternal && file_exists($_SERVER['DOCUMENT_ROOT'] . '/airlinereservationsystem/' . $file)) {
            $url .= '?v=' . filemtime($_SERVER['DOCUMENT_ROOT'] . '/airlinereservationsystem/' . $file);
        }
        
        // Create the script tag
        $script_tag = "<script src=\"$url\"$attrs></script>\n";
        
        // Either output now or store for footer
        if ($inFooter) {
            if (!isset($GLOBALS['footer_js'])) {
                $GLOBALS['footer_js'] = [];
            }
            $GLOBALS['footer_js'][] = $script_tag;
        } else {
            echo $script_tag;
        }
    }
}

/**
 * Output footer JavaScript that was queued
 */
function output_footer_js() {
    if (isset($GLOBALS['footer_js']) && !empty($GLOBALS['footer_js'])) {
        echo "<!-- Footer JavaScript -->\n";
        foreach ($GLOBALS['footer_js'] as $script) {
            echo $script;
        }
    }
}
?>
