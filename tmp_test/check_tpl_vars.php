<?php
declare(strict_types=1);
$path = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$zip = new ZipArchive();
$zip->open($path);
$docXml = false;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (preg_match('#document\.xml$#', $name)) {
        $docXml = $zip->getFromIndex($i);
        break;
    }
}
$zip->close();

if (!$docXml) { echo "No document.xml found\n"; exit; }
echo "docXml length: " . strlen($docXml) . "\n";

// Find all {{ ... }} variables
preg_match_all('#\{\{[^}]*\}\}#', $docXml, $vars);
echo "Variables found: " . count($vars[0]) . "\n";
foreach ($vars[0] as $v) echo "  $v\n";

// Check for remaining unreplaced template syntax
if (preg_match('#NOM_ASSOCIE#', $docXml)) echo "\n** STILL HAS NOM_ASSOCIE **\n";
if (preg_match('#PRENOM_ASSOCIE#', $docXml)) echo "\n** STILL HAS PRENOM_ASSOCIE **\n";
if (preg_match('#ASSOCIE_NOM#', $docXml)) echo "\nHas ASSOCIE_NOM\n";
if (preg_match('#ASSOCIE_PRENOM#', $docXml)) echo "\nHas ASSOCIE_PRENOM\n";
