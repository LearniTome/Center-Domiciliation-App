<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/includes/fonctions.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifie.']);
    exit;
}

$config = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/base_donnees.php';

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
}

try {
    $pdo = db();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion a la base de donnees.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

$csrfToken = $_POST['_csrf_token'] ?? '';
if ($csrfToken === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
    exit;
}

// ── Import Excel configuration ──
$importTableConfig = [
    'societes' => [
        'columnMap' => [
            'Raison sociale' => 'societe_raison_sociale',
            'Dossier domiciliation' => 'societe_dossier',
            'Forme juridique' => 'societe_forme_juridique',
            'ICE' => 'societe_ice',
            'RC' => 'societe_rc',
            'IF' => 'societe_if',
            'Ville' => 'societe_ville',
            'Email' => 'societe_email',
            'Telephone' => 'societe_telephone',
            'Capital' => 'societe_capital',
        ],
        'defaults' => ['societe_source' => 'import'],
    ],
    'associes' => [
        'columnMap' => [
            'Societe ID' => 'societe_id',
            'Nom complet' => 'associe_nom_complet',
            'CIN' => 'associe_cin',
            'Date naissance' => 'associe_date_naissance',
            'Lieu naissance' => 'associe_lieu_naissance',
            'Nationalite' => 'associe_nationalite',
            'Telephone' => 'associe_telephone',
            'Email' => 'associe_email',
            'Qualite' => 'associe_qualite',
            'Parts' => 'associe_parts',
        ],
        'defaults' => [],
    ],
    'contrats' => [
        'columnMap' => [
            'Societe ID' => 'societe_id',
            'Type contrat' => 'contrat_type',
            'Date contrat' => 'contrat_date',
            'Duree (mois)' => 'contrat_duree_mois',
            'Date debut' => 'contrat_date_debut',
            'Date fin' => 'contrat_date_fin',
            'Loyer TTC/mois' => 'contrat_loyer_ttc',
            'Statut' => 'contrat_statut',
        ],
        'defaults' => [],
    ],
    'collaborateurs' => [
        'columnMap' => [
            'Nom complet' => 'nom_complet',
            'Fonction' => 'fonction',
            'Type' => 'collaborateur_type',
            'Code' => 'collaborateur_code',
            'ICE' => 'collaborateur_ice',
            'Telephone' => 'telephone',
            'Email' => 'email',
            'Statut' => 'statut',
        ],
        'defaults' => [],
    ],
    'cessions' => [
        'columnMap' => [
            'Dossier' => 'cession_dossier',
            'Societe' => 'societe_id',
            'Date' => 'cession_date',
            'Statut' => 'cession_status',
        ],
        'defaults' => [],
    ],
];

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Action inconnue.'];

$allowedTables = [
    'societes' => [
        'societe_raison_sociale', 'societe_dossier', 'societe_forme_juridique',
        'societe_ice', 'societe_rc', 'societe_if', 'societe_tp', 'societe_cnss',
        'societe_capital', 'societe_part_social', 'societe_valeur_nominale',
        'societe_date_ice', 'societe_date_exp_cert_neg',
        'societe_adresse', 'societe_adresse_siege', 'societe_ville',
        'societe_tribunal', 'societe_tribunal_type',
        'societe_email', 'societe_telephone',
        'societe_type_generation', 'societe_procedure_creation', 'societe_mode_depot',
        'societe_source', 'societe_activites_statuts', 'societe_activites_ompic',
    ],
    'associes' => [
        'societe_id',
        'associe_civilite', 'associe_nom', 'associe_prenom', 'associe_nom_complet',
        'associe_cin', 'associe_date_validite_cin', 'associe_date_naissance',
        'associe_lieu_naissance', 'associe_nationalite', 'associe_adresse',
        'associe_telephone', 'associe_email', 'associe_qualite',
        'associe_parts', 'associe_capital_detenu', 'associe_part_percent', 'associe_est_gerant',
    ],
    'contrats' => [
        'societe_id',
        'contrat_type', 'contrat_type_domiciliation', 'contrat_date',
        'contrat_duree_mois', 'contrat_date_debut', 'contrat_date_fin',
        'contrat_tva_pourcent', 'contrat_loyer_ht', 'contrat_loyer_ttc',
        'contrat_total_ht', 'contrat_type_renouvellement',
        'contrat_renouv_tva_pourcent', 'contrat_renouv_loyer_ht',
        'contrat_renouv_loyer_ttc', 'contrat_renouv_total_ht',
        'contrat_statut', 'contrat_notes', 'contrat_caution', 'contrat_pack_montant_ttc',
    ],
    'collaborateurs' => [
        'role_id',
        'nom_complet', 'den_ste', 'fonction', 'collaborateur_type',
        'collaborateur_code', 'collaborateur_ice', 'collaborateur_tp',
        'collaborateur_rc', 'collaborateur_if',
        'collaborateur_tel_fixe', 'collaborateur_tel_mobile',
        'collaborateur_email', 'collaborateur_adresse', 'statut', 'can_login',
    ],
    'cessions' => [
        'societe_id',
        'cession_dossier', 'cession_date', 'cession_status',
    ],
    'pv_ago' => [
        'dossier_numero', 'date_ago', 'exercice_clos',
        'resultat_type', 'resultat_net', 'statut',
    ],
    'ref_formes_juridiques' => ['forme_juridique', 'template_folder'],
    'ref_ste_adresses' => ['adresse'],
    'ref_villes' => ['ville'],
    'ref_nationalites' => ['nationalite'],
    'ref_lieux_naissance' => ['lieu_naissance'],
    'ref_qualites_associe' => ['qualite_associe'],
];

$tableColumnMap = $allowedTables;

try {
    switch ($action) {
        case 'quick_create':
            $response = handle_quick_create($pdo, $allowedTables, $user);
            break;
        case 'inline_update':
            $response = handle_inline_update($pdo, $allowedTables);
            break;
        case 'bulk_update':
            $response = handle_bulk_update($pdo, $allowedTables);
            break;
        case 'import_preview':
            $response = handle_import_preview($importTableConfig);
            break;
        case 'import_confirm':
            $response = handle_import_confirm($pdo, $user, $importTableConfig);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()];
}

echo json_encode($response);
exit;

function handle_quick_create(PDO $pdo, array $allowedTables, array $user): array
{
    $table = $_POST['table'] ?? '';
    if (!isset($allowedTables[$table])) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Table non autorisee.'];
    }

    $allowedCols = $allowedTables[$table];
    $data = [];
    foreach ($allowedCols as $col) {
        if (isset($_POST[$col])) {
            $data[$col] = $_POST[$col];
        }
    }
    if ($table === 'societes') {
        if (!isset($data['societe_source'])) {
            $data['societe_source'] = 'creation';
        }
    }
    $colStmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE 'created_by'");
    $colStmt->execute();
    if ($colStmt->fetch()) {
        $data['created_by'] = (int) $user['id'];
    }

    if (empty($data)) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Aucune donnee fournie.'];
    }

    $cols = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_map(fn(string $c) => ":$c", array_keys($data)));
    $stmt = $pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$placeholders})");
    $stmt->execute($data);
    $newId = (int) $pdo->lastInsertId();

    if (function_exists('log_activity')) {
        log_activity($pdo, 'create', $table, $newId, null, 'Quick create via API');
    }

    $fetch = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
    $fetch->execute(['id' => $newId]);
    $record = $fetch->fetch(PDO::FETCH_ASSOC);

    return ['success' => true, 'message' => 'Enregistrement cree avec succes.', 'data' => $record];
}

