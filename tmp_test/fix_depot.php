<?php
declare(strict_types=1);

$srcPath = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$tmpDir = sys_get_temp_dir() . '/docx_fix_dl2';

// Clean temp dir
if (is_dir($tmpDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
    rmdir($tmpDir);
}
mkdir($tmpDir, 0777, true);

// Restore from git first (previous file is corrupted)
$z1 = new ZipArchive();
$r = $z1->open($srcPath);
if ($r !== true) { echo "Cannot open ($r): $srcPath\n"; exit(1); }
$z1->extractTo($tmpDir);
$z1->close();
echo "Extracted to $tmpDir\n";

// Fix document.xml - replace LONGER strings first
$docFile = $tmpDir . '/word/document.xml';
$xml = file_get_contents($docFile);

// Fix the corrupted PRE{{ ASSOCIE_NOM }} back to PRENOM_ASSOCIE first
$xml = str_replace('PRE{{ ASSOCIE_NOM }}', 'PRENOM_ASSOCIE', $xml);
$xml = str_replace('{{ ASSOCIE_NOM }}', 'NOM_ASSOCIE', $xml);

echo "Reverted to original\n";

// Now apply in correct order (longer first)
$count1 = substr_count($xml, 'PRENOM_ASSOCIE');
$count2 = substr_count($xml, 'NOM_ASSOCIE');
echo "Before: $count1 PRENOM_ASSOCIE, $count2 NOM_ASSOCIE\n";

$xml = str_replace('PRENOM_ASSOCIE', '{{ ASSOCIE_PRENOM }}', $xml);
$xml = str_replace('NOM_ASSOCIE', '{{ ASSOCIE_NOM }}', $xml);

$count1after = substr_count($xml, 'ASSOCIE_PRENOM');
$count2after = substr_count($xml, 'ASSOCIE_NOM');
echo "After: $count1after ASSOCIE_PRENOM, $count2after ASSOCIE_NOM\n";

file_put_contents($docFile, $xml);

// Repack
unlink($srcPath);
$z2 = new ZipArchive();
$r2 = $z2->open($srcPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($r2 !== true) { echo "Cannot create ($r2)\n"; exit(1); }

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);
foreach ($iterator as $file) {
    $rel = str_replace($tmpDir . '/', '', str_replace('\\', '/', $file->getPathname()));
    $z2->addFile($file->getPathname(), $rel);
}
$z2->close();

echo "Saved: $srcPath (" . filesize($srcPath) . " bytes)\n";
