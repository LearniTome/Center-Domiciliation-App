<?php
declare(strict_types=1);
$f = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$zip = new ZipArchive();
if ($zip->open($f) !== true) { echo "Cannot open\n"; exit(1); }
$xml = null;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, 'document.xml') !== false) { $xml = $zip->getFromIndex($i); break; }
}
$zip->close();
if (!$xml) { echo "No document.xml\n"; exit(1); }
$clean = preg_replace('#<[^>]+>#', ' ', $xml);
preg_match_all('#\{\{\s*([A-Za-z_.]+)\s*\}\}#', $clean, $m);
echo "Variables: " . implode(', ', array_unique($m[1])) . "\n\n";
echo "NOM_ASSOCIE remaining: " . ((strpos($xml, 'NOM_ASSOCIE') !== false) ? 'YES (BAD)' : 'NO (GOOD)') . "\n";
echo "PRENOM_ASSOCIE remaining: " . ((strpos($xml, 'PRENOM_ASSOCIE') !== false) ? 'YES (BAD)' : 'NO (GOOD)') . "\n";