function handle_inline_update(PDO $pdo, array $allowedTables): array
{
    $table = $_POST['table'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $column = $_POST['column'] ?? '';
    $value = $_POST['value'] ?? '';

    if (!isset($allowedTables[$table]) || $id <= 0) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Parametres invalides.'];
    }

    if (!in_array($column, $allowedTables[$table], true)) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Colonne non autorisee.'];
    }

    $stmt = $pdo->prepare("UPDATE {$table} SET {$column} = :val WHERE id = :id");
    $stmt->execute(['val' => $value, 'id' => $id]);

    return ['success' => true, 'message' => 'Mis a jour avec succes.'];
}

function handle_bulk_update(PDO $pdo, array $allowedTables): array
{
    $table = $_POST['table'] ?? '';
    $idsRaw = $_POST['ids'] ?? '';

    if (!isset($allowedTables[$table]) || $idsRaw === '') {
        http_response_code(400);
        return ['success' => false, 'message' => 'Parametres invalides.'];
    }

    $ids = array_filter(array_map('intval', explode(',', $idsRaw)), fn(int $id) => $id > 0);
    if ($ids === []) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Aucun ID valide fourni.'];
    }

    $allowedCols = $allowedTables[$table];
    $updates = [];
    foreach ($allowedCols as $col) {
        if (isset($_POST[$col]) && $_POST[$col] !== '') {
            $updates[$col] = $_POST[$col];
        }
    }

    if ($updates === []) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Aucune valeur a mettre a jour.'];
    }

    $setParts = implode(', ', array_map(fn(string $c) => "{$c} = :{$c}", array_keys($updates)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("UPDATE {$table} SET {$setParts} WHERE id IN ({$placeholders})");
    $params = array_values($updates);
    foreach ($ids as $id) {
        $params[] = $id;
    }
    $stmt->execute($params);

    return ['success' => true, 'message' => count($ids) . ' enregistrement(s) mis a jour avec succes.', 'updated' => $stmt->rowCount()];
}

function handle_import_preview(array $config): array
{
    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
        http_response_code(500);
        return ['success' => false, 'message' => 'PhpSpreadsheet n\'est pas installe.'];
    }

    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Veuillez selectionner un fichier Excel valide.'];
    }

    $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'], true)) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Format de fichier non supporte. Utilisez .xlsx ou .xls.'];
    }

    $tmpDir = __DIR__ . '/uploads/tmp/import/';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0777, true);
    }

    $tmpFile = $tmpDir . uniqid('import_', true) . '.' . $ext;
    move_uploaded_file($_FILES['import_file']['tmp_name'], $tmpFile);

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
        $result = loadSpreadsheetData($spreadsheet, $tmpFile);
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'ENTITY') !== false && stripos($msg, 'XML') !== false) {
            $cleaned = stripXlsxEntityDeclarations($tmpFile);
            if ($cleaned !== null) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($cleaned);
                    $result = loadSpreadsheetData($spreadsheet, $tmpFile);
                    @unlink($cleaned);
                } catch (\Throwable $e2) {
                    @unlink($cleaned);
                    if (file_exists($tmpFile)) @unlink($tmpFile);
                    http_response_code(500);
                    return ['success' => false, 'message' => 'Erreur de lecture du fichier Excel : le fichier contient des declarations ENTITY XML incompatibles. Reenregistrez le fichier depuis Excel (Fichier > Enregistrer sous > Classeur Excel .xlsx).'];
                }
            } else {
                if (file_exists($tmpFile)) @unlink($tmpFile);
                http_response_code(500);
                return ['success' => false, 'message' => 'Erreur de lecture du fichier Excel : le fichier contient des declarations ENTITY XML incompatibles. Reenregistrez le fichier depuis Excel (Fichier > Enregistrer sous > Classeur Excel .xlsx).'];
            }
        } else {
            if (file_exists($tmpFile)) @unlink($tmpFile);
            http_response_code(500);
            return ['success' => false, 'message' => 'Erreur de lecture du fichier Excel : ' . $e->getMessage()];
        }
    }

    // Merge expected columns from config with actual Excel columns
    $table = $_POST['table'] ?? '';
    if (isset($config[$table])) {
        $expectedHeaders = array_keys($config[$table]['columnMap']);
        $excelHeaders = $result['headers'];
        // Only keep and reorder: expected first, then extra Excel columns not in expected
        $mergedHeaders = $expectedHeaders;
        foreach ($excelHeaders as $h) {
            if (!in_array($h, $mergedHeaders, true)) {
                $mergedHeaders[] = $h;
            }
        }
        $result['headers'] = $mergedHeaders;
        $result['expected_headers'] = $expectedHeaders;

        // Pad each row with empty values for any missing expected column
        foreach ($result['rows'] as &$row) {
            foreach ($expectedHeaders as $h) {
                if (!isset($row[$h])) {
                    $row[$h] = '';
                }
            }
        }
        unset($row);
    }

    return $result;
}

