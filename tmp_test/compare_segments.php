<?php
declare(strict_types=1);
function extractRawText($path) {
    $zip = new ZipArchive();
    $zip->open($path);
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'document.xml') !== false && strpos($name, 'rels') === false) {
            $xml = $zip->getFromIndex($i);
            break;
        }
    }
    $zip->close();
    return $xml;
}

function getCleanText($xml) {
    // Use DOMDocument to properly extract text from <w:t> elements
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xp = new DOMXPath($dom);
    $texts = $xp->query('//w:t');
    $result = [];
    foreach ($texts as $t) {
        $result[] = $t->textContent;
    }
    return $result;
}

function printWithPositions($segments, $label) {
    echo "=== $label ===\n";
    $pos = 0;
    foreach ($segments as $i => $seg) {
        echo sprintf("[%3d] (%3d-%3d) '%s'\n", $i, $pos, $pos + strlen($seg) - 1, $seg);
        $pos += strlen($seg);
    }
    echo "\n";
}

$tpl = 'E:/Dev_Project/Center-Domiciliation-App/templates/SARL/SARL_2026-07_Annonce-Legale-Journal_Template.docx';
$doc = 'E:/Dev_Project/Center-Domiciliation-App/dossiers_generer/dossiers_domiciliation/2026-07-16_SARL_BAATRI/SARL_2026-07-16_Annonce-Legale-Journal_BAATRI.docx';

$tplXml = extractRawText($tpl);
$docXml = extractRawText($doc);

$tplSegs = getCleanText($tplXml);
$docSegs = getCleanText($docXml);

printWithPositions($tplSegs, "TEMPLATE segments");
printWithPositions($docSegs, "GENERATED segments");

// Compare segment by segment
echo "=== COMPARISON ===\n";
$max = max(count($tplSegs), count($docSegs));
$tplTotal = 0;
$docTotal = 0;
for ($i = 0; $i < $max; $i++) {
    $ts = $tplSegs[$i] ?? '(missing)';
    $ds = $docSegs[$i] ?? '(missing)';
    $tplTotal += strlen($ts);
    $docTotal += strlen($ds);
    if ($ts !== $ds) {
        // Find the diff
        $match = '';
        $minLen = min(strlen($ts), strlen($ds));
        for ($j = 0; $j < $minLen; $j++) {
            if ($ts[$j] === $ds[$j]) $match .= $ts[$j];
            else break;
        }
        echo sprintf("[%3d] TPL: '%s'\n", $i, $ts);
        echo sprintf("     DOC: '%s'\n", $ds);
        if (strlen($match) > 0 && strlen($match) < max(strlen($ts), strlen($ds))) {
            echo sprintf("     DIFF at pos %d: TPL has '%s' but DOC has '%s'\n", strlen($match), substr($ts, strlen($match), 20), substr($ds, strlen($match), 20));
        }
        echo "\n";
    }
}
echo "TPL total chars: $tplTotal\n";
echo "DOC total chars: $docTotal\n";
echo "Difference: " . ($tplTotal - $docTotal) . " chars missing from DOC\n";
