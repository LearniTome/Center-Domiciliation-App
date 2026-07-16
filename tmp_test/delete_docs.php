<?php
declare(strict_types=1);
// Delete old generated files for BAATRI so they get regenerated
$dir = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_creation/2026-05-18_SARL_BAATRI/';
$files = glob($dir . '*.docx');
$deleted = 0;
foreach ($files as $f) {
    if (unlink($f)) {
        echo "Deleted: " . basename($f) . "\n";
        $deleted++;
    }
}
echo "\nTotal deleted: $deleted\n";