function loadSpreadsheetData(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $tmpFile): array
{
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    if (count($data) < 2) {
        @unlink($tmpFile);
        http_response_code(400);
        return ['success' => false, 'message' => 'Le fichier doit contenir au moins une ligne d\'en-tete et une ligne de donnees.'];
    }

    $headers = array_map('trim', $data[0]);
    $rows = [];
    for ($i = 1; $i < count($data); $i++) {
        $row = [];
        foreach ($data[$i] as $j => $value) {
            $row[$headers[$j] ?? 'Colonne_' . ($j + 1)] = $value;
        }
        $rows[] = $row;
    }

    $_SESSION['_import_file'] = $tmpFile;

    return [
        'success' => true,
        'headers' => $headers,
        'rows' => $rows,
        'total' => count($rows),
        'file' => basename($tmpFile),
    ];
}

function stripXlsxEntityDeclarations(string $path): ?string
{
    $zip = new \ZipArchive();
    $src = $zip->open($path);
    if ($src !== true) {
        return null;
    }

    $entries = [];
    $hasEntity = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        $content = $zip->getFromIndex($i);
        if ($content === false) {
            $entries[$name] = false;
            continue;
        }
        if (preg_match('/<!ENTITY\s+\S+\s+/s', $content)) {
            $hasEntity = true;
            $content = preg_replace('/<!ENTITY\s+\S+\s+[^>]*>\s*/s', '', $content);
        }
        $entries[$name] = $content;
    }
    $zip->close();

    if (!$hasEntity) {
        return null;
    }

    $outPath = $path . '.cleaned.xlsx';
    $out = new \ZipArchive();
    if ($out->open($outPath, \ZipArchive::CREATE) !== true) {
        return null;
    }
    foreach ($entries as $name => $content) {
        if ($content === false) {
            $out->addEmptyDir(dirname($name));
        } else {
            $out->addFromString($name, $content);
        }
    }
    $out->close();

    return file_exists($outPath) ? $outPath : null;
}

