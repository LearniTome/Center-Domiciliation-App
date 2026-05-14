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
        $prefix = '';
        $datePart = '';
        $docType = '';

        if (count($parts) >= 3) {
            if (preg_match('/^\d{4}[_-]/', $parts[0])) {
                $datePart = $parts[0];
                $prefix = '';
                $rest = array_slice($parts, 1);
            } else {
                $prefix = $parts[0];
                $datePart = $parts[1] ?? '';
                $rest = array_slice($parts, 2);
            }
            $docType = implode('_', $rest);
            $docType = preg_replace('/_Template$/', '', $docType);
            $docType = preg_replace('/-Template$/', '', $docType);
        } elseif (count($parts) === 2) {
            if (preg_match('/^\d{4}[_-]/', $parts[0])) {
                $datePart = $parts[0];
                $docType = str_replace('_Template', '', $parts[1]);
            } else {
                $prefix = $parts[0];
                $docType = str_replace('_Template', '', $parts[1]);
            }
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

    public static function getExpectedContextKeys(): array
    {
        return [
            'DEN_STE', 'denomination', 'denomination_sociale', 'den_ste', 'name',
            'FORME_JUR', 'forme_juridique',
            'ICE', 'ice', 'NUMERO_ICE',
            'DATE_ICE', 'date_ice',
            'DATE_EXP_CERT_NEG', 'date_expiration_certificat_negatif',
            'CAPITAL', 'capital', 'CAPITAL_SOCIAL',
            'PART_SOCIAL', 'parts_social', 'NOMBRE_PARTS_SOCIALES',
            'VALEUR_NOMINALE', 'valeur_nominale',
            'STE_ADRESS', 'adresse', 'ADRESSE_SIEGE_SOCIAL',
            'TRIBUNAL', 'tribunal', 'TRIBUNAL_COMPETENT',
            'DOSSIER_DOMICILIATION', 'dossier_domiciliation',
            'CIVIL', 'civilite', 'CIVILITE_ASSOCIE',
            'PRENOM', 'prenom', 'PRENOM_ASSOCIE',
            'NOM', 'nom', 'NOM_ASSOCIE',
            'NATIONALITY', 'nationalite', 'NATIONALITE_ASSOCIE',
            'CIN_NUM', 'num_piece', 'NUMERO_CIN_ASSOCIE',
            'CIN_VALIDATY', 'validite_piece', 'DATE_VALIDITE_CIN_ASSOCIE',
            'DATE_NAISS', 'date_naiss', 'DATE_NAISSANCE_ASSOCIE',
            'LIEU_NAISS', 'lieu_naiss', 'LIEU_NAISSANCE_ASSOCIE',
            'ADRESSE', 'ADRESSE_ASSOCIE',
            'PHONE', 'telephone', 'TELEPHONE_ASSOCIE',
            'EMAIL', 'email', 'EMAIL_ASSOCIE',
            'QUALITY', 'qualite', 'QUALITE_ASSOCIE',
            'QUALITE_GERANT', 'qualite_gerant',
            'PARTS', 'parts', 'num_parts', 'NOMBRE_PARTS_ASSOCIE',
            'CAPITAL_DETENU', 'capital_detenu', 'CAPITAL_DETENU_ASSOCIE',
            'IS_GERANT', 'est_gerant', 'EST_GERANT',
            'PERIOD_DOMCIL', 'period', 'period_domcil', 'DUREE_CONTRAT_MOIS',
            'PRIX_CONTRAT', 'prix_mensuel', 'LOYER_MENSUEL_TTC',
            'PRIX_INTERMEDIARE_CONTRAT', 'FRAIS_INTERMEDIAIRE_CONTRAT',
            'DOM_DATEDEB', 'date_debut', 'DATE_DEBUT_CONTRAT',
            'DOM_DATEFIN', 'date_fin', 'DATE_FIN_CONTRAT',
            'TYPE_CONTRAT_DOMICILIATION', 'type_contrat_domiciliation',
            'DATE_CONTRAT', 'date_contrat', 'DTAE_CONTRAT',
            'TAUX_TVA_POURCENT', 'taux_tva_pourcent',
            'LOYER_MENSUEL_HT', 'loyer_mensuel_ht',
            'MONTANT_TOTAL_HT_CONTRAT', 'montant_total_ht_contrat',
            'TYPE_RENOUVELLEMENT', 'type_renouvellement',
            'TAUX_TVA_RENOUVELLEMENT_POURCENT',
            'LOYER_MENSUEL_HT_RENOUVELLEMENT',
            'MONTANT_PACK_DEMARRAGE_TTC', 'montant_pack_demarrage_ttc',
            'LOYER_MENSUEL_PACK_DEMARRAGE_TTC',
            'LOYER_MENSUEL_RENOUVELLEMENT_TTC',
            'LOYER_ANNUEL_RENOUVELLEMENT_TTC',
            'MODE_SIGNATURE_GERANCE', 'mode_signature_gerance',
            'TYPE_GENERATION', 'type_generation',
            'PROCEDURE_CREATION', 'procedure_creation',
            'MODE_DEPOT_CREATION', 'mode_depot_creation',
        ];
    }

    public static function inferSection(string $variableName): string
    {
        $name = strtoupper($variableName);

        if (in_array($name, ['DATE', 'DATE_LONG', 'ANNEE', 'MOIS', 'JOUR', 'values'], true)) {
            return 'autre';
        }

        if (str_contains($name, 'COLLAB')) {
            return 'collaborateur';
        }

        $associeTokens = ['ASSOC', 'GERANT', 'CIN', 'NAISS', 'CIVIL', 'NATIONAL', 'QUALITE', 'PHONE', 'EMAIL'];
        foreach ($associeTokens as $token) {
            if (str_contains($name, $token)) {
                return 'associe';
            }
        }

        $contratTokens = ['CONTRAT', 'LOYER', 'RENOUV', 'TVA', 'MONTANT', 'PACK', 'DOM_', 'DTAE', 'PERIOD', 'PRIX'];
        foreach ($contratTokens as $token) {
            if (str_contains($name, $token)) {
                return 'contrat';
            }
        }

        $societeTokens = ['STE', 'SOCIETE', 'DEN', 'FORME', 'ICE', 'TRIBUNAL', 'CAPITAL', 'PART', 'ACTIVITY', 'ACTIVIT', 'VALEUR_NOMINALE', 'DOSSIER', 'PROCEDURE', 'MODE_DEPOT'];
        foreach ($societeTokens as $token) {
            if (str_contains($name, $token)) {
                return 'societe';
            }
        }

        return 'autre';
    }

    public static function analyzeCoverage(array $templates, array $contextKeys = []): array
    {
        if (empty($contextKeys)) {
            $contextKeys = self::getExpectedContextKeys();
        }

        $contextKeys = array_map('strtoupper', $contextKeys);
        $contextKeySet = array_flip($contextKeys);

        $allVariables = [];
        $variableTemplates = [];
        $details = [];
        $errors = [];

        foreach ($templates as $tpl) {
            $path = $tpl['path'] ?? '';
            if (!file_exists($path)) {
                continue;
            }

            try {
                $variables = self::extractVariables($path);
            } catch (Throwable $e) {
                $errors[] = ['template' => basename($path), 'error' => $e->getMessage()];
                continue;
            }

            foreach ($variables as $var) {
                $varUpper = strtoupper($var);
                $allVariables[$varUpper] = ($allVariables[$varUpper] ?? 0) + 1;
                $variableTemplates[$varUpper][] = basename($path);
                $details[] = [
                    'template' => basename($path),
                    'template_path' => $path,
                    'variable' => $var,
                    'occurrences' => 1,
                    'section' => self::inferSection($var),
                    'coverage' => isset($contextKeySet[$varUpper]) ? 'couvert' : 'non couvert',
                    'legal_form' => $tpl['folder'] ?? 'Racine',
                ];
            }
        }

        $variableRows = [];
        foreach ($allVariables as $varUpper => $count) {
            $tpls = array_unique($variableTemplates[$varUpper] ?? []);
            $variableRows[] = [
                'variable' => $varUpper,
                'occurrences' => $count,
                'templates_count' => count($tpls),
                'templates' => $tpls,
                'section' => self::inferSection($varUpper),
                'coverage' => isset($contextKeySet[$varUpper]) ? 'couvert' : 'non couvert',
            ];
        }

        usort($variableRows, fn(array $a, array $b) => strcasecmp($a['variable'], $b['variable']));
        usort($details, fn(array $a, array $b) => strcasecmp($a['template'], $b['template']));

        $coveredCount = count(array_filter($variableRows, fn(array $r) => $r['coverage'] === 'couvert'));
        $uncoveredCount = count($variableRows) - $coveredCount;

        return [
            'summary' => [
                'total_templates' => count($templates),
                'total_variables' => count($allVariables),
                'covered_variables' => $coveredCount,
                'uncovered_variables' => $uncoveredCount,
            ],
            'variables' => $variableRows,
            'details' => $details,
            'errors' => $errors,
        ];
    }

    public static function exportAnalysisCsv(array $rows, string $outPath): string
    {
        $dir = dirname($outPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fh = fopen($outPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('Impossible de creer le fichier CSV.');
        }

        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['Variable', 'Occurrences', 'Templates', 'Section', 'Couverture'], ';');

        foreach ($rows as $row) {
            $templates = is_array($row['templates'] ?? null)
                ? implode(', ', $row['templates'])
                : (string) ($row['templates'] ?? '');
            fputcsv($fh, [
                $row['variable'] ?? '',
                (string) ($row['occurrences'] ?? ''),
                $templates,
                $row['section'] ?? '',
                $row['coverage'] ?? '',
            ], ';');
        }

        fclose($fh);
        return $outPath;
    }
}
