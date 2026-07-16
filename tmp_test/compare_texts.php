<?php
declare(strict_types=1);
function extractText($path) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return "CANNOT OPEN: $path";
    $xml = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'document.xml') !== false && strpos($name, 'rels') === false) {
            $xml = $zip->getFromIndex($i);
            break;
        }
    }
    $zip->close();
    if (!$xml) return "NO XML found in $path";
    preg_match_all('#<w:t[^>]*>(.*?)</w:t>#s', $xml, $matches);
    return implode('', $matches[1]);
}

$tpl = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Annonce-Legale-Journal_Template.docx';
$doc = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Annonce-Legale-Journal_BAATRI.docx';

$tplText = extractText($tpl);
$docText = extractText($doc);

echo "=== TEMPLATE TEXT ===\n$tplText\n\n";
echo "=== GENERATED TEXT ===\n$docText\n";
