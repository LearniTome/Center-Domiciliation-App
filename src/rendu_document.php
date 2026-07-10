<?php

declare(strict_types=1);

class DocumentRenderer
{
    private string $templatePath;
    private string $outputDir;

    private static function formatDate(?string $val): string
    {
        if (empty($val)) return '';
        $dt = \DateTime::createFromFormat('Y-m-d', $val);
        return $dt ? $dt->format('d/m/Y') : $val;
    }

    private static function calcPackMontantTtc(array $contrat): string
    {
        $loyerTtc = $contrat['contrat_loyer_ttc'] ?? null;
        $dureeMois = $contrat['contrat_duree_mois'] ?? null;
        if ($loyerTtc !== null && $loyerTtc !== '' && $dureeMois !== null && $dureeMois !== '') {
            return (string) ((float) $loyerTtc * (int) $dureeMois);
        }
        return '';
    }

    private static function calcRenouvAnnuelTtc(array $contrat): string
    {
        $renouvLoyerTtc = $contrat['contrat_renouv_loyer_ttc'] ?? null;
        if ($renouvLoyerTtc !== null && $renouvLoyerTtc !== '') {
            return (string) ((float) $renouvLoyerTtc * 12);
        }
        $renouvLoyerHt = $contrat['contrat_renouv_loyer_ht'] ?? null;
        $renouvTva = $contrat['contrat_renouv_tva_pourcent'] ?? null;
        if ($renouvLoyerHt !== null && $renouvLoyerHt !== '' && $renouvTva !== null && $renouvTva !== '') {
            $monthlyTtc = (float) $renouvLoyerHt * (1 + (float) $renouvTva / 100);
            return (string) ($monthlyTtc * 12);
        }
        return '';
    }

    public function __construct(string $templatePath, string $outputDir)
    {
        $this->templatePath = $templatePath;
        $this->outputDir = $outputDir;
    }

    public function render(array $context, string $outputName = ''): string
    {
        $xml = $this->readXml();
        $xml = $this->processLoops($xml, $context);
        $xml = $this->replaceValues($xml, $context);
        return $this->saveDocx($xml, $outputName);
    }

    private function readXml(): string
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docx_' . uniqid();
        $this->extractTo($this->templatePath, $tmpDir);
        $xmlPath = $tmpDir . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'document.xml';

        if (!file_exists($xmlPath)) {
            throw new RuntimeException('document.xml introuvable dans le template.');
        }

