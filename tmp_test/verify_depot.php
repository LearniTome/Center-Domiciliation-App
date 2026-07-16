<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

$path = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$tmpDir = sys_get_temp_dir() . '/docx_fix_depot';

// Extract
$zip = new ZipArchive();
if ($zip->open($path) !== true) { echo "Cannot open: $path\n"; exit(1); }
$zip->extractTo($tmpDir);
$zip->close();

$docPath = $tmpDir . '/word/document.xml';
$xml = file_get_contents($docPath);

// Verify replacements already applied
$hasNomAssocie = (strpos($xml, 'NOM_ASSOCIE') !== false);
$hasPrenomAssocie = (strpos($xml, 'PRENOM_ASSOCIE') !== false);
echo "NOM_ASSOCIE still present: " . ($hasNomAssocie ? 'YES (need re-fix)' : 'NO (fixed)') . "\n";
echo "PRENOM_ASSOCIE still present: " . ($hasPrenomAssocie ? 'YES (need re-fix)' : 'NO (fixed)') . "\n";

// Check that {{ ASSOCIE_NOM }} is there
$hasNewNom = (strpos($xml, '{{ ASSOCIE_NOM }}') !== false);
$hasNewPrenom = (strpos($xml, '{{ ASSOCIE_PRENOM }}') !== false);
echo "{{ ASSOCIE_NOM }} present: " . ($hasNewNom ? 'YES' : 'NO') . "\n";
echo "{{ ASSOCIE_PRENOM }} present: " . ($hasNewPrenom ? 'YES' : 'NO') . "\n";

// Cleanup extracted dir
foreach (glob("$tmpDir/**/*") as $f) { if (is_file($f)) unlink($f); }
rmdir($tmpDir);
echo "Cleanup done.\n";
