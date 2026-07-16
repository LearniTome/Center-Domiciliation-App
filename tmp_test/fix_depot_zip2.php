<?php
declare(strict_types=1);
require_once 'E:/Dev_Project/Center-Domiciliation-App/src/analyseur_templates.php';

$templatePath = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$tmpDir = sys_get_temp_dir() . '/docx_fix_flat_' . uniqid();
mkdir($tmpDir, 0777, true);

// Extract
$zip = new ZipArchive();
$zip->open($templatePath);
$zip->extractTo($tmpDir);
$zip->close();

// Find the nested path prefix by looking for word/document.xml
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS));
$prefix = '';
foreach ($iterator as $f) {
    $rel = str_replace($tmpDir . DIRECTORY_SEPARATOR, '', str_replace('\\', '/', $f->getPathname()));
    if (str_ends_with($rel, 'word/document.xml')) {
        $prefix = str_replace('word/document.xml', '', $rel);
        break;
    }
}
echo "Prefix to strip: $prefix\n";

// Rebuild zip with flattened paths
unlink($templatePath);
$newZip = new ZipArchive();
$newZip->open($templatePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$iterator2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iterator2 as $f) {
    if (!$f->isFile()) continue;
    $fullPath = str_replace('\\', '/', $f->getPathname());
    $relPath = str_replace($tmpDir . '/', '', $fullPath);
    $cleanPath = str_replace($prefix, '', $relPath);
    echo "  $relPath -> $cleanPath\n";
    $newZip->addFile($f->getPathname(), $cleanPath);
}
$newZip->close();
echo "\nRebuilt template with flattened paths\n";

// Verify
$tplVars = TemplateAnalyzer::extractVariables($templatePath);
echo "Variables: " . count($tplVars) . "\n";
foreach ($tplVars as $v) echo "  $v\n";

// Cleanup
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $ff) { $ff->isDir() ? rmdir($ff->getPathname()) : unlink($ff->getPathname()); }
rmdir($tmpDir);