        $xml = file_get_contents($xmlPath);
        $this->tmpDir = $tmpDir;
        return $xml;
    }

    private string $tmpDir = '';

    private function extractTo(string $zipFile, string $dest): void
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($dest);
                $zip->close();
                return;
            }
        }

        mkdir($dest, 0777, true);
        if (self::isCommandAvailable('unzip')) {
            shell_exec(sprintf('unzip -o %s -d %s 2>&1', escapeshellarg($zipFile), escapeshellarg($dest)));
            if (is_dir($dest . DIRECTORY_SEPARATOR . 'word')) {
                return;
            }
        }

        self::extractZipPurePhp($zipFile, $dest);
    }

    private static function extractZipPurePhp(string $zipFile, string $dest): void
    {
        $fh = fopen($zipFile, 'rb');
        if ($fh === false) {
            throw new RuntimeException('Impossible d\'ouvrir le fichier ZIP.');
        }

        $centralDir = self::findCentralDir($fh);
        if ($centralDir === null) {
            fclose($fh);
            throw new RuntimeException('Impossible de trouver le repertoire central du ZIP.');
        }

        $entries = self::parseCentralDir($fh, $centralDir);
        fclose($fh);

        foreach ($entries as $name => $e) {
            $fh = fopen($zipFile, 'rb');
            if ($fh === false) continue;
            fseek($fh, $e['offset']);
            $data = self::inflateEntry($fh, $e['compressed'], $e['uncompressed'], $e['method']);
            fclose($fh);

            if ($data === null) continue;

            $targetPath = $dest . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            file_put_contents($targetPath, $data);
        }
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

    private static function isCommandAvailable(string $command): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $where = shell_exec("where {$command} 2>NUL");
            return $where !== null && $where !== '';
        }
        $which = shell_exec("which {$command} 2>/dev/null");
        return $which !== null && $which !== '';
    }

    private function processLoops(string $xml, array $context): string
    {
        $xml = $this->processAssocieLoop($xml, $context);
        $xml = $this->processActivityLoop($xml, $context);
        $xml = $this->processCessionPartsLoop($xml, $context);
        $xml = $this->processPvResolutionsLoop($xml, $context);
        return $xml;
    }

    private function processCessionPartsLoop(string $xml, array $context): string
    {
        $pattern = '/\{\%p\s+for\s+c\s+in\s+cession_parts\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s';
        return preg_replace_callback($pattern, function ($matches) use ($context) {
            $block = $matches[1];
            $parts = $context['cession_parts'] ?? [];
            $result = '';
            foreach ($parts as $i => $part) {
                $item = $block;
                $item = str_replace('{{ c.CEDANT_NOM_COMPLET }}', $part['cedant_nom_complet'] ?? '', $item);
                $item = str_replace('{{ c.CEDANT_CIN }}', $part['cedant_cin'] ?? '', $item);
                $item = str_replace('{{ c.CEDANT_TYPE }}', $part['cedant_type'] ?? 'existant', $item);
                $item = str_replace('{{ c.CESSIONNAIRE_NOM_COMPLET }}', $part['cessionnaire_nom_complet'] ?? '', $item);
                $item = str_replace('{{ c.CESSIONNAIRE_CIN }}', $part['cessionnaire_cin'] ?? '', $item);
                $item = str_replace('{{ c.CESSIONNAIRE_TYPE }}', $part['cessionnaire_type'] ?? 'existant', $item);
                $item = str_replace('{{ c.PARTS_CEDEES }}', (string) ($part['parts_cedees'] ?? ''), $item);
                $item = str_replace('{{ c.PRIX_UNITAIRE }}', (string) ($part['prix_unitaire'] ?? ''), $item);
                $item = str_replace('{{ c.PRIX_TOTAL }}', (string) ($part['prix_total'] ?? ''), $item);
                $result .= $item;
                if ($i < count($parts) - 1) {
                    $result .= "\n";
                }
            }
            return $result;
        }, $xml);
    }

    private function processAssocieLoop(string $xml, array $context): string
    {
        $pattern = '/\{\%p\s+for\s+a\s+in\s+associes\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s';
        return preg_replace_callback($pattern, function ($matches) use ($context) {
            $block = $matches[1];
            $associes = $context['associes'] ?? [];
            $result = '';
            foreach ($associes as $i => $associe) {
                $item = $block;
                $item = str_replace('{{ a.NOM }}', $associe['associe_nom'] ?? '', $item);
                $item = str_replace('{{ a.PRENOM }}', $associe['associe_prenom'] ?? '', $item);
                $item = str_replace('{{ a.CIN }}', $associe['associe_cin'] ?? '', $item);
                $item = str_replace('{{ a.NATIONALITE }}', $associe['associe_nationalite'] ?? '', $item);
                $item = str_replace('{{ a.QUALITE }}', $associe['associe_qualite'] ?? '', $item);
                $item = str_replace('{{ a.PARTS }}', (string) ($associe['associe_parts'] ?? ''), $item);
                $item = str_replace('{{ a.EST_GERANT }}', $associe['associe_est_gerant'] ?? 'Non', $item);
                $prefix = $associe['associe_civilite'] ?? 'M.';
                $fullName = trim($prefix . ' ' . ($associe['associe_prenom'] ?? '') . ' ' . ($associe['associe_nom'] ?? ''));
                $item = str_replace('{{ a.NOM_COMPLET }}', $fullName, $item);
                $item = str_replace('{{ a.ADRESSE }}', $associe['adresse'] ?? '', $item);
                $item = str_replace('{{ a.EMAIL }}', $associe['email'] ?? '', $item);
                $item = str_replace('{{ a.TELEPHONE }}', $associe['telephone'] ?? '', $item);
                $item = str_replace('{{ a.DATE_NAISSANCE }}', $associe['associe_date_naissance'] ?? '', $item);
                $item = str_replace('{{ a.LIEU_NAISSANCE }}', $associe['associe_lieu_naissance'] ?? '', $item);
                $result .= $item;
                if ($i < count($associes) - 1) {
                    $result .= "\n";
                }
            }
            return $result;
        }, $xml);
    }

    private function processPvResolutionsLoop(string $xml, array $context): string
    {
        $pattern = '/\{\%p\s+for\s+r\s+in\s+pv_resolutions\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s';
        return preg_replace_callback($pattern, function ($matches) use ($context) {
            $block = $matches[1];
            $resolutions = $context['pv_resolutions'] ?? [];
            $result = '';
            foreach ($resolutions as $i => $r) {
                $item = $block;
                $title = 'Résolution ' . ($i + 1) . ' : ' . ($r['title'] ?? '');
                $content = str_replace("\n", '</w:t></w:r><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:br/><w:t xml:space="preserve">', $r['content'] ?? '');
                $item = str_replace('{{ r.TITLE }}', $title, $item);
                $item = str_replace('{{ r.CONTENT }}', $content, $item);
                $result .= $item;
            }
            return $result;
        }, $xml);
    }

    private function processActivityLoop(string $xml, array $context): string
    {
        $list = $context['activities'] ?? $context['ACTIVITIES_LIST'] ?? [];
        if (is_string($list)) {
            $list = [$list];
        }

        $patterns = [
            '/\{\%p\s+for\s+a\s+in\s+ACTIVITIES_LIST\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s',
            '/\{\%p\s+for\s+a\s+in\s+ACTIVITES_LIST\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s',
            '/\{\%p\s+for\s+a\s+in\s+ACTIVITIES\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s',
            '/\{\%p\s+for\s+a\s+in\s+ACTIVITES\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s',
        ];

        foreach ($patterns as $pattern) {
            $xml = preg_replace_callback($pattern, function ($matches) use ($list) {
                $block = $matches[1];
                $result = '';
                foreach ($list as $i => $activity) {
                    $item = str_replace('{{ a }}', $activity, $block);
                    $result .= $item;
                    if ($i < count($list) - 1) {
                        $result .= "\n";
                    }
                }
                return $result;
            }, $xml);
        }

        return $xml;
    }

    private function replaceValues(string $xml, array $context): string
    {
        $flat = $this->flattenContext($context);

        foreach ($flat as $key => $value) {
            $xml = str_replace('{{ ' . $key . ' }}', (string) $value, $xml);
            $xml = str_replace('{{' . $key . '}}', (string) $value, $xml);
        }

        return $xml;
    }

    private static function createZipPurePhp(string $sourceDir, string $outPath): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $entries = [];
        $centralDir = '';
        $offset = 0;

        $fh = fopen($outPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('Impossible de creer le fichier ZIP.');
        }

        foreach ($files as $file) {
            if ($file->isDir()) continue;
            $localPath = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $localPath = str_replace(DIRECTORY_SEPARATOR, '/', $localPath);
            $data = file_get_contents($file->getPathname());
            $crc = crc32($data);
            $compressed = @gzdeflate($data);
            $useCompression = $compressed !== false && strlen($compressed) < strlen($data);
            $finalData = $useCompression ? $compressed : $data;
            $method = $useCompression ? 8 : 0;
            $size = strlen($data);
            $compSize = strlen($finalData);

            $nameLen = strlen($localPath);
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
            $localHeader .= $localPath;

            $entries[] = [
                'name' => $localPath,
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

    private function flattenContext(array $context, string $prefix = ''): array
    {
        $result = [];
        foreach ($context as $key => $value) {
            if (is_array($value) && !isset($value[0])) {
                $nested = $this->flattenContext($value, $prefix . strtoupper((string) $key) . '_');
                foreach ($nested as $nk => $nv) {
                    $result[$nk] = $nv;
                }
                $result[strtoupper((string) $key)] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (!is_array($value)) {
                $result[$prefix . strtoupper((string) $key)] = $value;
                $result[strtoupper((string) $key)] = $value;
            }
        }

        return $result;
    }

    private function saveDocx(string $newXml, string $outputName = ''): string
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        if ($outputName !== '') {
            $outPath = $this->outputDir . DIRECTORY_SEPARATOR . $outputName;
        } else {
            $outName = pathinfo($this->templatePath, PATHINFO_FILENAME);
            $outName = preg_replace('/_Template$/', '', $outName);
            $outName = preg_replace('/-Template$/', '', $outName);
            $outPath = $this->outputDir . DIRECTORY_SEPARATOR . $outName . '_genere.docx';
        }

        $tmpDir = $this->tmpDir;
        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'document.xml', $newXml);

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($files as $file) {
                    $relative = substr($file->getPathname(), strlen($tmpDir) + 1);
                    $zip->addFile($file->getPathname(), $relative);
                }
                $zip->close();
                $this->cleanup();
                return $outPath;
            }
        }

        self::createZipPurePhp($tmpDir, $outPath);
        $this->cleanup();
        return $outPath;
    }

    private function cleanup(): void
    {
        if ($this->tmpDir !== '' && is_dir($this->tmpDir)) {
            $this->rmdirRecursive($this->tmpDir);
        }
    }

    private function rmdirRecursive(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function tryConvertToPdf(string $docxPath, string $pdfName = ''): ?string
    {
        $pdfPath = $pdfName !== ''
            ? $this->outputDir . DIRECTORY_SEPARATOR . $pdfName
            : preg_replace('/\.docx$/i', '.pdf', $docxPath);

        if (self::isCommandAvailable('soffice')) {
            $dir = escapeshellarg(dirname($docxPath));
            $cmd = "soffice --headless --convert-to pdf --outdir {$dir} " . escapeshellarg($docxPath) . ' 2>&1';
            shell_exec($cmd);
            if (file_exists($pdfPath)) {
                return $pdfPath;
            }
        }

        if (self::isCommandAvailable('libreoffice')) {
            $dir = escapeshellarg(dirname($docxPath));
            $cmd = "libreoffice --headless --convert-to pdf --outdir {$dir} " . escapeshellarg($docxPath) . ' 2>&1';
            shell_exec($cmd);
            if (file_exists($pdfPath)) {
                return $pdfPath;
            }
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $absPath = realpath($docxPath);
                if ($absPath !== false) {
                    $word = new COM('Word.Application');
                    $word->Visible = false;
                    $word->DisplayAlerts = false;
                    $doc = $word->Documents->Open($absPath);
                    $doc->ExportAsFixedFormat($pdfPath, 17);
                    $doc->Close(false);
                    $word->Quit(false);
                    unset($doc, $word);
                    if (file_exists($pdfPath)) {
                        return $pdfPath;
                    }
                }
            } catch (\Throwable $e) {
                if (isset($word)) {
                    try { $word->Quit(false); } catch (\Throwable $ignore) {}
                    unset($word);
                }
            }
        }

        // Fallback: PHPWord → HTML → Dompdf
        if (class_exists('\PhpOffice\PhpWord\IOFactory') && class_exists('\Dompdf\Dompdf')) {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
                $htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
                $tmpHtml = tempnam(sys_get_temp_dir(), 'pw2pdf_') . '.html';
                $htmlWriter->save($tmpHtml);
                $html = file_get_contents($tmpHtml);
                @unlink($tmpHtml);

                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4');
                $dompdf->render();

                file_put_contents($pdfPath, $dompdf->output());

                if (file_exists($pdfPath)) {
                    return $pdfPath;
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    public static function buildContextFromDb(PDO $pdo, int $societeId): array
    {
        $societe = fetch_record($pdo, 'societes', $societeId);
        if (!$societe) {
            return [];
        }

        $stmt = $pdo->prepare('SELECT * FROM associes WHERE societe_id = :id ORDER BY id');
        $stmt->execute(['id' => $societeId]);
        $associes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM contrats WHERE societe_id = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['id' => $societeId]);
        $contrat = $stmt->fetch() ?: [];

        $activitesStmt = $pdo->prepare('SELECT activite FROM ref_activites ORDER BY sort_order, activite');
        $activitesStmt->execute();
        $allActivities = array_map(static fn(array $row): string => $row['activite'], $activitesStmt->fetchAll());

        $societeActivities = [];
        if (!empty($societe['societe_activites_statuts'])) {
            $societeActivities = array_map('trim', explode(',', (string) $societe['societe_activites_statuts']));
        }

        $activitiesList = $societeActivities ?: $allActivities;
        $activitiesCount = count($activitiesList);
        $activitiesInline = implode(', ', $activitiesList);
        $activitiesBullets = '';
        $activitiesContinuationBullets = '';
        foreach ($activitiesList as $i => $act) {
            $prefix = '  \\item ';
            $activitiesBullets .= $prefix . $act . "\n";
            if ($i < $activitiesCount - 1) {
                $activitiesContinuationBullets .= $prefix . $act . "\n";
            }
        }

        $certNegCode = !empty($societe['societe_activites_ompic']) ? (string) $societe['societe_activites_ompic'] : '';
        $certNegList = [];
        if ($certNegCode !== '') {
            try {
                $nmaStmt = $pdo->prepare("SELECT CONCAT(code, ' - ', libelle) AS display FROM ref_activites_ompic WHERE code = :code LIMIT 1");
                $nmaStmt->execute(['code' => $certNegCode]);
                $nmaRow = $nmaStmt->fetch();
                $certNegList = $nmaRow ? [$nmaRow['display']] : [$certNegCode];
            } catch (Throwable) {
                $certNegList = [$certNegCode];
            }
        } else {
            $certNegList = $allActivities;
        }
        $certNegCount = count($certNegList);
        $certNegInline = implode(', ', $certNegList);
        $certNegBullets = '';
        foreach ($certNegList as $i => $act) {
            $prefix = '  \\item ';
            $certNegBullets .= $prefix . $act . "\n";
        }

        $associeList = [];
        foreach ($associes as $a) {
            $cin = $a['associe_cin'] ?? '';
            $dateValiditeCin = $a['associe_date_validite_cin'] ?? '';
            $dateNaiss = $a['associe_date_naissance'] ?? '';
            $lieuNaiss = $a['associe_lieu_naissance'] ?? '';
            $adresse = $a['associe_adresse'] ?? '';
            $phone = $a['associe_telephone'] ?? '';
            $email = $a['associe_email'] ?? '';
            $nationalite = $a['associe_nationalite'] ?? '';
            $qualite = $a['associe_qualite'] ?? '';
            $parts = $a['associe_parts'] ?? '';
            $capitalDetenu = $a['associe_capital_detenu'] ?? '';
            $isGerant = (int) ($a['associe_est_gerant'] ?? 0) === 1 ? 'Gerant' : 'Associe';

            $nomComplet = $a['associe_nom_complet'] ?? '';
            $nomParts = explode(' ', $nomComplet, 2);
            $prenom = count($nomParts) > 1 ? $nomParts[0] : '';
            $nom = count($nomParts) > 1 ? $nomParts[1] : $nomComplet;

            $associeList[] = [
                'associe_nom' => $nom,
                'associe_prenom' => $prenom,
                'associe_cin' => $cin,
                'associe_date_validite_cin' => $dateValiditeCin,
                'associe_nationalite' => $nationalite,
                'associe_qualite' => $qualite,
                'associe_parts' => $parts,
                'associe_est_gerant' => $isGerant,
                'associe_civilite' => $a['associe_civilite'] ?? 'M.',
                'adresse' => $adresse,
                'email' => $email,
                'telephone' => $phone,
                'associe_date_naissance' => $dateNaiss,
                'associe_lieu_naissance' => $lieuNaiss,
                'associe_capital_detenu' => $capitalDetenu,
            ];
        }

        $firstAssocie = $associeList[0] ?? [];
        $fNom = $firstAssocie['associe_nom'] ?? '';
        $fPrenom = $firstAssocie['associe_prenom'] ?? '';
        $fCivilite = $firstAssocie['associe_civilite'] ?? 'M.';
        $fNomComplet = trim("$fCivilite $fPrenom $fNom");

        $now = new DateTime();
        $dateContrat = $contrat['contrat_date'] ?? ($contrat['contrat_date_debut'] ?? $now->format('Y-m-d'));
        $dateDebut = $contrat['contrat_date_debut'] ?? $dateContrat;
        $dateFin = $contrat['contrat_date_fin'] ?? '';
        $dureeMois = $contrat['contrat_duree_mois'] ?? '';

        $denSte = $societe['societe_raison_sociale'] ?? '';
        $formeJur = $societe['societe_forme_juridique'] ?? '';
        $capital = $societe['societe_capital'] ?? '';
        $partSocial = $societe['societe_part_social'] ?? '';
        $ice = $societe['societe_ice'] ?? '';
        $steAdress = $societe['societe_adresse_siege'] ?? $societe['adresse'] ?? '';
        $tribunal = $societe['societe_tribunal'] ?? '';
        $ville = $societe['societe_ville'] ?? '';

        return [
            'societe' => $societe,
            'associes' => $associeList,
            'contrat' => $contrat,
            'activities' => $activitiesList,
            'SOCIETE_RAISON_SOCIALE' => $denSte,
            'SOCIETE_FORME_JURIDIQUE' => $formeJur,
            'SOCIETE_ICE' => $ice,
            'SOCIETE_RC' => $societe['societe_rc'] ?? '',
            'SOCIETE_IF' => $societe['societe_if'] ?? '',
            'SOCIETE_CAPITAL' => $capital,
            'SOCIETE_PART_SOCIAL' => $partSocial,
            'SOCIETE_VALEUR_NOMINALE' => $societe['societe_valeur_nominale'] ?? '',
            'SOCIETE_VILLE' => $ville,
            'SOCIETE_TRIBUNAL' => $tribunal,
            'SOCIETE_ADRESSE_SIEGE' => $steAdress,
            'SOCIETE_EMAIL' => $societe['societe_email'] ?? '',
            'SOCIETE_TELEPHONE' => $societe['societe_telephone'] ?? '',
            'SOCIETE_DOSSIER' => $societe['societe_dossier'] ?? '',
            'SOCIETE_TYPE_GENERATION' => $societe['societe_type_generation'] ?? '',
            'SOCIETE_PROCEDURE_CREATION' => $societe['societe_procedure_creation'] ?? '',
            'SOCIETE_MODE_DEPOT' => $societe['societe_mode_depot'] ?? '',
            'SOCIETE_TRIBUNAL_TYPE' => $societe['societe_tribunal_type'] ?? '',
            'SOCIETE_DATE_ICE' => self::formatDate($societe['societe_date_ice'] ?? ''),
            'SOCIETE_DATE_EXP_CERT_NEG' => self::formatDate($societe['societe_date_exp_cert_neg'] ?? ''),
            'ASSOCIE_NOM_COMPLET' => $fNomComplet,
            'ASSOCIE_NOM' => $fNom,
            'ASSOCIE_PRENOM' => $fPrenom,
            'ASSOCIE_CIVILITE' => $fCivilite,
            'ASSOCIE_CIN' => $firstAssocie['associe_cin'] ?? '',
            'ASSOCIE_DATE_VALIDITE_CIN' => self::formatDate($firstAssocie['associe_date_validite_cin'] ?? ''),
            'ASSOCIE_DATE_NAISSANCE' => self::formatDate($firstAssocie['associe_date_naissance'] ?? ''),
            'ASSOCIE_LIEU_NAISSANCE' => $firstAssocie['associe_lieu_naissance'] ?? '',
            'ASSOCIE_NATIONALITE' => $firstAssocie['associe_nationalite'] ?? '',
            'ASSOCIE_ADRESSE' => $firstAssocie['adresse'] ?? '',
            'ASSOCIE_TELEPHONE' => $firstAssocie['telephone'] ?? '',
            'ASSOCIE_EMAIL' => $firstAssocie['email'] ?? '',
            'ASSOCIE_QUALITE' => $firstAssocie['associe_qualite'] ?? '',
            'ASSOCIE_PARTS' => $firstAssocie['associe_parts'] ?? '',
            'ASSOCIE_CAPITAL_DETENU' => $firstAssocie['associe_capital_detenu'] ?? '',
            'ASSOCIE_EST_GERANT' => $firstAssocie['associe_est_gerant'] ?? '',
            'CONTRAT_TYPE' => $contrat['contrat_type'] ?? '',
            'CONTRAT_TYPE_DOMICILIATION' => $contrat['contrat_type_domiciliation'] ?? '',
            'CONTRAT_DATE' => self::formatDate($dateContrat),
            'CONTRAT_DATE_DEBUT' => self::formatDate($dateDebut),
            'CONTRAT_DATE_FIN' => self::formatDate($dateFin),
            'CONTRAT_DUREE_MOIS' => $dureeMois,
            'CONTRAT_LOYER_TTC' => $contrat['contrat_loyer_ttc'] ?? '',
            'CONTRAT_LOYER_HT' => $contrat['contrat_loyer_ht'] ?? '',
            'CONTRAT_TVA_POURCENT' => $contrat['contrat_tva_pourcent'] ?? '',
            'CONTRAT_TOTAL_HT' => $contrat['contrat_total_ht'] ?? '',
            'CONTRAT_FRAIS_INTERMEDIAIRE' => $contrat['contrat_frais_intermediaire'] ?? '',
            'CONTRAT_CAUTION' => $contrat['contrat_caution'] ?? '',
            'CONTRAT_STATUT' => $contrat['contrat_statut'] ?? '',
            'CONTRAT_MODE_SIGNATURE' => $contrat['contrat_mode_signature'] ?? '',
            'CONTRAT_PACK_MONTANT_TTC' => $contrat['contrat_pack_montant_ttc'] ?? self::calcPackMontantTtc($contrat),
            'CONTRAT_PACK_LOYER_TTC' => $contrat['contrat_pack_loyer_ttc'] ?? ($contrat['contrat_loyer_ttc'] ?? ''),
            'CONTRAT_TYPE_RENOUVELLEMENT' => $contrat['contrat_type_renouvellement'] ?? '',
            'CONTRAT_RENOUV_TVA_POURCENT' => $contrat['contrat_renouv_tva_pourcent'] ?? '',
            'CONTRAT_RENOUV_LOYER_HT' => $contrat['contrat_renouv_loyer_ht'] ?? '',
            'CONTRAT_RENOUV_LOYER_TTC' => $contrat['contrat_renouv_loyer_ttc'] ?? '',
            'CONTRAT_RENOUV_ANNUEL_TTC' => $contrat['contrat_renouv_annuel_ttc'] ?? self::calcRenouvAnnuelTtc($contrat),
            'ACTIVITES' => $activitiesList,
            'ACTIVITES_INLINE' => $activitiesInline,
            'ACTIVITES_PLAIN' => $activitiesInline,
            'ACTIVITES_PUCES' => $activitiesBullets,
            'ACTIVITES_SUITE_PUCES' => $activitiesContinuationBullets,
            'NB_ACTIVITES' => (string) $activitiesCount,
            'ACTIVITES_OMPIC' => $certNegList,
            'OMPIC_INLINE' => $certNegInline,
            'OMPIC_PUCES' => $certNegBullets,
            'NB_OMPIC' => (string) $certNegCount,
            'DATE' => $now->format('d/m/Y'),
            'DATE_LONG' => strtr($now->format('d F Y'), [
                'January' => 'Janvier', 'February' => 'Fevrier', 'March' => 'Mars',
                'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
                'July' => 'Juillet', 'August' => 'Aout', 'September' => 'Septembre',
                'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Decembre',
            ]),
            'ANNEE' => $now->format('Y'),
            'MOIS' => $now->format('m'),
            'JOUR' => $now->format('d'),
        ];
    }

    public static function buildContextFromCession(PDO $pdo, int $cessionId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM cessions WHERE id = :id');
        $stmt->execute(['id' => $cessionId]);
        $cession = $stmt->fetch();

        if (!$cession) {
            return [];
        }

        $societe = fetch_record($pdo, 'societes', (int) $cession['societe_id']);
        if (!$societe) {
            return [];
        }

        $stmt = $pdo->prepare('SELECT * FROM cession_parts WHERE cession_id = :id ORDER BY id');
        $stmt->execute(['id' => $cessionId]);
        $cessionParts = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM associes WHERE societe_id = :id ORDER BY id');
        $stmt->execute(['id' => $societe['id']]);
        $associes = $stmt->fetchAll();

        $now = new DateTime();

        $capitalAvant = (float) ($cession['capital_avant'] ?? $societe['societe_capital'] ?? 0);
        $partsAvant = (int) ($cession['parts_avant'] ?? $societe['societe_part_social'] ?? 0);

        $totalPartsCedees = 0;
        $totalPrix = 0;
        foreach ($cessionParts as $part) {
            $totalPartsCedees += (int) ($part['parts_cedees'] ?? 0);
            $totalPrix += (float) ($part['prix_total'] ?? 0);
        }

        $capitalApres = $capitalAvant;
        $partsApres = $partsAvant - $totalPartsCedees;

        $firstPart = $cessionParts[0] ?? [];

        // Look up cedant additional info from associes table
        $cedantInfo = [];
        $cedantAssocieId = $firstPart['cedant_associe_id'] ?? 0;
        if ($cedantAssocieId > 0) {
            $stmtCed = $pdo->prepare('SELECT * FROM associes WHERE id = :id');
            $stmtCed->execute(['id' => $cedantAssocieId]);
            $cedDb = $stmtCed->fetch();
            if ($cedDb) {
                $cedantInfo = $cedDb;
            }
        }

        $associeList = [];
        foreach ($associes as $a) {
            $nomComplet = $a['associe_nom_complet'] ?? '';
            $nomParts = explode(' ', $nomComplet, 2);
            $prenom = count($nomParts) > 1 ? $nomParts[0] : '';
            $nom = count($nomParts) > 1 ? $nomParts[1] : $nomComplet;
            $associeList[] = [
                'associe_nom' => $nom,
                'associe_prenom' => $prenom,
                'associe_cin' => $a['associe_cin'] ?? '',
                'associe_nationalite' => $a['associe_nationalite'] ?? '',
                'associe_qualite' => $a['associe_qualite'] ?? '',
                'associe_parts' => $a['associe_parts'] ?? '',
                'associe_est_gerant' => (int) ($a['associe_est_gerant'] ?? 0) === 1 ? 'Gerant' : 'Associe',
                'associe_civilite' => $a['associe_civilite'] ?? 'M.',
                'adresse' => $a['associe_adresse'] ?? '',
                'email' => $a['associe_email'] ?? '',
                'telephone' => $a['associe_telephone'] ?? '',
                'associe_date_naissance' => $a['associe_date_naissance'] ?? '',
                'associe_lieu_naissance' => $a['associe_lieu_naissance'] ?? '',
                'associe_capital_detenu' => $a['associe_capital_detenu'] ?? '',
            ];
        }

        $firstAssocie = $associeList[0] ?? [];
        $fNom = $firstAssocie['associe_nom'] ?? '';
        $fPrenom = $firstAssocie['associe_prenom'] ?? '';
        $fCivilite = $firstAssocie['associe_civilite'] ?? 'M.';
        $fNomComplet = trim("$fCivilite $fPrenom $fNom");

        return [
            'societe' => $societe,
            'associes' => $associeList,
            'cession_parts' => $cessionParts,
            'cession' => $cession,
            'SOCIETE_RAISON_SOCIALE' => $societe['societe_raison_sociale'] ?? '',
            'SOCIETE_FORME_JURIDIQUE' => $societe['societe_forme_juridique'] ?? '',
            'SOCIETE_ICE' => $societe['societe_ice'] ?? '',
            'SOCIETE_RC' => $societe['societe_rc'] ?? '',
            'SOCIETE_IF' => $societe['societe_if'] ?? '',
            'SOCIETE_TP' => $societe['societe_tp'] ?? '',
            'SOCIETE_CNSS' => $societe['societe_cnss'] ?? '',
            'SOCIETE_CAPITAL' => (string) ($societe['societe_capital'] ?? ''),
            'SOCIETE_PART_SOCIAL' => (string) ($societe['societe_part_social'] ?? ''),
            'SOCIETE_VALEUR_NOMINALE' => (string) ($societe['societe_valeur_nominale'] ?? ''),
            'SOCIETE_VILLE' => $societe['societe_ville'] ?? '',
            'SOCIETE_TRIBUNAL' => $societe['societe_tribunal'] ?? '',
            'SOCIETE_ADRESSE_SIEGE' => $societe['societe_adresse_siege'] ?? '',
            'SOCIETE_EMAIL' => $societe['societe_email'] ?? '',
            'SOCIETE_TELEPHONE' => $societe['societe_telephone'] ?? '',
            'SOCIETE_DOSSIER' => $societe['societe_dossier'] ?? '',
            'SOCIETE_DATE_ICE' => self::formatDate($societe['societe_date_ice'] ?? ''),
            'SOCIETE_DATE_EXP_CERT_NEG' => self::formatDate($societe['societe_date_exp_cert_neg'] ?? ''),
            'ASSOCIE_NOM_COMPLET' => $fNomComplet,
            'ASSOCIE_NOM' => $fNom,
            'ASSOCIE_PRENOM' => $fPrenom,
            'ASSOCIE_CIVILITE' => $fCivilite,
            'ASSOCIE_CIN' => $firstAssocie['associe_cin'] ?? '',
            'ASSOCIE_DATE_VALIDITE_CIN' => self::formatDate($firstAssocie['associe_date_validite_cin'] ?? ''),
            'ASSOCIE_DATE_NAISSANCE' => self::formatDate($firstAssocie['associe_date_naissance'] ?? ''),
            'ASSOCIE_NATIONALITE' => $firstAssocie['associe_nationalite'] ?? '',
            'ASSOCIE_ADRESSE' => $firstAssocie['adresse'] ?? '',
            'ASSOCIE_QUALITE' => $firstAssocie['associe_qualite'] ?? '',
            'ASSOCIE_PARTS' => (string) ($firstAssocie['associe_parts'] ?? ''),
            'ASSOCIE_EST_GERANT' => $firstAssocie['associe_est_gerant'] ?? '',
            'CESSION_DATE' => self::formatDate($cession['cession_date'] ?? ''),
            'CESSION_DOSSIER' => $cession['cession_dossier'] ?? '',
            'CESSION_STATUS' => $cession['cession_status'] ?? 'brouillon',
            'CESSION_MOTIF' => $cession['cession_motif'] ?? '',
            'CEDANT_NOM_COMPLET' => $firstPart['cedant_nom_complet'] ?? '',
            'CEDANT_CIN' => $firstPart['cedant_cin'] ?? '',
            'CEDANT_CIVILITE' => $cedantInfo['associe_civilite'] ?? '',
            'CEDANT_DATE_NAISSANCE' => self::formatDate($cedantInfo['associe_date_naissance'] ?? ''),
            'CEDANT_LIEU_NAISSANCE' => $cedantInfo['associe_lieu_naissance'] ?? '',
            'CEDANT_NATIONALITE' => $cedantInfo['associe_nationalite'] ?? $firstPart['cedant_nationalite'] ?? '',
            'CEDANT_ADRESSE' => $cedantInfo['associe_adresse'] ?? '',
            'CESSIONNAIRE_NOM_COMPLET' => $firstPart['cessionnaire_nom_complet'] ?? '',
            'CESSIONNAIRE_CIN' => $firstPart['cessionnaire_cin'] ?? '',
            'CESSIONNAIRE_CIVILITE' => $firstPart['cessionnaire_civilite'] ?? '',
            'CESSIONNAIRE_DATE_NAISSANCE' => self::formatDate($firstPart['cessionnaire_date_naissance'] ?? ''),
            'CESSIONNAIRE_LIEU_NAISSANCE' => $firstPart['cessionnaire_lieu_naissance'] ?? '',
            'CESSIONNAIRE_NATIONALITE' => $firstPart['cessionnaire_nationalite'] ?? '',
            'CESSIONNAIRE_ADRESSE' => $firstPart['cessionnaire_adresse'] ?? '',
            'CESSIONNAIRE_TELEPHONE' => $firstPart['cessionnaire_telephone'] ?? '',
            'CESSIONNAIRE_EMAIL' => $firstPart['cessionnaire_email'] ?? '',
            'CESSIONNAIRE_QUALITE' => $firstPart['cessionnaire_qualite'] ?? '',
            'CESSIONNAIRE_PARTS' => (string) ($firstPart['cessionnaire_parts'] ?? ''),
            'CESSIONNAIRE_CAPITAL_DETENU' => (string) ($firstPart['cessionnaire_capital_detenu'] ?? ''),
            'CESSIONNAIRE_EST_GERANT' => $firstPart['cessionnaire_est_gerant'] ?? '',
            'PARTS_CEDEES' => (string) $totalPartsCedees,
            'PRIX_UNITAIRE' => (string) ($firstPart['prix_unitaire'] ?? ''),
            'PRIX_TOTAL' => (string) $totalPrix,
            'CAPITAL_AVANT' => (string) $capitalAvant,
            'CAPITAL_APRES' => (string) $capitalApres,
            'PARTS_AVANT' => (string) $partsAvant,
            'PARTS_APRES' => (string) $partsApres,
            'NB_CEDANTS' => (string) count($cessionParts),
            'NB_CESSIONNAIRES' => (string) count($cessionParts),
            'DATE' => $now->format('d/m/Y'),
            'DATE_LONG' => strtr($now->format('d F Y'), [
                'January' => 'Janvier', 'February' => 'Fevrier', 'March' => 'Mars',
                'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
                'July' => 'Juillet', 'August' => 'Aout', 'September' => 'Septembre',
                'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Decembre',
            ]),
            'ANNEE' => $now->format('Y'),
            'MOIS' => $now->format('m'),
            'JOUR' => $now->format('d'),
        ];
    }

    public static function buildContextFromPvAgo(PDO $pdo, int $pvAgoId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM pv_ago WHERE id = :id');
        $stmt->execute(['id' => $pvAgoId]);
        $pv = $stmt->fetch();

        if (!$pv) {
            return [];
        }

        $societe = fetch_record($pdo, 'societes', (int) $pv['societe_id']);
        if (!$societe) {
            return [];
        }

        $now = new DateTime();
        $capital = (float) ($pv['capital_social'] ?? $societe['societe_capital'] ?? 0);
        $totalParts = (int) ($pv['total_parts'] ?? $societe['societe_part_social'] ?? 0);
        $partsPresentes = (int) ($pv['parts_presentes'] ?? $totalParts);
        $resultatNet = (float) ($pv['resultat_net'] ?? 0);
        $isBenefice = ($pv['resultat_type'] ?? 'benefice') === 'benefice';
        $reportDebiteur = (float) ($pv['report_a_nouveau_debiteur'] ?? 0);
        $reserveLegaleExistante = (float) ($pv['reserve_legale_existante'] ?? 0);
        $reserveStatutaireExistante = (float) ($pv['reserve_statutaire_existante'] ?? 0);
        $reserveFacultativeExistante = (float) ($pv['reserve_facultative_existante'] ?? 0);
        $affectation = $pv['affectation_option'] ?? '';
        $dividendeTotal = (float) ($pv['dividende_total'] ?? 0);
        $reserveStatutaireDotation = (float) ($pv['reserve_statutaire_dotation'] ?? 0);
        $reserveFacultativeDotation = (float) ($pv['reserve_facultative_dotation'] ?? 0);
        $perteReservePrelevement = (float) ($pv['perte_reserve_prelevement'] ?? 0);

        // Calculations
        $plafondReserveLegale = $capital * 0.10;
        $baseReserveLegale = max(0, $resultatNet - $reportDebiteur);
        $reserveLegaleDotation = 0;
        if ($isBenefice && $baseReserveLegale > 0) {
            $dotationCalculee = $baseReserveLegale * 0.05;
            $reserveLegaleDotation = min($dotationCalculee, max(0, $plafondReserveLegale - $reserveLegaleExistante));
        }
        $newReserveLegale = $reserveLegaleExistante + $reserveLegaleDotation;

        $tpaMontant = $dividendeTotal * 0.10;
        $dividendeNet = $dividendeTotal - $tpaMontant;

        $reportNouveau = 0;
        if ($isBenefice) {
            if ($affectation === 'profit_distribution') {
                $reportNouveau = $baseReserveLegale - $reserveLegaleDotation - $reserveStatutaireDotation - $reserveFacultativeDotation - $dividendeTotal;
            }
        } else {
            if ($affectation === 'loss_carryforward') {
                $reportNouveau = -$resultatNet;
            } else {
                $reportNouveau = 0;
            }
        }

        $resultatLib = $isBenefice
            ? 'Un benefice net comptable de ' . number_format($resultatNet, 2, ',', ' ') . ' DH'
            : 'Une perte nette comptable de ' . number_format(abs($resultatNet), 2, ',', ' ') . ' DH';

        $calculLines = [];
        $calculLines[] = 'Resultat net de l\'exercice : ' . number_format($resultatNet, 2, ',', ' ') . ' DH';
        if ($reportDebiteur > 0) {
            $calculLines[] = 'Report a nouveau debiteur anterieur : ' . number_format($reportDebiteur, 2, ',', ' ') . ' DH';
            $calculLines[] = 'Base de calcul reserve legale : ' . number_format($baseReserveLegale, 2, ',', ' ') . ' DH';
        }
        if ($isBenefice) {
            if ($reserveLegaleDotation > 0) {
                $calculLines[] = 'Reserve legale (5%) : ' . number_format($reserveLegaleDotation, 2, ',', ' ') . ' DH (plafond ' . number_format($plafondReserveLegale, 2, ',', ' ') . ' DH)';
            } else {
                $calculLines[] = 'Reserve legale : plafond atteint ou non applicable';
            }
            if ($reserveStatutaireDotation > 0) {
                $calculLines[] = 'Reserve statutaire : ' . number_format($reserveStatutaireDotation, 2, ',', ' ') . ' DH';
            }
            if ($reserveFacultativeDotation > 0) {
                $calculLines[] = 'Reserve facultative : ' . number_format($reserveFacultativeDotation, 2, ',', ' ') . ' DH';
            }
            if ($dividendeTotal > 0) {
                $calculLines[] = 'Dividendes : ' . number_format($dividendeTotal, 2, ',', ' ') . ' DH';
                $calculLines[] = 'TPA (10%) : ' . number_format($tpaMontant, 2, ',', ' ') . ' DH';
                $calculLines[] = 'Dividendes nets verses : ' . number_format($dividendeNet, 2, ',', ' ') . ' DH';
            }
            $calculLines[] = 'Report a nouveau crediteur (solde) : ' . number_format(max(0, $reportNouveau), 2, ',', ' ') . ' DH';
        } else {
            if ($affectation === 'loss_carryforward') {
                $calculLines[] = 'Perte reportee a nouveau : ' . number_format(abs($resultatNet), 2, ',', ' ') . ' DH';
            } else {
                $calculLines[] = 'Perte imputee sur reserves : ' . number_format($perteReservePrelevement, 2, ',', ' ') . ' DH';
            }
        }
        $calculDetail = implode("\n", $calculLines);

        // Ordre du jour
        $ordreJour = "1. Rapport de gestion de la gerance sur l'activite et le resultat de l'exercice clos le " . ($pv['exercice_clos'] ?? '31/12/2025') . "\n"
            . "2. Lecture du bilan, du compte de produits et charges (CPC), de l'etat des soldes de gestion (ESG) et des annexes\n"
            . "3. Approbation des etats de synthese et quitus a la gerance\n"
            . "4. Affectation du resultat de l'exercice\n"
            . "5. Pouvoirs pour l'accomplissement des formalites legales de depot et de publicite";

        $resolutions = [];
        if (!empty($pv['resolutions'])) {
            $parsed = json_decode($pv['resolutions'], true);
            if (is_array($parsed)) {
                $resolutions = $parsed;
            }
        }

        return [
            'pv_ago' => $pv,
            'societe' => $societe,
            'SOCIETE_RAISON_SOCIALE' => $societe['societe_raison_sociale'] ?? '',
            'SOCIETE_FORME_JURIDIQUE' => $societe['societe_forme_juridique'] ?? '',
            'SOCIETE_ICE' => $societe['societe_ice'] ?? '',
            'SOCIETE_RC' => $societe['societe_rc'] ?? '',
            'SOCIETE_IF' => $societe['societe_if'] ?? '',
            'SOCIETE_TP' => $societe['societe_tp'] ?? '',
            'SOCIETE_CNSS' => $societe['societe_cnss'] ?? '',
            'SOCIETE_CAPITAL' => (string) $capital,
            'SOCIETE_PART_SOCIAL' => (string) $totalParts,
            'SOCIETE_VALEUR_NOMINALE' => (string) ($societe['societe_valeur_nominale'] ?? ''),
            'SOCIETE_VILLE' => $societe['societe_ville'] ?? '',
            'SOCIETE_TRIBUNAL' => $societe['societe_tribunal'] ?? '',
            'SOCIETE_ADRESSE_SIEGE' => $societe['societe_adresse_siege'] ?? '',
            'SOCIETE_EMAIL' => $societe['societe_email'] ?? '',
            'SOCIETE_TELEPHONE' => $societe['societe_telephone'] ?? '',
            'SOCIETE_DOSSIER' => $societe['societe_dossier'] ?? '',
            // PV AGO specifics
            'PV_AGO_DATE' => self::formatDate($pv['date_ago'] ?? ''),
            'PV_AGO_HEURE' => $pv['heure_ago'] ?? '10:00',
            'PV_AGO_LIEU' => $pv['lieu_ago'] ?? 'au siege social',
            'PV_AGO_PRESIDENT_NOM' => $pv['president_nom'] ?? '',
            'PV_AGO_PRESIDENT_QUALITE' => $pv['president_qualite'] ?? 'Gerant',
            'PV_AGO_EXERCICE_CLOS' => $pv['exercice_clos'] ?? '',
            'PV_AGO_RESULTAT_NET' => number_format($resultatNet, 2, ',', ' '),
            'PV_AGO_RESULTAT_TYPE' => $isBenefice ? 'benefice' : 'perte',
            'PV_AGO_RESULTAT_LIB' => $resultatLib,
            'PV_AGO_BASE_RESERVE_LEGALE' => number_format($baseReserveLegale, 2, ',', ' '),
            'PV_AGO_RESERVE_LEGALE_EXISTANTE' => number_format($reserveLegaleExistante, 2, ',', ' '),
            'PV_AGO_RESERVE_LEGALE_PLAFOND' => number_format($plafondReserveLegale, 2, ',', ' '),
            'PV_AGO_RESERVE_LEGALE_DOTATION' => number_format($reserveLegaleDotation, 2, ',', ' '),
            'PV_AGO_RESERVE_STATUTAIRE_DOTATION' => number_format($reserveStatutaireDotation, 2, ',', ' '),
            'PV_AGO_RESERVE_FACULTATIVE_DOTATION' => number_format($reserveFacultativeDotation, 2, ',', ' '),
            'PV_AGO_RESERVE_FACULTATIVE_PRELEVEMENT' => number_format($perteReservePrelevement, 2, ',', ' '),
            'PV_AGO_RESERVE_STATUTAIRE_EXISTANTE' => number_format($reserveStatutaireExistante, 2, ',', ' '),
            'PV_AGO_RESERVE_FACULTATIVE_EXISTANTE' => number_format($reserveFacultativeExistante, 2, ',', ' '),
            'PV_AGO_REPORT_DEBITEUR' => number_format($reportDebiteur, 2, ',', ' '),
            'PV_AGO_DIVIDENDE_BRUT' => number_format($dividendeTotal, 2, ',', ' '),
            'PV_AGO_TPA_MONTANT' => number_format($tpaMontant, 2, ',', ' '),
            'PV_AGO_DIVIDENDE_NET' => number_format($dividendeNet, 2, ',', ' '),
            'PV_AGO_REPORT_A_NOUVEAU_SOLDE' => number_format(max(0, $reportNouveau), 2, ',', ' '),
            'PV_AGO_CALCUL_DETAIL' => $calculDetail,
            'PV_AGO_TOTAL_PARTS' => (string) $totalParts,
            'PV_AGO_PARTS_PRESENTES' => (string) $partsPresentes,
            'PV_AGO_ORDRE_JOUR' => $ordreJour,
            // Dates auto
            'DATE' => $now->format('d/m/Y'),
            'DATE_LONG' => strtr($now->format('d F Y'), [
                'January' => 'Janvier', 'February' => 'Fevrier', 'March' => 'Mars',
                'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
                'July' => 'Juillet', 'August' => 'Aout', 'September' => 'Septembre',
                'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Decembre',
            ]),
            'ANNEE' => $now->format('Y'),
            'MOIS' => $now->format('m'),
            'JOUR' => $now->format('d'),
            // PV resolutions
            'PV_ORDER_ITEMS' => $ordreJour,
        ];
    }

    public static function buildContextFromSession(array $wizard, ?PDO $pdo = null): array
    {
        $societe = $wizard['societe'] ?? [];
        $associes = $wizard['associes'] ?? [];
        $contrat = $wizard['contrat'] ?? [];

        $denSte = $societe['societe_raison_sociale'] ?? '';
        $formeJur = $societe['societe_forme_juridique'] ?? '';
        $capital = $societe['societe_capital'] ?? '';
        $partSocial = $societe['societe_part_social'] ?? '';
        $ice = $societe['societe_ice'] ?? '';
        $steAdress = $societe['societe_adresse_siege'] ?? $societe['adresse'] ?? '';
        $tribunal = $societe['societe_tribunal'] ?? '';
        $ville = $societe['societe_ville'] ?? '';

        $associeList = [];
        foreach ($associes as $a) {
            $nomComplet = $a['associe_nom_complet'] ?? '';
            $nomParts = explode(' ', $nomComplet, 2);
            $prenom = count($nomParts) > 1 ? $nomParts[0] : '';
            $nom = count($nomParts) > 1 ? $nomParts[1] : $nomComplet;

            $associeList[] = [
                'associe_nom' => $nom,
                'associe_prenom' => $prenom,
                'associe_cin' => $a['associe_cin'] ?? '',
                'associe_nationalite' => $a['associe_nationalite'] ?? '',
                'associe_qualite' => $a['associe_qualite'] ?? '',
                'associe_parts' => $a['associe_parts'] ?? '',
                'associe_est_gerant' => ((string) ($a['associe_est_gerant'] ?? '0') === '1') ? 'Gerant' : 'Associe',
                'associe_civilite' => $a['associe_civilite'] ?? 'M.',
                'adresse' => $a['associe_adresse'] ?? '',
                'email' => $a['associe_email'] ?? '',
                'telephone' => $a['associe_telephone'] ?? '',
                'associe_date_naissance' => $a['associe_date_naissance'] ?? '',
                'associe_lieu_naissance' => $a['associe_lieu_naissance'] ?? '',
                'associe_capital_detenu' => $a['associe_capital_detenu'] ?? '',
            ];
        }

        $firstAssocie = $associeList[0] ?? [];
        $fNom = $firstAssocie['associe_nom'] ?? '';
        $fPrenom = $firstAssocie['associe_prenom'] ?? '';
        $fCivilite = $firstAssocie['associe_civilite'] ?? 'M.';
        $fNomComplet = trim("$fCivilite $fPrenom $fNom");

        $now = new DateTime();
        $dateContrat = $contrat['contrat_date'] ?? ($contrat['contrat_date_debut'] ?? $now->format('Y-m-d'));
        $dateDebut = $contrat['contrat_date_debut'] ?? $dateContrat;
        $dateFin = $contrat['contrat_date_fin'] ?? '';
        $dureeMois = $contrat['contrat_duree_mois'] ?? '';

        $activitiesList = [];
        $activitiesInline = '';
        $activitiesBullets = '';
        $activitiesContinuationBullets = '';
        $activitiesCount = 0;

        if ($pdo !== null) {
            try {
                $activitesStmt = $pdo->query('SELECT activite FROM ref_activites ORDER BY sort_order, activite');
                $allActivities = array_map(static fn(array $row): string => $row['activite'], $activitesStmt->fetchAll());
                $activitiesList = $allActivities;
                $activitiesCount = count($activitiesList);
                $activitiesInline = implode(', ', $activitiesList);
                foreach ($activitiesList as $i => $act) {
                    $prefix = '  \\item ';
                    $activitiesBullets .= $prefix . $act . "\n";
                    if ($i < $activitiesCount - 1) {
                        $activitiesContinuationBullets .= $prefix . $act . "\n";
                    }
                }
            } catch (Throwable $e) {
            }
        }

        $certNegCode = !empty($societe['societe_activites_ompic']) ? (string) $societe['societe_activites_ompic'] : '';
        $certNegList = [];
        $certNegInline = '';
        $certNegBullets = '';
        $certNegCount = 0;
        if ($certNegCode !== '' && $pdo !== null) {
            try {
                $nmaStmt = $pdo->prepare("SELECT CONCAT(code, ' - ', libelle) AS display FROM ref_activites_ompic WHERE code = :code LIMIT 1");
                $nmaStmt->execute(['code' => $certNegCode]);
                $nmaRow = $nmaStmt->fetch();
                $certNegList = $nmaRow ? [$nmaRow['display']] : [$certNegCode];
                $certNegCount = count($certNegList);
                $certNegInline = implode(', ', $certNegList);
                foreach ($certNegList as $act) {
                    $certNegBullets .= '  \\item ' . $act . "\n";
                }
            } catch (Throwable) {
                $certNegList = [$certNegCode];
                $certNegCount = 1;
                $certNegInline = $certNegCode;
            }
        }

        return [
            'societe' => $societe,
            'associes' => $associeList,
            'contrat' => $contrat,
            'activities' => $activitiesList,
            'SOCIETE_RAISON_SOCIALE' => $denSte,
            'SOCIETE_FORME_JURIDIQUE' => $formeJur,
            'SOCIETE_ICE' => $ice,
            'SOCIETE_RC' => $societe['societe_rc'] ?? '',
            'SOCIETE_IF' => $societe['societe_if'] ?? '',
            'SOCIETE_CAPITAL' => $capital,
            'SOCIETE_PART_SOCIAL' => $partSocial,
            'SOCIETE_VALEUR_NOMINALE' => $societe['societe_valeur_nominale'] ?? '',
            'SOCIETE_VILLE' => $ville,
            'SOCIETE_TRIBUNAL' => $tribunal,
            'SOCIETE_ADRESSE_SIEGE' => $steAdress,
            'SOCIETE_EMAIL' => $societe['societe_email'] ?? '',
            'SOCIETE_TELEPHONE' => $societe['societe_telephone'] ?? '',
            'SOCIETE_DOSSIER' => $societe['societe_dossier'] ?? '',
            'SOCIETE_TYPE_GENERATION' => $societe['societe_type_generation'] ?? '',
            'SOCIETE_PROCEDURE_CREATION' => $societe['societe_procedure_creation'] ?? '',
            'SOCIETE_MODE_DEPOT' => $societe['societe_mode_depot'] ?? '',
            'SOCIETE_TRIBUNAL_TYPE' => $societe['societe_tribunal_type'] ?? '',
            'SOCIETE_DATE_ICE' => self::formatDate($societe['societe_date_ice'] ?? ''),
            'SOCIETE_DATE_EXP_CERT_NEG' => self::formatDate($societe['societe_date_exp_cert_neg'] ?? ''),
            'ASSOCIE_NOM_COMPLET' => $fNomComplet,
            'ASSOCIE_NOM' => $fNom,
            'ASSOCIE_PRENOM' => $fPrenom,
            'ASSOCIE_CIVILITE' => $fCivilite,
            'ASSOCIE_CIN' => $firstAssocie['associe_cin'] ?? '',
            'ASSOCIE_DATE_VALIDITE_CIN' => self::formatDate($firstAssocie['associe_date_validite_cin'] ?? ''),
            'ASSOCIE_DATE_NAISSANCE' => self::formatDate($firstAssocie['associe_date_naissance'] ?? ''),
            'ASSOCIE_LIEU_NAISSANCE' => $firstAssocie['associe_lieu_naissance'] ?? '',
            'ASSOCIE_NATIONALITE' => $firstAssocie['associe_nationalite'] ?? '',
            'ASSOCIE_ADRESSE' => $firstAssocie['adresse'] ?? '',
            'ASSOCIE_TELEPHONE' => $firstAssocie['telephone'] ?? '',
            'ASSOCIE_EMAIL' => $firstAssocie['email'] ?? '',
            'ASSOCIE_QUALITE' => $firstAssocie['associe_qualite'] ?? '',
            'ASSOCIE_PARTS' => $firstAssocie['associe_parts'] ?? '',
            'ASSOCIE_CAPITAL_DETENU' => $firstAssocie['associe_capital_detenu'] ?? '',
            'ASSOCIE_EST_GERANT' => $firstAssocie['associe_est_gerant'] ?? '',
            'CONTRAT_TYPE' => $contrat['contrat_type'] ?? '',
            'CONTRAT_TYPE_DOMICILIATION' => $contrat['contrat_type_domiciliation'] ?? '',
            'CONTRAT_DATE' => self::formatDate($dateContrat),
            'CONTRAT_DATE_DEBUT' => self::formatDate($dateDebut),
            'CONTRAT_DATE_FIN' => self::formatDate($dateFin),
            'CONTRAT_DUREE_MOIS' => $dureeMois,
            'CONTRAT_LOYER_TTC' => $contrat['contrat_loyer_ttc'] ?? '',
            'CONTRAT_LOYER_HT' => $contrat['contrat_loyer_ht'] ?? '',
            'CONTRAT_TVA_POURCENT' => $contrat['contrat_tva_pourcent'] ?? '',
            'CONTRAT_TOTAL_HT' => $contrat['contrat_total_ht'] ?? '',
            'CONTRAT_FRAIS_INTERMEDIAIRE' => $contrat['contrat_frais_intermediaire'] ?? '',
            'CONTRAT_CAUTION' => $contrat['contrat_caution'] ?? '',
            'CONTRAT_STATUT' => $contrat['contrat_statut'] ?? '',
            'CONTRAT_MODE_SIGNATURE' => $contrat['contrat_mode_signature'] ?? '',
            'CONTRAT_PACK_MONTANT_TTC' => $contrat['contrat_pack_montant_ttc'] ?? self::calcPackMontantTtc($contrat),
            'CONTRAT_PACK_LOYER_TTC' => $contrat['contrat_pack_loyer_ttc'] ?? ($contrat['contrat_loyer_ttc'] ?? ''),
            'CONTRAT_TYPE_RENOUVELLEMENT' => $contrat['contrat_type_renouvellement'] ?? '',
            'CONTRAT_RENOUV_TVA_POURCENT' => $contrat['contrat_renouv_tva_pourcent'] ?? '',
            'CONTRAT_RENOUV_LOYER_HT' => $contrat['contrat_renouv_loyer_ht'] ?? '',
            'CONTRAT_RENOUV_LOYER_TTC' => $contrat['contrat_renouv_loyer_ttc'] ?? '',
            'CONTRAT_RENOUV_ANNUEL_TTC' => $contrat['contrat_renouv_annuel_ttc'] ?? self::calcRenouvAnnuelTtc($contrat),
            'ACTIVITES' => $activitiesList,
            'ACTIVITES_INLINE' => $activitiesInline,
            'ACTIVITES_PLAIN' => $activitiesInline,
            'ACTIVITES_PUCES' => $activitiesBullets,
            'ACTIVITES_SUITE_PUCES' => $activitiesContinuationBullets,
            'NB_ACTIVITES' => (string) $activitiesCount,
            'ACTIVITES_OMPIC' => $certNegList,
            'OMPIC_INLINE' => $certNegInline,
            'OMPIC_PUCES' => $certNegBullets,
            'NB_OMPIC' => (string) $certNegCount,
            'DATE' => $now->format('d/m/Y'),
            'DATE_LONG' => $now->format('d F Y'),
            'ANNEE' => $now->format('Y'),
            'MOIS' => $now->format('m'),
            'JOUR' => $now->format('d'),
        ];
    }

}
