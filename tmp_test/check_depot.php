<?php
declare(strict_types=1);
$f = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$zip = new ZipArchive();
if ($zip->open($f) === true) {
    $xml = $zip->getFromName('word/document.xml') ?: $zip->getFromName('word\\document.xml');
    $zip->close();
    $text = preg_replace('#<[^>]+>#', ' ', $xml);
    $text = preg_replace('#\s+#', ' ', $text);
    // Show 500 chars around NOM_ASSOCIE
    $pos = strpos($text, 'NOM_ASSOCIE');
    if ($pos !== false) {
        echo "=== Around NOM_ASSOCIE ===\n";
        echo substr($text, max(0, $pos - 200), 600) . "\n\n";
    }
    $pos2 = strpos($text, 'PRENOM_ASSOCIE');
    if ($pos2 !== false) {
        echo "=== Around PRENOM_ASSOCIE ===\n";
        echo substr($text, max(0, $pos2 - 200), 600) . "\n\n";
    }
    // Show full text first 3000 chars
    echo "=== Full text (first 3000) ===\n";
    echo substr($text, 0, 3000) . "\n";
} else {
    echo "Cannot open\n";
}
