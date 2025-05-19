<?php
/**
 * Image fallback helper functions
 */

/**
 * Check if destination image exists, if not use default
 * @param string $destinationName The name of the destination
 * @return string Path to the destination image
 */
function getDestinationImagePath($destinationName) {
    $baseUrl = getBaseUrl();
    $destinationName = strtolower(preg_replace('/[^a-z0-9_]/', '_', $destinationName));
    $imagePath = "assets/images/destinations/{$destinationName}.jpg";
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . "/airlinereservationsystem/" . $imagePath;
    
    // If specific destination image doesn't exist, use default
    if (!file_exists($fullPath)) {
        $imagePath = "assets/images/destinations/default.jpg";
    }
    
    return $baseUrl . $imagePath;
}

/**
 * Output HTML for an image with fallback
 * @param string $destinationName The name of the destination
 * @param string $altText Alt text for the image
 * @param string $cssClass CSS class for the image
 * @return string HTML for the image tag
 */
function destinationImageTag($destinationName, $altText = '', $cssClass = '') {
    $baseUrl = getBaseUrl();
    $destinationName = strtolower(preg_replace('/[^a-z0-9_]/', '_', $destinationName));
    $imagePath = "assets/images/destinations/{$destinationName}.jpg";
    $defaultPath = "assets/images/destinations/default.jpg";
    
    // Make sure alt text is set
    if (empty($altText)) {
        $altText = ucfirst($destinationName);
    }
    
    // Build class attribute
    $classAttr = !empty($cssClass) ? " class=\"{$cssClass}\"" : '';
    
    // Return image tag with onerror handler to use default image
    return "<img src=\"{$baseUrl}{$imagePath}\" alt=\"{$altText}\"{$classAttr} onerror=\"this.src='{$baseUrl}{$defaultPath}'\">";
}
?>
