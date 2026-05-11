<?php

declare(strict_types=1);

class TemplateEditor
{
    public static function extractText(string $docxPath): string
    {
        $xml = self::readDocxXml($docxPath);
        if ($xml === null) {
            return '';
        }

        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    public static function saveText(string $docxPath, string $text): void
    {
        $files = self::readDocxFiles($docxPath);
        $files['word/document.xml'] = self::textToDocumentXml($text);
        self::writeDocxFiles($files, $docxPath);
    }

    public static function createNew(string $text, string $outputPath): void
    {
        $files = self::getSkeletonFiles();
        $files['word/document.xml'] = self::textToDocumentXml($text);
        self::writeDocxFiles($files, $outputPath);
    }

    public static function extractHtml(string $docxPath): string
    {
        $xml = self::readDocxXml($docxPath);
        if ($xml === null) {
            return '';
        }
        return self::wordXmlToHtml($xml);
    }

    public static function saveHtml(string $docxPath, string $html): void
    {
        $files = self::readDocxFiles($docxPath);
        $files['word/document.xml'] = self::htmlToWordXml($html);
        self::writeDocxFiles($files, $docxPath);
    }

    public static function createNewHtml(string $html, string $outputPath): void
    {
        $files = self::getSkeletonFiles();
        $files['word/document.xml'] = self::htmlToWordXml($html);
        self::writeDocxFiles($files, $outputPath);
    }

    private static function textToDocumentXml(string $text): string
    {
        $paragraphs = explode("\n", $text);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
        $xml .= ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $xml .= ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"';
        $xml .= ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"';
        $xml .= ' mc:Ignorable="w14 wp14"';
        $xml .= ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"';
        $xml .= ' xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"';
        $xml .= ' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"';
        $xml .= '>';
        $xml .= '<w:body>';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                $xml .= '<w:p><w:pPr><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t xml:space="preserve"> </w:t></w:r></w:p>';
            } else {
                $escaped = htmlspecialchars($para, ENT_XML1, 'UTF-8');
                $xml .= '<w:p><w:pPr><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="fr-FR"/></w:rPr></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="fr-FR"/></w:rPr><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
            }
        }

