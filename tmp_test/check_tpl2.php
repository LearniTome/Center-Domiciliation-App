<?php
declare(strict_types=1);
$path = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Depot-Legal-Constitution_Template.docx';
$zip = new ZipArchive();
if ($zip->open($path) === true) {
    echo "Opened OK, " . $zip->numFiles . " files\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        echo "  $name\n";
        if (preg_match('#document\.xml$#', $name)) {
            $data = $zip->getFromIndex($i);
            echo "  -> read: " . ($data !== false ? strlen($data) . " bytes" : "FAILED") . "\n";
        }
    }
    $zip->close();
}
