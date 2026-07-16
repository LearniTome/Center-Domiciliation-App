<?php
declare(strict_types=1);
$dir = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_creation/2026-05-18_SARL_BAATRI/';
$files = glob($dir . '*.docx');
foreach ($files as $f) {
    $base = basename($f);
    echo "\n=== $base ===\n";
    $zip = new ZipArchive();
    if ($zip->open($f) !== true) { echo "  SKIP\n"; continue; }
    $docEntry = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'document.xml') !== false) { $docEntry = $name; break; }
    }
    if (!$docEntry) { $zip->close(); echo "  No XML\n"; continue; }
    $data = $zip->getFromIndex(array_search($docEntry, array_map(fn($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1))));
    $zip->close();
    if (!$data) { echo "  Cannot read\n"; continue; }
    $text = preg_replace('#<[^>]+>#', ' ', $data);
    $text = preg_replace('#\s+#', ' ', $text);
    // Check for Gérant (with accent) vs Gerant (without)
    $hasAccent = (strpos($text, 'rant') !== false);
    if ($hasAccent) {
        preg_match_all('#.{0,40}rant.{0,40}#', $text, $m);
        foreach (array_unique($m[0]) as $match) {
            echo "  " . trim($match) . "\n";
        }
    }
    // Check for NOM_ASSOCIE/PRENOM_ASSOCIE (should be gone)
    if (strpos($text, 'NOM_ASSOCIE') !== false || strpos($text, 'PRENOM_ASSOCIE') !== false) {
        echo "  ** STILL HAS NOM_ASSOCIE/PRENOM_ASSOCIE **\n";
    }
}
