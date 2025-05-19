<?php
// Create the directory if it doesn't exist
$directory = __DIR__;
if (!file_exists($directory)) {
    mkdir($directory, 0755, true);
}

// Create a simple placeholder image using GD
$width = 800;
$height = 500;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 51, 113, 202); // Blue background
$text_color = imagecolorallocate($image, 255, 255, 255); // White text

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add text
$text = "SkyWay Airlines";
$font = 5; // Built-in font
$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);
$center_x = ($width - $text_width) / 2;
$center_y = ($height - $text_height) / 2;
imagestring($image, $font, $center_x, $center_y - 20, $text, $text_color);

// Add another line of text
$text2 = "Default Destination Image";
$text2_width = imagefontwidth($font) * strlen($text2);
$center_x2 = ($width - $text2_width) / 2;
imagestring($image, $font, $center_x2, $center_y + 10, $text2, $text_color);

// Save the image
imagejpeg($image, __DIR__ . '/default.jpg', 90);
imagedestroy($image);

echo "Default image created successfully at: " . __DIR__ . '/default.jpg';
?>
