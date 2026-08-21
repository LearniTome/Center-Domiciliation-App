<?php

declare(strict_types=1);

/**
 * Conversion ponctuelle : {{ VAR }} -> _VAR_ dans tous les .docx de templates/
 * - Sauvegarde l'original dans backups/conversion_underscore/<timestamp>/
 * - Passe 1 : remplacement brut (tokens intacts dans un seul noeud w:t)
 * - Passe 2 : DOM par paragraphe pour les tokens coupes entre plusieurs runs
 */

$root = dirname(__DIR__);
$templatesDir = $root . '/templates';
$backupBase = $root . '/backups/conversion_underscore/' . date('Ymd_His');

if (!is_dir($templatesDir)) {
    fwrite(STDERR, "Dossier templates introuvable : $templatesDir\n");
    exit(1);
}

$rawPattern = '/\{\{\s*([A-Za-z][A-Za-z0-9.\-_]*)\s*\}\}/u';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templatesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$filesChanged = 0;
$totalReplacements = 0;
$domPassFiles = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'docx') {
        continue;
    }

    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($templatesDir) + 1));

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        echo "ERREUR ouverture : $rel\n";
        continue;
    }

    $parts = ['word/document.xml'];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = $zip->getNameIndex($i);
        if ($n !== false && (str_starts_with($n, 'word/header') || str_starts_with($n, 'word/footer'))) {
            $parts[] = $n;
        }
    }
    $parts = array_values(array_unique($parts));

    $fileReplacements = 0;
    $usedDomPass = false;
    $newContents = [];

    foreach ($parts as $part) {
        $xml = $zip->getFromName($part);
        if ($xml === false) {
            continue;
        }

        // Passe 1 : tokens intacts
        $newXml = preg_replace($rawPattern, '_$1_', $xml, -1, $count);
        $fileReplacements += $count;

        // Passe 2 : tokens coupes entre plusieurs runs (paragraphes contenant encore '{{')
        if (str_contains($newXml, '{{')) {
            $res = convertSplitTokens((string) $newXml);
            if ($res['replacements'] > 0) {
                $newXml = $res['xml'];
                $fileReplacements += $res['replacements'];
                $usedDomPass = true;
            }
        }

        if ($newXml !== $xml) {
            $newContents[$part] = $newXml;
        }
    }

    $zip->close();

    if ($newContents === []) {
        continue;
    }

    // Sauvegarde de l'original avant modification
    $backupPath = $backupBase . '/' . $rel;
    $backupDirPart = dirname($backupPath);
    if (!is_dir($backupDirPart)) {
        mkdir($backupDirPart, 0777, true);
    }
    copy($path, $backupPath);

    // Reecriture des parts modifies
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        echo "ERREUR reouverture : $rel\n";
        continue;
    }
    foreach ($newContents as $part => $content) {
        $zip->deleteName($part);
        $zip->addFromString($part, $content);
    }
    $zip->close();

    $filesChanged++;
    $totalReplacements += $fileReplacements;
    $flag = $usedDomPass ? ' [passe DOM]' : '';
    echo "Converti ($fileReplacements) : $rel$flag\n";
}

echo "\nTermine. $filesChanged fichier(s), $totalReplacements variable(s) converties.\n";
echo "Sauvegardes : $backupBase\n";

/**
 * Passe DOM : fusionne le texte de chaque paragraphe et convertit les {{ VAR }} restants.
 */
function convertSplitTokens(string $xml): array
{
    $doc = new DOMDocument();
    $suppress = libxml_use_internal_errors(true);
    $doc->loadXML($xml);
    libxml_use_internal_errors($suppress);

    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $paragraphs = $xpath->query('//w:p');
    if ($paragraphs === false || $paragraphs->length === 0) {
        return ['xml' => $xml, 'replacements' => 0];
    }

    $total = 0;
    foreach ($paragraphs as $p) {
        $textNodes = $xpath->query('.//w:t', $p);
        if ($textNodes === false || $textNodes->length === 0) {
            continue;
        }

        $combined = '';
        foreach ($textNodes as $tn) {
            $combined .= $tn->textContent;
        }
        if (!str_contains($combined, '{{')) {
            continue;
        }

        $newCombined = preg_replace_callback(
            '/\{\{\s*([^}<>]+?)\s*\}\}/u',
            static fn(array $m): string => '_' . trim($m[1]) . '_',
            $combined,
            -1,
            $count
        );
        if ($count === 0 || $newCombined === null || $newCombined === $combined) {
            continue;
        }

        $total += $count;
        $textNodes->item(0)->textContent = $newCombined;
        for ($i = 1; $i < $textNodes->length; $i++) {
            $textNodes->item($i)->textContent = '';
        }
    }

    if ($total === 0) {
        return ['xml' => $xml, 'replacements' => 0];
    }

    $out = $doc->saveXML();

    return ['xml' => ($out !== false ? $out : $xml), 'replacements' => $total];
}
