<?php
declare(strict_types=1);
// Check docx files in all BAATRI dirs
$base = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/';
$dirs = glob($base . '*', GLOB_ONLYDIR);
foreach ($dirs as $d) {
    $subdirs = glob($d . '*', GLOB_ONLYDIR);
    foreach ($subdirs as $sd) {
        if (stripos($sd, 'BAATRI') === false) continue;
        $files = glob($sd . '/*.docx');
        if (empty($files)) { echo "EMPTY: $sd\n"; continue; }
        foreach ($files as $f) {
            echo "\n=== " . basename($f) . " ($sd) ===\n";
            $zip = new ZipArchive();
            if ($zip->open($f) !== true) { echo "  CANNOT OPEN\n"; continue; }
            $docEntry = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'document.xml') !== false) { $docEntry = $name; break; }
            }
            if (!$docEntry) { $zip->close(); echo "  No XML\n"; continue; }
            $data = $zip->getFromName($docEntry);
            $zip->close();
            $text = preg_replace('#<[^>]+>#', ' ', $data);
            $text = preg_replace('#\s+#', ' ', $text);
            $ga = preg_match_all('#.{0,30}[Gg]\x{00e9}rant.{0,30}#u', $text, $m1);
            $gna = preg_match_all('#.{0,30}Gerant.{0,30}#', $text, $m2);
            if ($ga) echo "  Gérant: " . count($m1[0]) . " times\n";
            if ($gna) echo "  Gerant (no accent): " . count($m2[0]) . " times\n";
            if (strpos($data, 'NOM_ASSOCIE') !== false) echo "  ** HAS NOM_ASSOCIE **\n";
            if (strpos($data, 'PRENOM_ASSOCIE') !== false) echo "  ** HAS PRENOM_ASSOCIE **\n";
        }
    }
}
echo "\n--- Done ---\n";
