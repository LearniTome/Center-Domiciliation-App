<?php
$z = new ZipArchive();
$gen = __DIR__ . '/../dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Annonce-Legale-Journal_BAATRI_Brouillon.docx';
if ($z->open($gen) === true) {
    echo "Entries: " . $z->numFiles . "\n";
    for ($i = 0; $i < $z->numFiles; $i++) {
        echo $z->getNameIndex($i) . "\n";
    }
    echo "---\n";
    $xml = $z->getFromName('word/document.xml');
    if ($xml) {
        echo "document.xml length: " . strlen($xml) . "\n";
        $text = preg_replace('#<[^>]+>#', ' ', $xml);
        $text = preg_replace('#\s+#', ' ', $text);
        echo substr(trim($text), 0, 2000) . "\n";
    } else {
        echo "word/document.xml NOT FOUND\n";
    }
} else {
    echo "Cannot open zip\n";
}
