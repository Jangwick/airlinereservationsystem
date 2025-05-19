<?php
// Script to create default image directory and files

// Define the destination directory
$dir = __DIR__ . '/images/destinations';

// Create directory if it doesn't exist
if (!file_exists($dir)) {
    if (!mkdir($dir, 0755, true)) {
        die("Failed to create directory: $dir");
    }
    echo "Created directory: $dir<br>";
}

// Path to default image
$default_image = $dir . '/default.jpg';

// Generate a simple placeholder image if it doesn't exist
if (!file_exists($default_image)) {
    // Create a 600x400 image
    $image = imagecreatetruecolor(600, 400);
    
    // Colors
    $bg_color = imagecolorallocate($image, 240, 240, 240);  // Light gray background
    $text_color = imagecolorallocate($image, 100, 100, 100); // Dark gray text
    $border_color = imagecolorallocate($image, 200, 200, 200); // Border color
    
    // Fill the background
    imagefill($image, 0, 0, $bg_color);
    
    // Add border
    imagerectangle($image, 0, 0, 599, 399, $border_color);
    
    // Add text
    $text = "Destination Image";
    $font_size = 5;
    
    // Get text dimensions
    $text_box = imagettfbbox($font_size, 0, "arial", $text);
    if ($text_box) {
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[7] - $text_box[1];
    } else {
        // If imagettfbbox fails, use approximation
        $text_width = strlen($text) * 10;
        $text_height = 20;
    }
    
    // Center text
    $text_x = (600 - $text_width) / 2;
    $text_y = (400 + $text_height) / 2;
    
    // Draw the text
    imagestring($image, $font_size, $text_x, $text_y, $text, $text_color);
    
    // Additional airplane icon in center
    imageline($image, 275, 180, 325, 180, $text_color);
    imageline($image, 300, 160, 300, 200, $text_color);
    
    // Save the image
    if (imagejpeg($image, $default_image, 90)) {
        echo "Default image created successfully: $default_image<br>";
    } else {
        echo "Failed to create default image<br>";
    }
    
    // Free memory
    imagedestroy($image);
} else {
    echo "Default image already exists: $default_image<br>";
}

// Create a simple PNG version as well (some systems might use .png extension)
$default_png = $dir . '/default.png';
if (!file_exists($default_png)) {
    // If we have the jpg, convert it, otherwise create a new one
    if (file_exists($default_image)) {
        $jpg = imagecreatefromjpeg($default_image);
        imagepng($jpg, $default_png);
        imagedestroy($jpg);
    } else {
        // Create a basic placeholder
        $image = imagecreatetruecolor(600, 400);
        $bg_color = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bg_color);
        imagepng($image, $default_png);
        imagedestroy($image);
    }
    echo "PNG version created: $default_png<br>";
}

echo "<p>All default images have been created. You should no longer see 404 errors for these resources.</p>";
?>
