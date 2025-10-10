<?php
// PNG to ICO Converter Script
// This script converts the mitsubishi_logo.png to favicon.ico and saves it to the root directory

function pngToIco($pngPath, $icoPath) {
    // Check if PNG file exists
    if (!file_exists($pngPath)) {
        return "Error: PNG file not found at $pngPath";
    }
    
    // Get image information
    $imageInfo = getimagesize($pngPath);
    if ($imageInfo === false) {
        return "Error: Invalid PNG file";
    }
    
    // Create image resource from PNG
    $srcImage = imagecreatefrompng($pngPath);
    if ($srcImage === false) {
        return "Error: Could not create image from PNG";
    }
    
    // Create ICO file
    $icoData = createIcoData($srcImage);
    
    // Save ICO file
    $result = file_put_contents($icoPath, $icoData);
    if ($result === false) {
        return "Error: Could not save ICO file to $icoPath";
    }
    
    imagedestroy($srcImage);
    return "Success: ICO file created at $icoPath";
}

function createIcoData($image) {
    $width = imagesx($image);
    $height = imagesy($image);
    
    // ICO header
    $ico = pack('v', 0); // Reserved
    $ico .= pack('v', 1); // Icon type
    $ico .= pack('v', 1); // Number of images
    
    // Icon directory entry
    $ico .= pack('C', $width == 256 ? 0 : $width); // Width
    $ico .= pack('C', $height == 256 ? 0 : $height); // Height
    $ico .= pack('C', 0); // Color palette
    $ico .= pack('C', 0); // Reserved
    $ico .= pack('v', 1); // Color planes
    $ico .= pack('v', 32); // Bits per pixel
    $ico .= pack('V', 0); // Image size (will be updated)
    $ico .= pack('V', 22); // Image offset
    
    // Convert image to BMP format
    $bmpData = imageToBmp($image);
    
    // Update image size
    $bmpLength = strlen($bmpData);
    $ico = substr($ico, 0, 14) . pack('V', $bmpLength) . substr($ico, 18);
    
    // Append BMP data
    $ico .= $bmpData;
    
    return $ico;
}

function imageToBmp($image) {
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Create BMP header
    $bmpHeader = 'BM'; // Signature
    $bmpHeader .= pack('V', 0); // File size (will be updated)
    $bmpHeader .= pack('v', 0); // Reserved
    $bmpHeader .= pack('v', 0); // Reserved
    $bmpHeader .= pack('V', 54); // Offset to pixel data
    
    // DIB header
    $bmpHeader .= pack('V', 40); // DIB header size
    $bmpHeader .= pack('V', $width); // Width
    $bmpHeader .= pack('V', $height * 2); // Height (doubled for AND mask)
    $bmpHeader .= pack('v', 1); // Color planes
    $bmpHeader .= pack('v', 32); // Bits per pixel
    $bmpHeader .= pack('V', 0); // Compression
    $bmpHeader .= pack('V', 0); // Image size (can be 0 for uncompressed)
    $bmpHeader .= pack('V', 2835); // Horizontal resolution
    $bmpHeader .= pack('V', 2835); // Vertical resolution
    $bmpHeader .= pack('V', 0); // Colors in palette
    $bmpHeader .= pack('V', 0); // Important colors
    
    // Image data
    $bmpData = '';
    for ($y = $height - 1; $y >= 0; $y--) {
        for ($x = 0; $x < $width; $x++) {
            $color = imagecolorat($image, $x, $y);
            $alpha = ($color >> 24) & 0x7F;
            $alpha = (127 - $alpha) * 2;
            $alpha = $alpha > 255 ? 255 : $alpha;
            
            $bmpData .= pack('C', $color & 0xFF); // Blue
            $bmpData .= pack('C', ($color >> 8) & 0xFF); // Green
            $bmpData .= pack('C', ($color >> 16) & 0xFF); // Red
            $bmpData .= pack('C', $alpha); // Alpha
        }
    }
    
    // AND mask (required but can be all zeros)
    $andMask = str_repeat("\x00", $width * $height * 4);
    
    $bmp = $bmpHeader . $bmpData . $andMask;
    
    // Update file size
    $bmp = substr($bmp, 0, 2) . pack('V', strlen($bmp)) . substr($bmp, 6);
    
    return $bmp;
}

// Execute conversion
$pngPath = '../includes/images/mitsubishi_logo.png';
$icoPath = '../favicon.ico';

echo "<h2>PNG to ICO Converter</h2>";
echo "<p>Converting: $pngPath</p>";
echo "<p>To: $icoPath</p>";

$result = pngToIco($pngPath, $icoPath);
echo "<p><strong>Result:</strong> $result</p>";

if (strpos($result, 'Success') !== false) {
    echo "<p style='color: green;'>The favicon.ico file has been created successfully!</p>";
    echo "<p>You can now access it at: <a href='../favicon.ico' target='_blank'>/favicon.ico</a></p>";
} else {
    echo "<p style='color: red;'>Conversion failed. Please check the error message above.</p>";
}

echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
?>