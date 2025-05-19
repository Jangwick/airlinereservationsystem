<?php
// Script to generate a default placeholder image
// Execute once then you can delete this file

$width = 800;
$height = 450;
$image = imagecreatetruecolor($width, $height);

// Create gradient background (blue sky)
$skyBlue = imagecolorallocate($image, 135, 206, 235);
$darkBlue = imagecolorallocate($image, 0, 0, 139);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Fill with gradient
for ($i = 0; $i < $height; $i++) {
    $ratio = $i / $height;
    $r = 135 - ($ratio * 135);
    $g = 206 - ($ratio * 100);
    $b = 235 - ($ratio * 96);
    $color = imagecolorallocate($image, $r, $g, $b);
    imageline($image, 0, $i, $width, $i, $color);
}

// Add airplane silhouette
imagefilledrectangle($image, $width/2-100, $height/2-10, $width/2+120, $height/2+10, $white);
imagefilledellipse($image, $width/2-80, $height/2, 50, 30, $white);
imagefilledpolygon($image, array(
    $width/2+80, $height/2-10, 
    $width/2+120, $height/2-30, 
    $width/2+120, $height/2, 
    $width/2+80, $height/2+10
), 4, $white);
imagefilledpolygon($image, array(
    $width/2, $height/2-10, 
    $width/2-20, $height/2-50, 
    $width/2+40, $height/2-50, 
    $width/2+60, $height/2-10
), 4, $white);

// Add text
$text = "Destination Image";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
imagestring($image, $font_size, ($width - $text_width) / 2, $height - 50, $text, $black);

// Output image
if (!is_dir('assets/images')) {
    mkdir('assets/images', 0755, true);
}
if (!is_dir('assets/images/destinations')) {
    mkdir('assets/images/destinations', 0755, true);
}

imagejpeg($image, 'assets/images/destinations/default.jpg');
imagejpeg($image, 'assets/images/placeholder.jpg');
imagedestroy($image);

echo "Placeholder images created successfully!";
?>
