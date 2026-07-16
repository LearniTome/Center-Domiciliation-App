<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/analyseur_templates.php';
require_once __DIR__ . '/../src/editeur_templates.php';

$tpl = __DIR__ . '/../templates/SARL/SARL_2026-07_Annonce-Legale-Journal_Template.docx';
$gen = __DIR__ . '/../dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Annonce-Legale-Journal_BAATRI_Brouillon.docx';

$tplText = TemplateEditor::extractText($tpl);
$genText = TemplateEditor::extractText($gen);

echo "Template: " . strlen($tplText) . " chars\n";
echo "Generated: " . strlen($genText) . " chars\n";
echo "Diff: " . (strlen($genText) - strlen($tplText)) . " chars\n\n";

$checks = [
    'Dhs divisé en',
    'parts sociales de',
    'DH chacune',
    'attribuées aux associés',
    'SARL AU',
    '100 000',
    'Acte',
    'Annonce',
    'Domiciliation',
];
foreach ($checks as $phrase) {
    $tplFound = stripos($tplText, $phrase) !== false;
    $genFound = stripos($genText, $phrase) !== false;
    $status = ($tplFound === $genFound) ? 'OK' : 'MISSING';
    echo "[$status] '$phrase' - TPL:" . ($tplFound ? 'YES' : 'NO') . " GEN:" . ($genFound ? 'YES' : 'NO') . "\n";
}

echo "\n--- Generated full text (first 3000 chars) ---\n";
echo substr($genText, 0, 3000) . "\n";
