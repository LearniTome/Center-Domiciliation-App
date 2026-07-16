<?php
declare(strict_types=1);
$base = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/';
$files = glob($base . '*.docx');
foreach ($files as $f) {
    $base_name = basename($f);
    $size = filesize($f);
    echo "\n=== $base_name ($size bytes) ===\n";
    $zip = new ZipArchive();
    if ($zip->open($f) !== true) { echo "  CANNOT OPEN\n"; continue; }
    // Scan all XML files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#\.xml$#', $name)) {
            $data = $zip->getFromIndex($i);
            if ($data === false) continue;
            // Check for Gerant without accent
            if (preg_match('#Gerant#', $data) && !preg_match('#Gérant#', $data)) {
                echo "  WARNING: '$name' has 'Gerant' without accent\n";
            }
            if (preg_match('#Gérant#', $data)) {
                echo "  OK: '$name' has 'Gérant' with accent\n";
            }
            // Check for unreplaced template vars
            if (preg_match('#{{\s*NOM_ASSOCIE\s*}}#', $data)) echo "  ** NOM_ASSOCIE unreplaced in $name **\n";
            if (preg_match('#{{\s*PRENOM_ASSOCIE\s*}}#', $data)) echo "  ** PRENOM_ASSOCIE unreplaced in $name **\n";
            if (preg_match('#{{\s*ASSOCIE_NOM\s*}}#', $data)) echo "  ASSOCIE_NOM variable present in $name\n";
            if (preg_match('#{{\s*ASSOCIE_PRENOM\s*}}#', $data)) echo "  ASSOCIE_PRENOM variable present in $name\n";
            if (preg_match('#Associé#', $data)) echo "  'Associé' found in $name\n";
            if (preg_match('#Associe#', $data) && !preg_match('#Associé#', $data)) echo "  'Associe' without accent in $name\n";
        }
    }
    $zip->close();
}
echo "\n--- Done ---\n";
