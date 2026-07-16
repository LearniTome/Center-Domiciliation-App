<?php
declare(strict_types=1);
// Fix the Depot-Legal template zip structure
// Strategy: extract all files to a clean temp dir, then re-zip with relative paths
$templatePath = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$backupPath = 'E:/Dev_Project/Center-Domiciliation-App/tmp_test/backup_Depot-Legal_Template.docx';
$tmpDir = sys_get_temp_dir() . '/docx_fix_dl_clean_' . uniqid();

// Backup original
copy($templatePath, $backupPath);
echo "Backup created: $backupPath\n";

// Extract to temp
$zip = new ZipArchive();
$zip->open($templatePath);
mkdir($tmpDir, 0777, true);
$zip->extractTo($tmpDir);
$zip->close();
echo "Extracted to: $tmpDir\n";

// List extracted files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir));
$files = [];
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $relative = str_replace($tmpDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relative = str_replace('\\', '/', $relative);
        $files[] = $relative;
        echo "  $relative\n";
    }
}

// Re-create zip with clean relative paths
unlink($templatePath);
$newZip = new ZipArchive();
$newZip->open($templatePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
foreach ($files as $relPath) {
    $fullPath = $tmpDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
    $newZip->addFile($fullPath, $relPath);
}
$newZip->close();
echo "\nRebuilt template with clean paths\n";

// Verify
$verify = new ZipArchive();
$verify->open($templatePath);
echo "New zip has " . $verify->numFiles . " entries:\n";
for ($i = 0; $i < $verify->numFiles; $i++) {
    echo "  " . $verify->getNameIndex($i) . "\n";
}
$verify->close();

// Cleanup temp dir
$iterator2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($iterator2 as $f) {
    $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
}
rmdir($tmpDir);
echo "\nTemp cleaned up\n";

// Test extraction
$tplVars = TemplateAnalyzer::extractVariables($templatePath);
echo "\nVariables after fix: " . count($tplVars) . "\n";
foreach ($tplVars as $v) echo "  $v\n";
