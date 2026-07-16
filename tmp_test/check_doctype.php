<?php
declare(strict_types=1);
// Check what the Depot-Legal template extracts for doc_type
$path = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$filename = pathinfo($path, PATHINFO_FILENAME);
$parts = explode('_', $filename);
echo "Filename: $filename\n";
echo "Parts: " . implode(', ', $parts) . "\n";
echo "Count: " . count($parts) . "\n";
if (count($parts) >= 4) {
    $docType = preg_replace('/_?Template$/i', '', implode('_', array_slice($parts, 2)));
    echo "DocType (>=4): $docType\n";
} elseif (count($parts) === 3) {
    $docType = preg_replace('/_?Template$/i', '', $parts[1]);
    echo "DocType (==3): $docType\n";
}
