<?php

declare(strict_types=1);

final class DocxFixture
{
    public static function create(string $path, string ...$paragraphTexts): void
    {
        $paragraphs = '';
        foreach ($paragraphTexts as $text) {
            $paragraphs .= '<w:p><w:r><w:t xml:space="preserve">'
                . htmlspecialchars($text, ENT_XML1)
                . '</w:t></w:r></w:p>';
        }
        self::createRaw($path, self::wrapBody($paragraphs));
    }

    public static function createRaw(string $path, string $documentXml): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Impossible de creer le fixture : {$path}");
        }
        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();
    }

    public static function wrapBody(string $innerXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $innerXml . '<w:sectPr/></w:body></w:document>';
    }

    public static function paragraph(string ...$runTexts): string
    {
        $runs = '';
        foreach ($runTexts as $text) {
            $runs .= '<w:r><w:t xml:space="preserve">'
                . htmlspecialchars($text, ENT_XML1)
                . '</w:t></w:r>';
        }
        return '<w:p>' . $runs . '</w:p>';
    }

    public static function readDocumentXml(string $docxPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException("Impossible de lire : {$docxPath}");
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new RuntimeException('word/document.xml introuvable dans : ' . $docxPath);
        }
        return $xml;
    }

    public static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';
    }
}
