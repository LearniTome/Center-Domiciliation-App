<?php
declare(strict_types=1);
// Deep check: extract ALL text from each docx and search for key words
$base = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/';
$files = glob($base . '*.docx');
foreach ($files as $f) {
    $base_name = basename($f);
    echo "\n=== $base_name ===\n";
    $zip = new ZipArchive();
    if ($zip->open($f) !== true) { echo "  CANNOT OPEN\n"; continue; }
    $fullText = '';
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#\.xml$#', $name)) {
            $data = $zip->getFromIndex($i);
            if ($data) {
                $text = preg_replace('#<[^>]+>#', ' ', $data);
                $text = preg_replace('#\s+#', ' ', $text);
                $fullText .= " $text";
            }
        }
    }
    $zip->close();
    
    // Check for key words
    $checks = ['Gérant', 'Gerant', 'Associé', 'Associe', 'BAATRI', 'NOM_ASSOCIE', 'PRENOM_ASSOCIE', 
               'ASSOCIE_NOM', 'ASSOCIE_PRENOM', 'ASSOCIE_ROLE_LABEL', 'ROLE_LABEL', 'ACTIVITES'];
    foreach ($checks as $word) {
        if (stripos($fullText, $word) !== false) {
            $count = substr_count(strtolower($fullText), strtolower($word));
            echo "  FOUND '$word': ~$count times\n";
        }
    }
}
echo "\n--- Done ---\n";
