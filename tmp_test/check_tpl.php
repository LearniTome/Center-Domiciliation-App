<?php
declare(strict_types=1);
$path = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$zip = new ZipArchive();
if ($zip->open($path) === true) {
    echo "Opened OK\n";
    $xml = $zip->getFromName('word/document.xml');
    $text = preg_replace('#<[^>]+>#', ' ', $xml);
    $text = preg_replace('#\s+#', ' ', trim($text));
    echo "Text length: " . strlen($text) . "\n";
    echo "First 500 chars:\n" . substr($text, 0, 500) . "\n";
    // Check for {{ }} vars
    preg_match_all('#\{\{\s*\w+\s*\}\}#', $xml, $vars);
    echo "\nTemplate variables found: " . count($vars[0]) . "\n";
    foreach ($vars[0] as $v) echo "  $v\n";
    $zip->close();
} else {
    echo "Cannot open template\n";
}
