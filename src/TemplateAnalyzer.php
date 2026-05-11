<?php

declare(strict_types=1);

class TemplateAnalyzer
{
    public static function extractVariables(string $docxPath): array
    {
        if (!file_exists($docxPath)) {
            return [];
        }

        $xml = self::readDocxXml($docxPath);
        if ($xml === null) {
            return [];
        }

        $text = strip_tags($xml);

        preg_match_all('/\{\{(.*?)\}\}/', $text, $matches);

        $variables = array_map('trim', $matches[1]);
        $variables = array_filter($variables, static fn (string $v): bool => $v !== '');
        $variables = array_unique($variables);
        sort($variables);

        return array_values($variables);
    }

    private static function readDocxXml(string $path): ?string
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml');
                if ($xml === false) {
                    $xml = $zip->getFromName('document.xml');
                }
                $zip->close();
                if ($xml !== false && $xml !== null) {
                    return $xml;
                }
            }
        }

        return self::readDocxXmlFallback($path);
    }

    private static function readDocxXmlFallback(string $path): ?string
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docx_' . uniqid();
        $xml = null;

        if (self::isCommandAvailable('unzip')) {
            $escapedPath = escapeshellarg($path);
            $escapedDir = escapeshellarg($tmpDir);
            mkdir($tmpDir, 0777, true);
            $output = shell_exec("unzip -o {$escapedPath} word/document.xml -d {$escapedDir} 2>&1");
            $xmlFile = $tmpDir . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'document.xml';
            if (file_exists($xmlFile)) {
                $xml = file_get_contents($xmlFile);
            }
            self::rmdirRecursive($tmpDir);
            if ($xml !== false && $xml !== null) {
                return $xml;
            }
        }

        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }

        $centralDir = self::findCentralDir($fh);
        if ($centralDir === null) {
            fclose($fh);
            return null;
        }

        $entries = self::parseCentralDir($fh, $centralDir);
        fclose($fh);

        $targetEntries = ['word/document.xml', 'document.xml'];
        foreach ($targetEntries as $entry) {
            if (isset($entries[$entry])) {
                $e = $entries[$entry];
                $fh = fopen($path, 'rb');
                if ($fh === false) continue;
                fseek($fh, $e['offset']);
                $data = self::inflateLocalEntry($fh, $e['compressed']);
                fclose($fh);
                if ($data !== null) {
                    return $data;
                }
            }
        }

        return null;
    }

    private static function isCommandAvailable(string $command): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $where = shell_exec("where {$command} 2>NUL");
            return $where !== null && $where !== '';
        }
        $which = shell_exec("which {$command} 2>/dev/null");
        return $which !== null && $which !== '';
    }

    private static function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::rmdirRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private static function findCentralDir($fh): ?array
    {
        $size = fstat($fh)['size'];
        $eocd = '';
        $pos = max(0, $size - 65557);
        fseek($fh, $pos);
        $data = fread($fh, $size - $pos);

        $sig = "\x50\x4b\x05\x06";
        $offset = strrpos($data, $sig);
        if ($offset === false) return null;

        $eocd = substr($data, $offset);
        if (strlen($eocd) < 22) return null;

        $unpack = unpack('vdisk/vdiskStart/vdiskEntries/vtotalEntries/VcentralSize/VcentralOffset/vcommentLen', substr($eocd, 4));

        return [
            'count' => $unpack['totalEntries'],
            'size' => $unpack['centralSize'],
            'offset' => $unpack['centralOffset'],
        ];
    }

    private static function parseCentralDir($fh, array $central): array
    {
        fseek($fh, $central['offset']);
        $data = fread($fh, $central['size']);
        $entries = [];
        $pos = 0;

        while ($pos < strlen($data)) {
            if (substr($data, $pos, 4) !== "\x50\x4b\x01\x02") break;
            $unpack = unpack('vmadeBy/vversion/vflags/vmethod/vmtime/vmdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen/vcommentLen/vdiskStart/vattrInternal/VattrExternal/VlocalOffset', substr($data, $pos + 4, 42));
            $name = substr($data, $pos + 46, $unpack['nameLen']);
            $entries[$name] = [
                'method' => $unpack['method'],
                'compressed' => $unpack['compressed'],
                'uncompressed' => $unpack['uncompressed'],
                'offset' => $unpack['localOffset'],
            ];
            $pos += 46 + $unpack['nameLen'] + $unpack['extraLen'] + $unpack['commentLen'];
        }

        return $entries;
    }

    private static function inflateLocalEntry($fh, ?int $knownCompressedSize = null): ?string
    {
        $sig = fread($fh, 4);
        if ($sig !== "\x50\x4b\x03\x04") return null;

        $unpack = unpack('vversion/vflags/vmethod/vmtime/vmdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen', fread($fh, 26));
        $name = fread($fh, $unpack['nameLen']);
        $extra = $unpack['extraLen'] > 0 ? fread($fh, $unpack['extraLen']) : '';

        $compressedSize = $knownCompressedSize ?? $unpack['compressed'];
        $uncompressedSize = $unpack['uncompressed'];

        if ($unpack['method'] === 0) {
            return fread($fh, $uncompressedSize > 0 ? $uncompressedSize : 8192);
        }

        if ($unpack['method'] === 8) {
            $data = $compressedSize > 0 ? fread($fh, $compressedSize) : fread($fh, $uncompressedSize);
            if ($data === false || strlen($data) === 0) {
                return null;
            }

            $inflated = @gzinflate($data);
            if ($inflated !== false) {
                return $inflated;
            }

            $inflated = @gzuncompress($data);
            return $inflated !== false ? $inflated : null;
        }

        return null;
    }

    public static function extractTemplateInfo(string $filePath): array
    {
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $folder = basename(dirname($filePath));

        $parts = explode('_', $filename);
        $prefix = count($parts) > 1 ? $parts[0] : '';
        $datePart = '';
        $docType = '';

        if (count($parts) >= 3) {
            $datePart = $parts[1] ?? '';
            $rest = array_slice($parts, 2);
            $docType = implode('_', $rest);
            $docType = preg_replace('/_Template$/', '', $docType);
            $docType = preg_replace('/-Template$/', '', $docType);
        } elseif (count($parts) === 2) {
            $docType = str_replace('_Template', '', $parts[1]);
        }

        return [
            'filename' => $filename,
            'folder' => $folder,
            'prefix' => $prefix,
            'date' => $datePart,
            'doc_type' => $docType,
            'path' => $filePath,
            'size' => file_exists($filePath) ? filesize($filePath) : 0,
            'modified' => file_exists($filePath) ? filemtime($filePath) : 0,
        ];
    }

    public static function scanTemplates(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $templates = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $subTemplates = self::scanTemplates($path);
                $templates = array_merge($templates, $subTemplates);
            } elseif (str_ends_with(strtolower($item), '.docx')) {
                $info = self::extractTemplateInfo($path);
                $info['variables'] = self::extractVariables($path);
                $templates[] = $info;
            }
        }

        return $templates;
    }

    public static function groupByFolder(array $templates): array
    {
        $grouped = [];
        foreach ($templates as $tpl) {
            $folder = $tpl['folder'];
            if (!isset($grouped[$folder])) {
                $grouped[$folder] = [];
            }
            $grouped[$folder][] = $tpl;
        }
        return $grouped;
    }
}