        $xml .= '</w:body></w:document>';
        return $xml;
    }

    private static function wordXmlToHtml(string $wordXml): string
    {
        $wordXml = preg_replace('/<w:proofErr[^>]*\/>/', '', $wordXml);
        $wordXml = preg_replace('/<mc:AlternateContent>.*?<\/mc:AlternateContent>/s', '', $wordXml);
        $wordXml = preg_replace('/<w:bookmarkStart[^>]*\/>/', '', $wordXml);
        $wordXml = preg_replace('/<w:bookmarkEnd[^>]*\/>/', '', $wordXml);

        $html = '';
        $inParagraph = false;
        $inRun = false;
        $inTable = false;
        $inRow = false;
        $inCell = false;
        $bold = false;
        $italic = false;
        $underline = false;
        $alignment = '';
        $cellContent = '';
        $rowCells = [];
        $rows = [];

        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        $values = [];
        $index = [];
        xml_parse_into_struct($parser, $wordXml, $values, $index);
        xml_parser_free($parser);

        $tagDepth = [];
        $currentText = '';

        foreach ($values as $val) {
            $tag = $val['tag'];
            $type = $val['type'];
            $attrs = $val['attributes'] ?? [];
            $isW = str_starts_with($tag, 'w:');

            if (!$isW) continue;
            $localTag = substr($tag, 2);

            if ($type === 'open' || $type === 'complete') {
                array_push($tagDepth, $localTag);
            }

            switch ($localTag) {
                case 'p':
                    if ($type === 'open') {
                        $alignment = '';
                        $currentText = '';
                        $bold = false;
                        $italic = false;
                        $underline = false;
                    } elseif ($type === 'close') {
                        if ($inTable) break;
                        $alignClass = $alignment ? " style=\"text-align:{$alignment}\"" : '';
                        $html .= "<p{$alignClass}>{$currentText}</p>\n";
                        $currentText = '';
                        $alignment = '';
                    } elseif ($type === 'complete') {
                        if (!$inTable) {
                            $alignClass = $alignment ? " style=\"text-align:{$alignment}\"" : '';
                            $html .= "<p{$alignClass}><br></p>\n";
                        }
                    }
                    break;

                case 'jc':
                    if (isset($attrs['w:val'])) {
                        $alignment = $attrs['w:val'];
                    }
                    break;

                case 'r':
                    if ($type === 'open') {
                        $bold = false;
                        $italic = false;
                        $underline = false;
                    } elseif ($type === 'close') {
                    }
                    break;

                case 'rPr':
                    break;

                case 'b':
                    if ($type === 'complete' || $type === 'open') {
                        $bold = true;
                    } elseif ($type === 'close') {
                        $bold = false;
                    }
                    break;

                case 'i':
                    if ($type === 'complete' || $type === 'open') {
                        $italic = true;
                    } elseif ($type === 'close') {
                        $italic = false;
                    }
                    break;

                case 'u':
                    if ($type === 'complete' || $type === 'open') {
                        $underline = true;
                    } elseif ($type === 'close') {
                        $underline = false;
                    }
                    break;

                case 't':
                    if ($type === 'complete' || $type === 'cdata') {
                        $text = $val['value'] ?? '';
                        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                        if ($bold) $text = "<strong>{$text}</strong>";
                        if ($italic) $text = "<em>{$text}</em>";
                        if ($underline) $text = "<u>{$text}</u>";
                        $currentText .= $text;
                    }
                    break;

                case 'tab':
                    $currentText .= "\t";
                    break;

                case 'br':
                    $currentText .= "<br>";
                    break;

                case 'tbl':
                    if ($type === 'open') {
                        $inTable = true;
                        $rows = [];
                    } elseif ($type === 'close') {
                        $inTable = false;
                        $html .= "<table>\n";
                        foreach ($rows as $row) {
                            $html .= "<tr>\n";
                            foreach ($row as $cell) {
                                $html .= "<td>{$cell}</td>\n";
                            }
                            $html .= "</tr>\n";
                        }
                        $html .= "</table>\n";
                    }
                    break;

                case 'tr':
                    if ($type === 'open') {
                        $rowCells = [];
                    } elseif ($type === 'close') {
                        $rows[] = $rowCells;
                    }
                    break;

                case 'tc':
                    if ($type === 'open') {
                        $cellContent = '';
                    } elseif ($type === 'close') {
                        $rowCells[] = trim($cellContent);
                    }
                    break;
            }

            if ($type === 'close') {
                array_pop($tagDepth);
            }
        }

        $html = preg_replace('/<p style="text-align:left">/i', '<p>', $html);

        return trim($html);
    }

    private static function htmlToWordXml(string $html): string
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>' . $html . '</body></html>');
        libxml_clear_errors();

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
        $xml .= ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $xml .= ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"';
        $xml .= ' mc:Ignorable="w14 wp14"';
        $xml .= '>';
        $xml .= '<w:body>';
        $xml .= self::convertHtmlNode($dom->getElementsByTagName('body')->item(0));
        $xml .= '</w:body></w:document>';

        return $xml;
    }

    private static function convertHtmlNode(DOMNode $node): string
    {
        $xml = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                if ($text === '') continue;
                $xml .= '<w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="fr-FR"/></w:rPr><w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1, 'UTF-8') . '</w:t></w:r>';
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) continue;

            $tag = strtolower($child->tagName);

            if ($tag === 'p' || $tag === 'div') {
                $align = '';
                $style = $child->getAttribute('style');
                if (preg_match('/text-align\s*:\s*(\w+)/i', $style, $m)) {
                    $align = strtolower($m[1]);
                }
                $xml .= '<w:p>';
                if ($align && $align !== 'left') {
                    $xml .= '<w:pPr><w:jc w:val="' . $align . '"/></w:pPr>';
                } else {
                    $xml .= '<w:pPr><w:jc w:val="both"/></w:pPr>';
                }
                $xml .= self::convertInlineContent($child);
                $xml .= '</w:p>';
            } elseif ($tag === 'br') {
                $xml .= '<w:p><w:pPr><w:jc w:val="both"/></w:pPr><w:r><w:br/><w:t xml:space="preserve"> </w:t></w:r></w:p>';
            } elseif ($tag === 'table') {
                $xml .= '<w:tbl>';
                $xml .= '<w:tblPr><w:tblStyle w:val="Grilledutableau"/>';
                $xml .= '<w:tblW w:w="5000" w:type="dxa"/>';
                $xml .= '<w:tblBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
                $xml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
                $xml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
                $xml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
                $xml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
                $xml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
                $xml .= '</w:tblBorders></w:tblPr>';
                $xml .= '<w:tblGrid><w:gridCol w:w="2500"/></w:tblGrid>';
                $xml .= self::convertTableContent($child);
                $xml .= '</w:tbl>';
            } elseif ($tag === 'ul' || $tag === 'ol') {
                $isOrdered = $tag === 'ol';
                foreach ($child->childNodes as $li) {
                    if ($li->nodeType !== XML_ELEMENT_NODE) continue;
                    if (strtolower($li->tagName) !== 'li') continue;
                    $xml .= '<w:p>';
                    $xml .= '<w:pPr><w:pStyle w:val="ListParagraph"/>';
                    $xml .= '<w:numPr><w:ilvl w:val="0"/>';
                    $xml .= '<w:numId w:val="' . ($isOrdered ? '2' : '1') . '"/></w:numPr>';
                    $xml .= '</w:pPr>';
                    $xml .= self::convertInlineContent($li);
                    $xml .= '</w:p>';
                }
            } elseif ($tag === 'hr') {
                $xml .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="auto"/></w:pBdr></w:pPr></w:p>';
            }
        }

        return $xml;
    }

    private static function convertInlineContent(DOMNode $parent): string
    {
        return self::convertInlineWithFormatting($parent, false, false, false);
    }

    private static function convertInlineWithFormatting(DOMNode $parent, bool $inheritBold, bool $inheritItalic, bool $inheritUnderline): string
    {
        $xml = '';
        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->textContent;
                if (trim($text) === '') continue;
                $xml .= self::makeRun($text, $inheritBold, $inheritItalic, $inheritUnderline);
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->tagName);

            if ($tag === 'br') {
                $xml .= '<w:r><w:br/><w:t xml:space="preserve"> </w:t></w:r>';
            } elseif (in_array($tag, ['b', 'strong'])) {
                $xml .= self::convertInlineWithFormatting($child, true, $inheritItalic, $inheritUnderline);
            } elseif (in_array($tag, ['i', 'em'])) {
                $xml .= self::convertInlineWithFormatting($child, $inheritBold, true, $inheritUnderline);
            } elseif ($tag === 'u') {
                $xml .= self::convertInlineWithFormatting($child, $inheritBold, $inheritItalic, true);
            } elseif (in_array($tag, ['span', 'font'])) {
                $xml .= self::convertInlineWithFormatting($child, $inheritBold, $inheritItalic, $inheritUnderline);
            } else {
                $xml .= self::convertInlineWithFormatting($child, $inheritBold, $inheritItalic, $inheritUnderline);
            }
        }
        return $xml;
    }

    private static function convertTableCellContent(DOMNode $td): string
    {
        $xml = '';
        foreach ($td->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                if ($text === '') continue;
                $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>' . self::makeRun($text) . '</w:p>';
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->tagName);
            if ($tag === 'p' || $tag === 'div') {
                $align = '';
                $style = $child->getAttribute('style');
                if (preg_match('/text-align\s*:\s*(\w+)/i', $style, $m)) {
                    $align = strtolower($m[1]);
                }
                $xml .= '<w:p>';
                if ($align && $align !== 'left') {
                    $xml .= '<w:pPr><w:jc w:val="' . $align . '"/></w:pPr>';
                }
                $xml .= self::convertInlineContent($child);
                $xml .= '</w:p>';
            } elseif (in_array($tag, ['strong', 'b', 'em', 'i', 'u', 'span'])) {
                $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>' . self::convertInlineContent($child) . '</w:p>';
            } elseif ($tag === 'br') {
                $xml .= '<w:p><w:r><w:t> </w:t></w:r></w:p>';
            } else {
                $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>' . self::convertInlineContent($child) . '</w:p>';
            }
        }
        return $xml;
    }

    private static function makeRun(string $text, bool $bold = false, bool $italic = false, bool $underline = false): string
    {
        if (trim($text) === '') return '';
        $rPr = '';
        if ($bold) $rPr .= '<w:b/><w:bCs/>';
        if ($italic) $rPr .= '<w:i/><w:iCs/>';
        if ($underline) $rPr .= '<w:u w:val="single"/>';
        $rPr .= '<w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="fr-FR"/>';
        $rPr = $rPr ? "<w:rPr>{$rPr}</w:rPr>" : '';
        return '<w:r>' . $rPr . '<w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1, 'UTF-8') . '</w:t></w:r>';
    }

    private static function convertTableContent(DOMNode $table): string
    {
        $xml = '';
        $colCount = 0;

        foreach ($table->childNodes as $tr) {
            if ($tr->nodeType !== XML_ELEMENT_NODE) continue;
            if (strtolower($tr->tagName) !== 'tr') continue;

            $rowCells = [];
            foreach ($tr->childNodes as $td) {
                if ($td->nodeType !== XML_ELEMENT_NODE) continue;
                if (!in_array(strtolower($td->tagName), ['td', 'th'])) continue;
                $cellHtml = self::convertTableCellContent($td);
                $rowCells[] = $cellHtml;
            }
            $colCount = max($colCount, count($rowCells));

            $xml .= '<w:tr>';
            foreach ($rowCells as $cell) {
                $xml .= '<w:tc>';
                $xml .= '<w:tcPr><w:tcW w:w="' . (int)(5000 / max($colCount, 1)) . '" w:type="dxa"/>';
                $xml .= '<w:vAlign w:val="center"/></w:tcPr>';
                $xml .= $cell;
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml;
    }

    private static function readDocxXml(string $path): ?string
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml !== false && $xml !== null) {
                    return $xml;
                }
            }
        }

        $fh = fopen($path, 'rb');
        if ($fh === false) return null;

        $centralDir = self::findCentralDir($fh);
        if ($centralDir === null) { fclose($fh); return null; }

        $entries = self::parseCentralDir($fh, $centralDir);
        fclose($fh);

        if (isset($entries['word/document.xml'])) {
            $e = $entries['word/document.xml'];
            $fh = fopen($path, 'rb');
            if ($fh === false) return null;
            fseek($fh, $e['offset']);
            $data = self::inflateEntry($fh, $e['compressed'], $e['uncompressed'], $e['method']);
            fclose($fh);
            return $data;
        }

        return null;
    }

    private static function readDocxFiles(string $path): array
    {
        $files = [];

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    $data = $zip->getFromIndex($i);
                    if ($data !== false) {
                        $files[$name] = $data;
                    }
                }
                $zip->close();
                return $files;
            }
        }

        $fh = fopen($path, 'rb');
        if ($fh === false) return [];

        $centralDir = self::findCentralDir($fh);
        if ($centralDir === null) { fclose($fh); return []; }

        $entries = self::parseCentralDir($fh, $centralDir);
        fclose($fh);

        foreach ($entries as $name => $e) {
            $fh = fopen($path, 'rb');
            if ($fh === false) continue;
            fseek($fh, $e['offset']);
            $data = self::inflateEntry($fh, $e['compressed'], $e['uncompressed'], $e['method']);
            fclose($fh);
            if ($data !== null) {
                $files[$name] = $data;
            }
        }

        return $files;
    }

    private static function writeDocxFiles(array $files, string $outputPath): void
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($files as $name => $data) {
                    $zip->addFromString($name, $data);
                }
                $zip->close();
                return;
            }
        }

        self::createZipPurePhp($files, $outputPath);
    }

    private static function createZipPurePhp(array $files, string $outputPath): void
    {
        $entries = [];
        $centralDir = '';
        $offset = 0;

        $fh = fopen($outputPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('Impossible de creer le fichier ZIP.');
        }

        foreach ($files as $name => $data) {
            $crc = crc32($data);
            $compressed = @gzdeflate($data);
            $useCompression = $compressed !== false && strlen($compressed) < strlen($data);
            $finalData = $useCompression ? $compressed : $data;
            $method = $useCompression ? 8 : 0;
            $size = strlen($data);
            $compSize = strlen($finalData);
            $nameLen = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                $method,
                0,
                0,
                $crc,
                $compSize,
                $size,
                $nameLen,
                0
            );
            $localHeader .= $name;

            $entries[] = [
                'name' => $name,
                'method' => $method,
                'crc' => $crc,
                'compSize' => $compSize,
                'size' => $size,
                'localOffset' => $offset,
            ];

            fwrite($fh, $localHeader);
            fwrite($fh, $finalData);
            $offset += strlen($localHeader) + $compSize;
        }

        $centralOffset = $offset;
        foreach ($entries as $e) {
            $name = $e['name'];
            $nameLen = strlen($name);
            $entryHeader = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                $e['method'],
                0,
                0,
                $e['crc'],
                $e['compSize'],
                $e['size'],
                $nameLen,
                0,
                0,
                0,
                0,
                0,
                $e['localOffset']
            );
            $entryHeader .= $name;
            $centralDir .= $entryHeader;
        }

        $centralSize = strlen($centralDir);
        fwrite($fh, $centralDir);

        $eocd = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            count($entries),
            count($entries),
            $centralSize,
            $centralOffset,
            0
        );
        fwrite($fh, $eocd);
        fclose($fh);
    }

    private static function getSkeletonFiles(): array
    {
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '</Relationships>';

        return [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => $rels,
            'word/_rels/document.xml.rels' => $docRels,
        ];
    }

    public static function getAvailableVariables(): array
    {
        return [
            'Societe' => [
                'RAISON_SOCIALE' => 'Raison sociale',
                'DENOMINATION_SOCIALE' => 'Denomination sociale',
                'DEN_STE' => 'Denomination interne',
                'FORME_JURIDIQUE' => 'Forme juridique',
                'FORME_JUR' => 'Forme juridique (court)',
                'ICE' => 'ICE',
                'NUMERO_ICE' => 'Numero ICE',
                'RC' => 'RC',
                'IF' => 'IF',
                'CAPITAL' => 'Capital',
                'CAPITAL_SOCIAL' => 'Capital social',
                'VILLE' => 'Ville',
                'TRIBUNAL' => 'Tribunal',
                'TRIBUNAL_COMPETENT' => 'Tribunal competent',
                'ADRESSE_SIEGE_SOCIAL' => 'Adresse siege social',
                'STE_ADRESS' => 'Adresse (court)',
                'EMAIL' => 'Email',
                'TELEPHONE' => 'Telephone',
                'DOSSIER_DOMICILIATION' => 'Dossier domiciliation',
            ],
            'Associe' => [
                'NOM_COMPLET' => 'Nom complet',
                'NOM_ASSOCIE' => 'Nom associe',
                'PRENOM_ASSOCIE' => 'Prenom associe',
                'CIN' => 'CIN',
                'NUMERO_CIN_ASSOCIE' => 'Numero CIN',
                'DATE_NAISSANCE' => 'Date naissance',
                'DATE_NAISSANCE_ASSOCIE' => 'Date naissance associe',
                'LIEU_NAISSANCE' => 'Lieu naissance',
                'LIEU_NAISSANCE_ASSOCIE' => 'Lieu naissance associe',
                'NATIONALITE' => 'Nationalite',
                'NATIONALITE_ASSOCIE' => 'Nationalite associe',
                'ADRESSE_ASSOCIE' => 'Adresse associe',
                'TELEPHONE_ASSOCIE' => 'Telephone associe',
                'EMAIL_ASSOCIE' => 'Email associe',
                'QUALITE_ASSOCIE' => 'Qualite associe',
                'NOMBRE_PARTS' => 'Nombre parts',
                'NOMBRE_PARTS_ASSOCIE' => 'Nombre parts associe',
                'EST_GERANT' => 'Est gerant',
            ],
            'Contrat' => [
                'TYPE_CONTRAT' => 'Type contrat',
                'DATE_CONTRAT' => 'Date contrat',
                'DUREE_CONTRAT_MOIS' => 'Duree (mois)',
                'DATE_DEBUT_CONTRAT' => 'Date debut',
                'DATE_FIN_CONTRAT' => 'Date fin',
                'LOYER_MENSUEL_TTC' => 'Loyer mensuel TTC',
                'CAUTION' => 'Caution',
                'STATUT' => 'Statut',
            ],
            'Activite' => [
                'ACTIVITY1' => 'Activite 1',
                'ACTIVITY2' => 'Activite 2',
                'ACTIVITY3' => 'Activite 3',
                'ACTIVITY4' => 'Activite 4',
                'ACTIVITY5' => 'Activite 5',
            ],
            'Dates' => [
                'DATE' => 'Date du jour (court)',
                'DATE_LONG' => 'Date du jour (long)',
                'ANNEE' => 'Annee en cours',
                'MOIS' => 'Mois en cours',
                'JOUR' => 'Jour en cours',
            ],
        ];
    }

    private static function findCentralDir($fh): ?array
    {
        $size = fstat($fh)['size'];
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

    private static function inflateEntry($fh, int $compressedSize, int $uncompressedSize, int $method): ?string
    {
        $sig = fread($fh, 4);
        if ($sig !== "\x50\x4b\x03\x04") return null;

        $unpack = unpack('vversion/vflags/vmethod/vmtime/vmdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen', fread($fh, 26));
        $name = fread($fh, $unpack['nameLen']);
        $extra = $unpack['extraLen'] > 0 ? fread($fh, $unpack['extraLen']) : '';

        $compSize = $compressedSize > 0 ? $compressedSize : $unpack['compressed'];
        $uncompSize = $uncompressedSize > 0 ? $uncompressedSize : $unpack['uncompressed'];

        if ($method === 0) {
            $len = $uncompSize > 0 ? $uncompSize : 8192;
            return fread($fh, $len);
        }

        if ($method === 8) {
            $len = $compSize > 0 ? $compSize : $uncompSize;
            if ($len <= 0) return null;
            $data = fread($fh, $len);
            if ($data === false || strlen($data) === 0) return null;
            $inflated = @gzinflate($data);
            if ($inflated !== false) return $inflated;
            $inflated = @gzuncompress($data);
            return $inflated !== false ? $inflated : null;
        }

        return null;
    }
}
