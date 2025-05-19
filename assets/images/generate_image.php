<?php
// Script to generate placeholder images dynamically

// Get destination name from query string
$destination = isset($_GET['name']) ? $_GET['name'] : 'Destination';
$width = isset($_GET['width']) ? intval($_GET['width']) : 600;
$height = isset($_GET['height']) ? intval($_GET['height']) : 400;

// Limit dimensions for security
$width = min($width, 1200);
$height = min($height, 800);

// Set content type to JPEG
header('Content-Type: image/jpeg');

// Create image
$image = imagecreatetruecolor($width, $height);

// Define colors
$bg_color = imagecolorallocate($image, 240, 240, 240);  // Light gray background
$text_color = imagecolorallocate($image, 50, 50, 50);   // Dark text
$accent_color = imagecolorallocate($image, 59, 113, 202); // Primary blue

// Fill the background
imagefill($image, 0, 0, $bg_color);

// Create a stylish background pattern
for ($i = 0; $i < $width; $i += 20) {
    imageline($image, $i, 0, $i, $height, imagecolorallocate($image, 230, 230, 230));
}
for ($i = 0; $i < $height; $i += 20) {
    imageline($image, 0, $i, $width, $i, imagecolorallocate($image, 230, 230, 230));
}

// Draw a border
imagerectangle($image, 0, 0, $width-1, $height-1, $accent_color);
imagerectangle($image, 5, 5, $width-6, $height-6, $accent_color);

// Add destination name text
$font_size = 5;
$dest_text = ucwords($destination);

// Center the text
$text_width = imagefontwidth($font_size) * strlen($dest_text);
$text_height = imagefontheight($font_size);
$text_x = ($width - $text_width) / 2;
$text_y = ($height - $text_height) / 2;

// Add a semi-transparent background for text
imagefilledrectangle(
    $image,
    $text_x - 20,
    $text_y - 20,
    $text_x + $text_width + 20,
    $text_y + $text_height + 10,
    imagecolorallocatealpha($image, 255, 255, 255, 80)
);

// Draw the text
imagestring($image, $font_size, $text_x, $text_y, $dest_text, $text_color);

// Add "Popular Destination" text at the top
$top_text = "Popular Destination";
$top_text_width = imagefontwidth(3) * strlen($top_text);
$top_text_x = ($width - $top_text_width) / 2;
imagestring($image, 3, $top_text_x, 15, $top_text, $accent_color);

// Add a small airplane icon
$plane_size = 30;
$plane_x = $width - $plane_size - 15;
$plane_y = $height - $plane_size - 15;

// Draw simplified airplane
imageline($image, $plane_x, $plane_y + $plane_size/2, $plane_x + $plane_size, $plane_y + $plane_size/2, $accent_color);
imageline($image, $plane_x + $plane_size/2, $plane_y, $plane_x + $plane_size/2, $plane_y + $plane_size, $accent_color);
imagefilledellipse($image, $plane_x + $plane_size/2, $plane_y + $plane_size/2, 10, 10, $accent_color);

// Output the image
imagejpeg($image, null, 90);
imagedestroy($image);
?>