function handle_import_confirm(PDO $pdo, array $user, array $config): array
{
    $table = $_POST['table'] ?? '';
    if (!isset($config[$table])) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Table non autorisee pour l\'import.'];
    }

    $rawData = $_POST['import_data'] ?? '';
    if ($rawData === '') {
        http_response_code(400);
        return ['success' => false, 'message' => 'Aucune donnee fournie.'];
    }

    $rows = json_decode($rawData, true);
    if (!is_array($rows) || $rows === []) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Donnees d\'import invalides.'];
    }

    $cfg = $config[$table];
    $columnMap = $cfg['columnMap'];
    $defaults = $cfg['defaults'];
    if (in_array($table, ['societes', 'associes', 'contrats', 'collaborateurs', 'cessions', 'pv_ago'])) {
        $defaults['created_by'] = (int) $user['id'];
    }
    $defaults['created_at'] = date('Y-m-d H:i:s');
    $defaults['updated_at'] = date('Y-m-d H:i:s');

    $imported = 0;
    $errors = [];

    foreach ($rows as $idx => $row) {
        try {
            $data = $defaults;
            foreach ($columnMap as $excelHeader => $dbCol) {
                $val = $row[$excelHeader] ?? '';
                $data[$dbCol] = $val !== '' ? $val : null;
            }

            // Convert DD/MM/YYYY to YYYY-MM-DD for date columns
            foreach ($data as $col => $val) {
                if (is_string($val) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $val)) {
                    $data[$col] = \DateTime::createFromFormat('d/m/Y', $val)->format('Y-m-d');
                }
            }

            $cols = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $stmt = $pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$placeholders})");
            $stmt->execute($data);
            $imported++;
        } catch (\Throwable $e) {
            $errors[] = 'Ligne ' . ($idx + 2) . ' : ' . $e->getMessage();
        }
    }

    // Cleanup temp file
    $tmpFile = $_SESSION['_import_file'] ?? null;
    if ($tmpFile && file_exists($tmpFile)) {
        @unlink($tmpFile);
    }
    unset($_SESSION['_import_file']);

    return [
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'message' => $imported . ' ligne(s) importee(s) avec succes.' . (!empty($errors) ? ' Erreurs : ' . implode(' | ', array_slice($errors, 0, 10)) : ''),
    ];
}
