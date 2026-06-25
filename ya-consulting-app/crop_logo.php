<?php
$sourcePath = 'public/images/logo.png';
$im = imagecreatefrompng($sourcePath);
if (!$im) {
    die("Could not load image\n");
}

$width = imagesx($im);
$height = imagesy($im);

// Find the bounding box of non-white pixels
$top = $height;
$bottom = 0;
$left = $width;
$right = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // If not white (allow some tolerance, e.g., < 250)
        if ($r < 252 || $g < 252 || $b < 252) {
            if ($x < $left) $left = $x;
            if ($x > $right) $right = $x;
            if ($y < $top) $top = $y;
            if ($y > $bottom) $bottom = $y;
        }
    }
}

// Add a small padding
$padding = 10;
$left = max(0, $left - $padding);
$top = max(0, $top - $padding);
$right = min($width - 1, $right + $padding);
$bottom = min($height - 1, $bottom + $padding);

$newWidth = $right - $left + 1;
$newHeight = $bottom - $top + 1;

if ($newWidth > 0 && $newHeight > 0) {
    $cropped = imagecrop($im, ['x' => $left, 'y' => $top, 'width' => $newWidth, 'height' => $newHeight]);
    if ($cropped !== false) {
        imagepng($cropped, $sourcePath);
        imagedestroy($cropped);
        echo "Cropped successfully! New dimensions: {$newWidth}x{$newHeight}\n";
    } else {
        echo "Crop failed\n";
    }
} else {
    echo "No content found to crop\n";
}

imagedestroy($im);
