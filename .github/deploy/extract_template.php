<?php
/**
 * Extracteur de deploiement (temporaire, auto-suppression).
 * Genere par le workflow GitHub Actions avec un token unique par run.
 */
declare(strict_types=1);

set_time_limit(0);

const TOKEN = '__TOKEN__';

if (!isset($_POST['token']) || !hash_equals(TOKEN, (string) $_POST['token'])) {
    http_response_code(403);
    exit('FORBIDDEN');
}

$zipFile = __DIR__ . '/app.zip';
if (!is_file($zipFile)) {
    http_response_code(500);
    exit('EXTRACT_FAIL_NO_ZIP');
}

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    http_response_code(500);
    exit('EXTRACT_FAIL_OPEN');
}
if (!$zip->extractTo(__DIR__)) {
    $zip->close();
    http_response_code(500);
    exit('EXTRACT_FAIL_EXTRACT');
}
$zip->close();

unlink($zipFile);
@unlink(__FILE__);

echo 'EXTRACT_OK';
