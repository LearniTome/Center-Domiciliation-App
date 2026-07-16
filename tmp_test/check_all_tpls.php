<?php
declare(strict_types=1);
// The Depot-Legal template has zip entries with full temp paths
// This means getFromName('word/document.xml') FAILS silently
// Let's check the templateAnalyzer extraction for ALL SARL templates
require_once 'E:/Dev_Project/Center-Domiciliation-App/src/analyseur_templates.php';

$tplDir = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/';
$tpls = glob($tplDir . '*.docx');
foreach ($tpls as $t) {
    echo "\n=== " . basename($t) . " ===\n";
    $vars = TemplateAnalyzer::extractVariables($t);
    echo "  Variables: " . count($vars) . "\n";
    if (count($vars) > 0) {
        foreach ($vars as $v) echo "    $v\n";
    }
    
    // Also check zip entry names for word/document.xml
    $zip = new ZipArchive();
    if ($zip->open($t) === true) {
        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === 'word/document.xml') { $found = true; break; }
        }
        echo "  word/document.xml entry: " . ($found ? 'YES' : 'NO') . "\n";
        if (!$found) {
            // Show first few entries
            for ($i = 0; $i < min(3, $zip->numFiles); $i++) {
                echo "    Entry $i: " . $zip->getNameIndex($i) . "\n";
            }
        }
        $zip->close();
    }
}
