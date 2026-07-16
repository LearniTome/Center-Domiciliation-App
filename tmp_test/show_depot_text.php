<?php
declare(strict_types=1);
$path = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Depot-Legal-Constitution_BAATRI_Brouillon.docx';
$zip = new ZipArchive();
$zip->open($path);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (preg_match('#document\.xml$#', $name)) {
        $xml = $zip->getFromIndex($i);
        $text = preg_replace('#<[^>]+>#', ' ', $xml);
        $text = preg_replace('#\s+#', ' ', $text);
        // Show first 1000 chars of rendered text
        echo substr(trim($text), 0, 1500) . "\n";
        echo "\n---\n";
        // Show more
        echo substr(trim($text), 1500, 1500) . "\n";
        break;
    }
}
$zip->close();
