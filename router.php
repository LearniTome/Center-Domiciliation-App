<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'webp', 'pdf', 'zip', 'docx'];

if (in_array($ext, $staticExtensions, true)) {
    $filePath = __DIR__ . urldecode($uri);
    if (file_exists($filePath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($filePath);
        return true;
    }
}


require __DIR__ . '/index.php';
return true;
