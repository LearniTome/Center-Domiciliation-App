<?php
/**
 * Generate a multi-size .ico file from the centre logo.
 * ICO format: header (6 bytes) + dir entries (16 bytes each) + PNG data chunks.
 */
$root = dirname(__DIR__);
$srcPath = $root . '/uploads/centre/logo.png';
$icoPath = $root . '/assets/favicon.ico';

$sizes = [16, 32, 48];
$pngData = [];

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $src = imagecreatefrompng($srcPath);
    imagecopyresampled($img, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
    imagedestroy($src);

    ob_start();
    imagepng($img);
    $pngData[$size] = ob_get_clean();
    imagedestroy($img);
}

// ICO header
$ico = pack('vvv', 0, 1, count($sizes)); // reserved, type=1 (ICO), count

$offset = 6 + (count($sizes) * 16); // header + dir entries

foreach ($sizes as $size) {
    $w = $size >= 256 ? 0 : $size; // 0 means 256
    $h = $size >= 256 ? 0 : $size;
    $dataLen = strlen($pngData[$size]);
    $ico .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $dataLen, $offset);
    $offset += $dataLen;
}

foreach ($sizes as $size) {
    $ico .= $pngData[$size];
}

file_put_contents($icoPath, $ico);
echo "OK: favicon.ico created (" . filesize($icoPath) . " bytes) with sizes: " . implode('x', $sizes) . "\n";
