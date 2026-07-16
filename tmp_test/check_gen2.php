<?php
$z = new ZipArchive();
$gen = __DIR__ . '/../dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Annonce-Legale-Journal_BAATRI_Brouillon.docx';
$tpl = __DIR__ . '/../templates/SARL/SARL_2026-07_Annonce-Legale-Journal_Template.docx';

echo "=== TEMPLATE ===\n";
$tplZip = new ZipArchive();
if ($tplZip->open($tpl) === true) {
    $xml = $tplZip->getFromName('word/document.xml');
    if ($xml) {
        $text = preg_replace('#<[^>]+>#', ' ', $xml);
        $text = preg_replace('#\s+#', ' ', $text);
        echo "Template text length: " . strlen(trim($text)) . "\n";
        echo substr(trim($text), 0, 2000) . "\n";
    }
}

echo "\n=== GENERATED (backslash) ===\n";
if ($z->open($gen) === true) {
    $xml = $z->getFromName('word/document.xml');
    if ($xml) {
        echo "Found with backslash: " . strlen($xml) . "\n";
    } else {
        echo "Not found with backslash\n";
    }
    $xml = $z->getFromName('word\\document.xml');
    if ($xml) {
        echo "Found with backslash-sep: " . strlen($xml) . "\n";
        $text = preg_replace('#<[^>]+>#', ' ', $xml);
        $text = preg_replace('#\s+#', ' ', $text);
        echo "Generated text length: " . strlen(trim($text)) . "\n";
        echo substr(trim($text), 0, 2000) . "\n";
    } else {
        echo "Not found with backslash-sep either\n";
    }
}
