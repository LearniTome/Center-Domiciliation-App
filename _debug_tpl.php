<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$templatePath = 'D:/SSD_2T/04_Dev/05_Programming Projects/PHP_Projects/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Statuts_Template.docx';
$tmpDir = 'D:/SSD_2T/04_Dev/05_Programming Projects/PHP_Projects/Center-Domiciliation-App/_debug_output/_tpl';
if (is_dir($tmpDir)) {
    $it = new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        if ($f->isDir()) @rmdir($f->getPathname()); else @unlink($f->getPathname());
    }
    @rmdir($tmpDir);
}

$zip = new ZipArchive();
$zip->open($templatePath);
$zip->extractTo($tmpDir);
$zip->close();

$xml = file_get_contents($tmpDir . '/word/document.xml');

// Find all {{ and show context
preg_match_all('/\{\{/', $xml, $m, PREG_OFFSET_CAPTURE);
echo "Found " . count($m[0]) . " opening {{ \n\n";

foreach ($m[0] as $match) {
    $pos = $match[1];
    $chunk = substr($xml, $pos, 200);
    echo "--- At offset $pos ---\n";
    echo $chunk . "\n\n";
}

// Also find all }} and show context
preg_match_all('/\}\}/', $xml, $m2, PREG_OFFSET_CAPTURE);
echo "\nFound " . count($m2[0]) . " closing }} \n\n";
foreach ($m2[0] as $match) {
    $pos = $match[1];
    $start = max(0, $pos - 80);
    $chunk = substr($xml, $start, 160);
    echo "--- }} at offset $pos ---\n";
    echo $chunk . "\n\n";
}

// Cleanup
$it = new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS);
foreach (new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST) as $f) {
    if ($f->isDir()) @rmdir($f->getPathname()); else @unlink($f->getPathname());
}
@rmdir($tmpDir);
