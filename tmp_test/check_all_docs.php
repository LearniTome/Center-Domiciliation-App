<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/editeur_templates.php';

$dir = __DIR__ . '/../dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI';
$files = glob($dir . '/*.docx');
foreach ($files as $f) {
    $name = basename($f);
    $text = TemplateEditor::extractText($f);
    echo "$name: " . strlen($text) . " chars\n";
    if (strlen($text) < 100) {
        echo "  WARNING: suspiciously short!\n";
    }
}
