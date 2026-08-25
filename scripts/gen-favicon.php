<?php
$root = dirname(__DIR__);
$src = imagecreatefrompng($root . '/uploads/centre/logo.png');
if (!$src) { echo "Failed to load PNG\n"; exit(1); }

// 16x16
$ico16 = imagecreatetruecolor(16, 16);
imagealphablending($ico16, false);
imagesavealpha($ico16, true);
imagecopyresampled($ico16, $src, 0, 0, 0, 0, 16, 16, imagesx($src), imagesy($src));
imagepng($ico16, $root . '/assets/favicon-16.png');
imagedestroy($ico16);

// 32x32
$ico32 = imagecreatetruecolor(32, 32);
imagealphablending($ico32, false);
imagesavealpha($ico32, true);
imagecopyresampled($ico32, $src, 0, 0, 0, 0, 32, 32, imagesx($src), imagesy($src));
imagepng($ico32, $root . '/assets/favicon-32.png');
imagedestroy($ico32);

// 180x180 (Apple Touch Icon)
$ico180 = imagecreatetruecolor(180, 180);
imagealphablending($ico180, false);
imagesavealpha($ico180, true);
imagecopyresampled($ico180, $src, 0, 0, 0, 0, 180, 180, imagesx($src), imagesy($src));
imagepng($ico180, $root . '/assets/apple-touch-icon.png');
imagedestroy($ico180);

imagedestroy($src);
echo "OK: favicon-16.png, favicon-32.png, apple-touch-icon.png created\n";
