<?php
require __DIR__ . '/src/TemplateAnalyzer.php';
$dir = __DIR__ . '/templates';
$templates = TemplateAnalyzer::scanTemplates($dir);
$analysis = TemplateAnalyzer::analyzeCoverage($templates);
echo 'Total variables: ' . $analysis['summary']['total_variables'] . "\n";
echo 'Couvertes: ' . $analysis['summary']['covered_variables'] . "\n";
echo 'Non couvertes: ' . $analysis['summary']['uncovered_variables'] . "\n";
foreach ($analysis['variables'] as $v) {
    if ($v['coverage'] !== 'couvert') {
        echo '  NON COUVERT: ' . $v['variable'] . ' (' . $v['section'] . ")\n";
    }
}
echo "\nPar template:\n";
foreach ($analysis['templates'] as $tpl) {
    $cov = $tpl['coverage_percent'];
    echo '  ' . $tpl['template'] . ': ' . ($cov === null ? 'N/A' : round($cov, 1) . "%\n");
}
