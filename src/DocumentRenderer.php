<?php

declare(strict_types=1);

class DocumentRenderer
{
    private string $templatePath;
    private string $outputDir;

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
        return $xml;
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

    private function processActivityLoop(string $xml, array $context): string
    {
        $list = $context['activities'] ?? $context['ACTIVITIES_LIST'] ?? [];
        if (is_string($list)) {
            $list = [$list];
        }

        $patterns = [
            '/\{\%p\s+for\s+a\s+in\s+ACTIVITIES_LIST\s*\%\}(.*?)\{\%p\s+endfor\s*\%\}/s',
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
            'SOCIETE_DATE_ICE' => $societe['societe_date_ice'] ?? '',
            'SOCIETE_DATE_EXP_CERT_NEG' => $societe['societe_date_exp_cert_neg'] ?? '',
            'ASSOCIE_NOM_COMPLET' => $fNomComplet,
            'ASSOCIE_NOM' => $fNom,
            'ASSOCIE_PRENOM' => $fPrenom,
            'ASSOCIE_CIVILITE' => $fCivilite,
            'ASSOCIE_CIN' => $firstAssocie['associe_cin'] ?? '',
            'ASSOCIE_DATE_VALIDITE_CIN' => $firstAssocie['associe_date_validite_cin'] ?? '',
            'ASSOCIE_DATE_NAISSANCE' => $firstAssocie['associe_date_naissance'] ?? '',
            'ASSOCIE_LIEU_NAISSANCE' => $firstAssocie['associe_lieu_naissance'] ?? '',
            'ASSOCIE_NATIONALITE' => $firstAssocie['associe_nationalite'] ?? '',
            'ASSOCIE_ADRESSE' => $firstAssocie['associe_adresse'] ?? '',
            'ASSOCIE_TELEPHONE' => $firstAssocie['associe_telephone'] ?? '',
            'ASSOCIE_EMAIL' => $firstAssocie['associe_email'] ?? '',
            'ASSOCIE_QUALITE' => $firstAssocie['associe_qualite'] ?? '',
            'ASSOCIE_PARTS' => $firstAssocie['associe_parts'] ?? '',
            'ASSOCIE_CAPITAL_DETENU' => $firstAssocie['associe_capital_detenu'] ?? '',
            'ASSOCIE_EST_GERANT' => $firstAssocie['associe_est_gerant'] ?? '',
            'CONTRAT_TYPE' => $contrat['contrat_type'] ?? '',
            'CONTRAT_TYPE_DOMICILIATION' => $contrat['contrat_type_domiciliation'] ?? '',
            'CONTRAT_DATE' => $dateContrat,
            'CONTRAT_DATE_DEBUT' => $dateDebut,
            'CONTRAT_DATE_FIN' => $dateFin,
            'CONTRAT_DUREE_MOIS' => $dureeMois,
            'CONTRAT_LOYER_TTC' => $contrat['contrat_loyer_ttc'] ?? '',
            'CONTRAT_LOYER_HT' => $contrat['contrat_loyer_ht'] ?? '',
            'CONTRAT_TVA_POURCENT' => $contrat['contrat_tva_pourcent'] ?? '',
            'CONTRAT_TOTAL_HT' => $contrat['contrat_total_ht'] ?? '',
            'CONTRAT_FRAIS_INTERMEDIAIRE' => $contrat['contrat_frais_intermediaire'] ?? '',
            'CONTRAT_CAUTION' => $contrat['contrat_caution'] ?? '',
            'CONTRAT_STATUT' => $contrat['contrat_statut'] ?? '',
            'CONTRAT_MODE_SIGNATURE' => $contrat['contrat_mode_signature'] ?? '',
            'CONTRAT_PACK_MONTANT_TTC' => $contrat['contrat_pack_montant_ttc'] ?? '',
            'CONTRAT_PACK_LOYER_TTC' => $contrat['contrat_pack_loyer_ttc'] ?? '',
            'CONTRAT_TYPE_RENOUVELLEMENT' => $contrat['contrat_type_renouvellement'] ?? '',
            'CONTRAT_RENOUV_TVA_POURCENT' => $contrat['contrat_renouv_tva_pourcent'] ?? '',
            'CONTRAT_RENOUV_LOYER_HT' => $contrat['contrat_renouv_loyer_ht'] ?? '',
            'CONTRAT_RENOUV_LOYER_TTC' => $contrat['contrat_renouv_loyer_ttc'] ?? '',
            'CONTRAT_RENOUV_ANNUEL_TTC' => $contrat['contrat_renouv_annuel_ttc'] ?? '',
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
            'SOCIETE_DATE_ICE' => $societe['societe_date_ice'] ?? '',
            'SOCIETE_DATE_EXP_CERT_NEG' => $societe['societe_date_exp_cert_neg'] ?? '',
            'ASSOCIE_NOM_COMPLET' => $fNomComplet,
            'ASSOCIE_NOM' => $fNom,
            'ASSOCIE_PRENOM' => $fPrenom,
            'ASSOCIE_CIVILITE' => $fCivilite,
            'ASSOCIE_CIN' => $firstAssocie['associe_cin'] ?? '',
            'ASSOCIE_DATE_VALIDITE_CIN' => $firstAssocie['associe_date_validite_cin'] ?? '',
            'ASSOCIE_DATE_NAISSANCE' => $firstAssocie['associe_date_naissance'] ?? '',
            'ASSOCIE_LIEU_NAISSANCE' => $firstAssocie['associe_lieu_naissance'] ?? '',
            'ASSOCIE_NATIONALITE' => $firstAssocie['associe_nationalite'] ?? '',
            'ASSOCIE_ADRESSE' => $firstAssocie['associe_adresse'] ?? '',
            'ASSOCIE_TELEPHONE' => $firstAssocie['associe_telephone'] ?? '',
            'ASSOCIE_EMAIL' => $firstAssocie['associe_email'] ?? '',
            'ASSOCIE_QUALITE' => $firstAssocie['associe_qualite'] ?? '',
            'ASSOCIE_PARTS' => $firstAssocie['associe_parts'] ?? '',
            'ASSOCIE_CAPITAL_DETENU' => $firstAssocie['associe_capital_detenu'] ?? '',
            'ASSOCIE_EST_GERANT' => $firstAssocie['associe_est_gerant'] ?? '',
            'CONTRAT_TYPE' => $contrat['contrat_type'] ?? '',
            'CONTRAT_TYPE_DOMICILIATION' => $contrat['contrat_type_domiciliation'] ?? '',
            'CONTRAT_DATE' => $dateContrat,
            'CONTRAT_DATE_DEBUT' => $dateDebut,
            'CONTRAT_DATE_FIN' => $dateFin,
            'CONTRAT_DUREE_MOIS' => $dureeMois,
            'CONTRAT_LOYER_TTC' => $contrat['contrat_loyer_ttc'] ?? '',
            'CONTRAT_LOYER_HT' => $contrat['contrat_loyer_ht'] ?? '',
            'CONTRAT_TVA_POURCENT' => $contrat['contrat_tva_pourcent'] ?? '',
            'CONTRAT_TOTAL_HT' => $contrat['contrat_total_ht'] ?? '',
            'CONTRAT_FRAIS_INTERMEDIAIRE' => $contrat['contrat_frais_intermediaire'] ?? '',
            'CONTRAT_CAUTION' => $contrat['contrat_caution'] ?? '',
            'CONTRAT_STATUT' => $contrat['contrat_statut'] ?? '',
            'CONTRAT_MODE_SIGNATURE' => $contrat['contrat_mode_signature'] ?? '',
            'CONTRAT_PACK_MONTANT_TTC' => $contrat['contrat_pack_montant_ttc'] ?? '',
            'CONTRAT_PACK_LOYER_TTC' => $contrat['contrat_pack_loyer_ttc'] ?? '',
            'CONTRAT_TYPE_RENOUVELLEMENT' => $contrat['contrat_type_renouvellement'] ?? '',
            'CONTRAT_RENOUV_TVA_POURCENT' => $contrat['contrat_renouv_tva_pourcent'] ?? '',
            'CONTRAT_RENOUV_LOYER_HT' => $contrat['contrat_renouv_loyer_ht'] ?? '',
            'CONTRAT_RENOUV_LOYER_TTC' => $contrat['contrat_renouv_loyer_ttc'] ?? '',
            'CONTRAT_RENOUV_ANNUEL_TTC' => $contrat['contrat_renouv_annuel_ttc'] ?? '',
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
