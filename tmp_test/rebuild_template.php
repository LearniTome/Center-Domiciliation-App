<?php
declare(strict_types=1);

$templatePath = __DIR__ . '/../templates/_Racine-Actifs/2026-03_Statuts_Template.docx';
$tmpDir = sys_get_temp_dir() . '/docx_fix_' . uniqid();

// Step 1: Extract template properly
echo "Step 1: Extracting template..." . PHP_EOL;
$zip = new ZipArchive();
$res = $zip->open($templatePath);
if ($res !== true) {
    echo "ERROR: Cannot open template (code $res)" . PHP_EOL;
    exit(1);
}
$zip->extractTo($tmpDir);
$zip->close();
echo "  Extracted to: $tmpDir" . PHP_EOL;

// Step 2: Read and verify document.xml
$xmlPath = $tmpDir . '/word/document.xml';
$xml = file_get_contents($xmlPath);
echo "Step 2: document.xml read (" . strlen($xml) . " bytes)" . PHP_EOL;

// Step 3: Check current state of the loop
preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $ts);
foreach ($ts[1] as $t) {
    if (strpos($t, 'for a in') !== false || strpos($t, '{{ a }}') !== false || strpos($t, 'endfor') !== false) {
        echo "  FOUND: $t" . PHP_EOL;
    }
}

// Step 4: Fix - ensure for/endfor are in single <w:t>, body has bullet char
// Fix for tag
$xml = preg_replace(
    '/<w:t[^>]*>\{\%p for a in ACTIVITES_<\/w:t><\/w:r>.*?<w:t[^>]*>LIST %\}<\/w:t>/s',
    '<w:t xml:space="preserve">{%p for a in ACTIVITES_LIST %}</w:t>',
    $xml
);

// Fix endfor tag
$xml = preg_replace(
    '/<w:t[^>]*>\{\%p <\/w:t><\/w:r>.*?<w:t[^>]*>endfor<\/w:t><\/w:r>.*?<w:t[^>*> %\}<\/w:t>/s',
    '<w:t xml:space="preserve">{%p endfor %}</w:t>',
    $xml
);

// Also fix any remaining split endfor
$xml = preg_replace(
    '/<w:t xml:space="preserve">\{\%p <\/w:t><\/w:r>\s*<w:r>.*?<w:t xml:space="preserve">endfor<\/w:t><\/w:r>\s*<w:r>.*?<w:t xml:space="preserve"> %\}<\/w:t>/s',
    '<w:t xml:space="preserve">{%p endfor %}</w:t>',
    $xml
);

// Fix loop body - replace empty paragraph or numPr paragraph with bullet char
$xml = preg_replace(
    '/<w:p><w:pPr><w:pStyle w:val="ListParagraph"\/><w:numPr>.*?<\/w:numPr><w:jc w:val="both"\/><\/w:pPr><w:r>.*?<w:t[^>]*>\{\{ a \}\}<\/w:t>.*?<\/w:p>/s',
    '<w:p><w:pPr><w:jc w:val="both"/></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="fr-FR"/></w:rPr><w:t xml:space="preserve">•  {{ a }}</w:t></w:r></w:p>',
    $xml
);

// Also handle case where body has no text at all
$xml = preg_replace(
    '/(<\{%p for a in ACTIVITES_LIST %\}.*?<\/w:r>)(<w:p><w:pPr><w:jc w:val="both"\/><\/w:pPr><\/w:p>)(<w:r>.*?\{%p endfor)/s',
    '$1<w:p><w:pPr><w:jc w:val="both"/></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="fr-FR"/></w:rPr><w:t xml:space="preserve">•  {{ a }}</w:t></w:r></w:p>$3',
    $xml
);

// Write back
file_put_contents($xmlPath, $xml);
echo "Step 3: XML fixed" . PHP_EOL;

// Verify
preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $ts2);
foreach ($ts2[1] as $t) {
    if (strpos($t, 'for a in') !== false || strpos($t, '{{ a }}') !== false || strpos($t, 'endfor') !== false) {
        echo "  VERIFY: $t" . PHP_EOL;
    }
}

// Step 4: Create new docx from fixed temp dir (clean zip)
$outPath = __DIR__ . '/output/2026-03_Statuts_genere.docx';
echo "Step 4: Creating clean zip..." . PHP_EOL;

// Remove old file if exists
if (file_exists($outPath)) unlink($outPath);

$zip2 = new ZipArchive();
$zip2->open($outPath, ZipArchive::CREATE);
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($files as $file) {
    if ($file->isDir()) continue;
    $relative = substr($file->getPathname(), strlen($tmpDir) + 1);
    $relative = str_replace('\\', '/', $relative);
    $zip2->addFile($file->getPathname(), $relative);
}
$zip2->close();
echo "  Saved: $outPath" . PHP_EOL;

// Cleanup
function rmdirRecursive(string $dir): void {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? rmdirRecursive($path) : unlink($path);
    }
    rmdir($dir);
}
rmdirRecursive($tmpDir);

// Verify output zip
$zip3 = new ZipArchive();
if ($zip3->open($outPath) === true) {
    echo "Step 5: Output zip valid, " . $zip3->numFiles . " files" . PHP_EOL;
    $xml2 = $zip3->getFromName('word/document.xml');
    echo "  document.xml: " . strlen($xml2) . " bytes" . PHP_EOL;
    $zip3->close();
} else {
    echo "ERROR: Output zip invalid!" . PHP_EOL;
}
