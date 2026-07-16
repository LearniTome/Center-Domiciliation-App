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
        // Extract text around key terms
        if (preg_match('#.{0,50}[Gg]rant.{0,50}#u', $text, $m)) echo "Found: $m[0]\n";
        if (preg_match('#.{0,50}[Aa]ssoci.{0,50}#u', $text, $m)) echo "Found: $m[0]\n";
        // Check all {{ vars }} in the rendered text
        preg_match_all('#\{\{\s*(\w+)\s*\}\}#', $xml, $vars);
        if (!empty($vars[0])) {
            echo "\nUnreplaced template vars:\n";
            foreach ($vars[0] as $v) echo "  $v\n";
        } else {
            echo "\nNo unreplaced template variables\n";
        }
        break;
    }
}
$zip->close();
