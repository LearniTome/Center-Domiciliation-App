<?php
declare(strict_types=1);
$f = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$zip = new ZipArchive();
$zip->open($f);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, 'document.xml') !== false) { $xml = $zip->getFromIndex($i); break; }
}
$zip->close();
// Check both in raw XML and stripped text
echo "Raw XML has NOM_ASSOCIE: " . ((strpos($xml, 'NOM_ASSOCIE') !== false) ? 'YES' : 'NO') . "\n";
echo "Raw XML has PRENOM_ASSOCIE: " . ((strpos($xml, 'PRENOM_ASSOCIE') !== false) ? 'YES' : 'NO') . "\n";
echo "Raw XML has ASSOCIE_NOM: " . ((strpos($xml, 'ASSOCIE_NOM') !== false) ? 'YES' : 'NO') . "\n";
echo "Raw XML has ASSOCIE_PRENOM: " . ((strpos($xml, 'ASSOCIE_PRENOM') !== false) ? 'YES' : 'NO') . "\n";
echo "Raw XML has ASSOCIE NOM (space): " . ((strpos($xml, 'ASSOCIE NOM') !== false) ? 'YES' : 'NO') . "\n";
echo "Raw XML has ASSOCIE PRENOM (space): " . ((strpos($xml, 'ASSOCIE PRENOM') !== false) ? 'YES' : 'NO') . "\n";
// Show context around ASSOCIE_NOM
$pos = strpos($xml, 'ASSOCIE_NOM');
if ($pos !== false) echo "\nAround ASSOCIE_NOM: " . substr($xml, max(0, $pos - 50), 150) . "\n";
$pos2 = strpos($xml, 'ASSOCIE_PRENOM');
if ($pos2 !== false) echo "\nAround ASSOCIE_PRENOM: " . substr($xml, max(0, $pos2 - 50), 150) . "\n";
// Also check for PRENOM
$pos3 = strpos($xml, 'PRENOM');
if ($pos3 !== false) echo "\nAround PRENOM: " . substr($xml, max(0, $pos3 - 80), 200) . "\n";
