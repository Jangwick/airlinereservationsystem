<?php
// List of destinations to create placeholder images for
$destinations = [
    'manila',
    'singapore',
    'tokyo',
    'cebu',
    'hong_kong',
    'seoul',
    'default'
];

$destinationsDir = __DIR__ . '/destinations';

// Create directory if it doesn't exist
if (!file_exists($destinationsDir)) {
    mkdir($destinationsDir, 0755, true);
}

// Function to generate simple placeholder images with text
function generatePlaceholderImage($text, $filename, $width = 800, $height = 600) {
    $image = imagecreatetruecolor($width, $height);
    
    // Define colors
    $bgColor = imagecolorallocate($image, 240, 240, 240); // Light gray
    $textColor = imagecolorallocate($image, 50, 50, 50); // Dark gray
    $accentColor = imagecolorallocate($image, 0, 123, 255); // Blue accent
    
    // Fill background
    imagefill($image, 0, 0, $bgColor);
    
    // Draw border
    $borderWidth = 15;
    imagefilledrectangle($image, 0, 0, $width, $borderWidth, $accentColor); // top
    imagefilledrectangle($image, 0, 0, $borderWidth, $height, $accentColor); // left
    imagefilledrectangle($image, 0, $height - $borderWidth, $width, $height, $accentColor); // bottom
    imagefilledrectangle($image, $width - $borderWidth, 0, $width, $height, $accentColor); // right
    
    // Add text
    $textCapitalized = ucfirst($text);
    $fontSize = 5;
    $fontWidth = imagefontwidth($fontSize);
    $fontHeight = imagefontheight($fontSize);
    $textWidth = $fontWidth * strlen($textCapitalized);
    $textX = ($width - $textWidth) / 2;
    $textY = ($height - $fontHeight) / 2;
    
    // Draw text
    imagestring($image, $fontSize, $textX, $textY, $textCapitalized, $textColor);
    imagestring($image, $fontSize, $textX, $textY + 30, "Placeholder Image", $textColor);
    
    // Save the image
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
    
    return true;
}

// Generate images for each destination
foreach ($destinations as $destination) {
    $filename = $destinationsDir . '/' . $destination . '.jpg';
    if (!file_exists($filename)) {
        if (generatePlaceholderImage($destination, $filename)) {
            echo "Generated placeholder image for {$destination}.<br>";
        } else {
            echo "Failed to generate image for {$destination}.<br>";
        }
    } else {
        echo "Image for {$destination} already exists.<br>";
    }
}

echo "All placeholder images have been created!";
?>
