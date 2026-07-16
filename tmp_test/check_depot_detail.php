<?php
declare(strict_types=1);
$path = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Depot-Legal-Constitution_BAATRI_Brouillon.docx';
$zip = new ZipArchive();
$zip->open($path);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (preg_match('#document\.xml$#', $name)) {
        $xml = $zip->getFromIndex($i);
        // Search for Gerant/Gérant with context
        preg_match_all('#.{0,40}[Gg]rant.{0,40}#u', $xml, $m);
        echo "Contexts around 'grant':\n";
        foreach ($m[0] as $ctx) {
            // Strip tags
            $clean = preg_replace('#<[^>]+>#', '', $ctx);
            echo "  -> $clean\n";
        }
        // Check for raw template vars
        if (strpos($xml, 'NOM_ASSOCIE') !== false) echo "\n** HAS NOM_ASSOCIE (unreplaced) **\n";
        if (strpos($xml, 'PRENOM_ASSOCIE') !== false) echo "\n** HAS PRENOM_ASSOCIE (unreplaced) **\n";
        break;
    }
}
$zip->close();
