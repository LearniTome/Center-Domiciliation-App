<?php

declare(strict_types=1);

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function download_url(string $absolutePath): string
{
    global $config;
    $projectDir = dirname(__DIR__);
    $relative = str_replace([$projectDir . '/', $projectDir . '\\'], '', $absolutePath);
    $relative = str_replace('\\', '/', $relative);
    return ltrim($relative, '/');
}

function word_url(string $filePath): string
{
    static $baseUrl = null;
    if ($baseUrl === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $baseUrl = $protocol . '://' . $host . rtrim($scriptDir, '/');
    }
    $projectDir = dirname(__DIR__);
    $relative = str_replace($projectDir . DIRECTORY_SEPARATOR, '', $filePath);
    $relative = str_replace('\\', '/', $relative);
    $fileUrl = $baseUrl . '/' . ltrim($relative, '/');
    return 'ms-word:ofe|u|' . str_replace(' ', '%20', $fileUrl);
}

function app_url(string $page = 'dashboard', array $params = []): string
{
    global $config;

    $query = array_merge(['page' => $page], $params);
    return $config['base_url'] . '?' . http_build_query($query);
}

function redirect_to(string $page, array $params = []): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    session_write_close();
    header('Location: ' . app_url($page, $params));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
        http_response_code(419);
        exit('Jeton CSRF invalide.');
    }
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function field_value(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) ? trim((string) $source[$key]) : $default;
}

function parse_money(string $value): float
{
    return (float) str_replace([',', ' '], ['.', ''], $value);
}

function money_value(array $source, string $key): ?float
{
    $value = field_value($source, $key);
    if ($value === '') {
        return null;
    }

    return parse_money($value);
}

function int_value(array $source, string $key): ?int
{
    $value = field_value($source, $key);
    if ($value === '') {
        return null;
    }

    return (int) $value;
}

function dashboard_count(?PDO $pdo, string $table): int
{
    if (!$pdo) {
        return 0;
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
    return (int) $stmt->fetchColumn();
}

function fetch_all_records(?PDO $pdo, string $table): array
{
    if (!$pdo) {
        return [];
    }

    $allowed = ['societes', 'associes', 'contrats', 'collaborateurs'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }

    $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id DESC");
    return $stmt->fetchAll();
}

function fetch_record(?PDO $pdo, string $table, int $id): ?array
{
    if (!$pdo) {
        return null;
    }

    $allowed = ['societes', 'associes', 'contrats', 'collaborateurs'];
    if (!in_array($table, $allowed, true)) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch();
    return $record ?: null;
}

function fetch_societes_options(?PDO $pdo, ?int $userId = null): array
{
    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT id, societe_raison_sociale, societe_ice, societe_ville FROM societes';
    $params = [];
    if ($userId !== null) {
        $sql .= ' WHERE created_by = :user_id';
        $params['user_id'] = $userId;
    }
    $sql .= ' ORDER BY societe_raison_sociale ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_tribunaux_types(?PDO $pdo): array
{
    if (!$pdo) return [];
    try {
        $stmt = $pdo->query("SELECT DISTINCT tribunal_type FROM ref_tribunaux WHERE tribunal_type IS NOT NULL AND tribunal_type != '' ORDER BY tribunal_type");
        return array_map(static fn(array $row): string => $row['tribunal_type'], $stmt->fetchAll());
    } catch (PDOException) {
        return [];
    }
}

function fetch_tribunaux_all(?PDO $pdo): array
{
    if (!$pdo) return [];
    try {
        $stmt = $pdo->query("SELECT tribunal, tribunal_type FROM ref_tribunaux ORDER BY FIELD(tribunal_type, 'Tribunal de commerce', 'Tribunal de Première Instance'), sort_order ASC, tribunal ASC");
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function fetch_adresses_all(?PDO $pdo): array
{
    if (!$pdo) return [];
    try {
        $stmt = $pdo->query("SELECT ste_adresse, ville FROM ref_ste_adresses ORDER BY ville ASC, sort_order ASC, ste_adresse ASC");
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function fetch_reference_options(?PDO $pdo, string $table, string $column): array
{
    if (!$pdo) {
        return [];
    }

    $allowed = [
        'ref_ste_adresses' => 'ste_adresse',
        'ref_tribunaux' => 'tribunal',
        'ref_activites' => 'activite',
        'ref_activites_ompic' => 'libelle',
        'ref_nationalites' => 'nationalite',
        'ref_lieux_naissance' => 'lieu_naissance',
        'ref_formes_juridiques' => 'forme_juridique',
        'ref_villes' => 'ville',
        'ref_qualites_associe' => 'qualite_associe',
    ];

    if (($allowed[$table] ?? null) !== $column) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT {$column} FROM {$table} ORDER BY sort_order ASC, {$column} ASC");
        return array_map(static fn (array $row): string => (string) $row[$column], $stmt->fetchAll());
    } catch (PDOException) {
        return [];
    }
}

function fetch_activites_ompic_options(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT code, libelle FROM ref_activites_ompic ORDER BY sort_order ASC, code ASC");
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function fetch_activites_ompic_display(?PDO $pdo, string $code): string
{
    $options = fetch_activites_ompic_options($pdo);
    foreach ($options as $row) {
        if ($row['code'] === $code) {
            return $row['code'] . ' - ' . $row['libelle'];
        }
    }
    return $code;
}

function fetch_all_documents(?PDO $pdo, ?int $societe_id = null, ?string $q = null, ?string $doc_type = null, ?int $userId = null): array
{
    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT d.*, s.societe_raison_sociale
            FROM documents_generes d
            JOIN societes s ON s.id = d.societe_id
            WHERE 1=1';
    $params = [];

    if ($userId !== null) {
        $sql .= ' AND s.created_by = :user_id';
        $params['user_id'] = $userId;
    }

    if ($societe_id !== null) {
        $sql .= ' AND d.societe_id = :societe_id';
        $params['societe_id'] = $societe_id;
    }

    if ($q !== null && $q !== '') {
        $sql .= ' AND (s.societe_raison_sociale LIKE :q OR d.doc_type LIKE :q2)';
        $params['q'] = like_term($q);
        $params['q2'] = like_term($q);
    }

    if ($doc_type !== null && $doc_type !== '') {
        $sql .= ' AND d.doc_type = :doc_type';
        $params['doc_type'] = $doc_type;
    }

    $sql .= ' ORDER BY d.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_all_doc_types(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->query("SELECT DISTINCT doc_type FROM documents_generes WHERE doc_type IS NOT NULL AND doc_type != '' ORDER BY doc_type");
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
}

function fetch_document(?PDO $pdo, int $id): ?array
{
    if (!$pdo) {
        return null;
    }

    $sql = 'SELECT d.*, s.societe_raison_sociale
            FROM documents_generes d
            JOIN societes s ON s.id = d.societe_id
            WHERE d.id = :id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch();
    return $record ?: null;
}

function search_term(string $key = 'q'): string
{
    return trim((string) ($_GET[$key] ?? ''));
}

function like_term(string $value): string
{
    return '%' . $value . '%';
}

function export_csv(string $filename, array $headers, array $rows): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        exit('Impossible de generer le fichier CSV.');
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers, ';');

    foreach ($rows as $row) {
        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;
}

function export_excel(string $filename, array $headers, array $rows): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        exit('PhpSpreadsheet n\'est pas installe. Lancez "composer install" a la racine du projet.');
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header row
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValue([$col, 1], $header);
        $col++;
    }

    // Data rows
    $rowNum = 2;
    foreach ($rows as $row) {
        $col = 1;
        foreach ($row as $value) {
            $sheet->setCellValue([$col, $rowNum], $value ?? '');
            $col++;
        }
        $rowNum++;
    }

    // Auto-size columns
    foreach (range(1, count($headers)) as $colIdx) {
        $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
    }

    // Bold header
    $sheet->getStyle([1, 1, count($headers), 1])->getFont()->setBold(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function import_excel_preview(string $filepath): array
{
    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        return ['error' => 'PhpSpreadsheet n\'est pas installe.'];
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        if (count($data) < 2) {
            return ['error' => 'Le fichier doit contenir au moins une ligne d\'en-tete et une ligne de donnees.'];
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

        return compact('headers', 'rows');
    } catch (\Throwable $e) {
        return ['error' => 'Erreur de lecture du fichier Excel : ' . $e->getMessage()];
    }
}

function import_excel_confirm(string $filepath, array $columnMap, callable $rowHandler): array
{
    $preview = import_excel_preview($filepath);
    if (isset($preview['error'])) {
        return $preview;
    }

    $imported = 0;
    $errors = [];

    foreach ($preview['rows'] as $idx => $row) {
        $mapped = [];
        foreach ($columnMap as $excelCol => $dbCol) {
            $mapped[$dbCol] = $row[$excelCol] ?? null;
        }
        try {
            $rowHandler($mapped, $idx);
            $imported++;
        } catch (\Throwable $e) {
            $errors[] = 'Ligne ' . ($idx + 2) . ' : ' . $e->getMessage();
        }
    }

    return compact('imported', 'errors');
}

function format_money(?float $value, string $suffix = ' DH'): string
{
    if ($value === null) return '-';
    return number_format($value, 2, ',', ' ') . $suffix;
}

function format_number(?float $value, int $decimals = 0): string
{
    if ($value === null) return '-';
    return number_format($value, $decimals, ',', ' ');
}

function format_date(?string $value): string
{
    if ($value === null || $value === '') return '-';
    try {
        $date = new DateTime($value);
        return $date->format('d/m/Y');
    } catch (Exception) {
        return $value;
    }
}

function time_ago(string $datetime): string
{
    if (!$datetime) return '-';
    $now = time();
    $ts = strtotime($datetime);
    if (!$ts) return '-';
    $diff = $now - $ts;
    if ($diff < 0) return "a l'instant";
    if ($diff < 60) return 'il y a ' . $diff . 's';
    if ($diff < 3600) return 'il y a ' . intdiv($diff, 60) . 'min';
    if ($diff < 86400) return 'il y a ' . intdiv($diff, 3600) . 'h';
    if ($diff < 604800) return 'il y a ' . intdiv($diff, 86400) . 'j';
    return date('d/m/Y', $ts);
}

function load_defaults(?string $key = null): array
{
    $defaultsFile = __DIR__ . '/../config/defaults.json';
    $defaults = [];
    
    if (file_exists($defaultsFile)) {
        $defaultsContent = file_get_contents($defaultsFile);
        if ($defaultsContent !== false) {
            $decoded = json_decode($defaultsContent, true);
            if (is_array($decoded)) {
                $defaults = $decoded;
            }
        }
    }
    
    if ($key !== null) {
        return $defaults[$key] ?? [];
    }
    
    return $defaults;
}

function fetch_legal_form_template_folder(?PDO $pdo, string $formeJuridique): string
{
    if (!$pdo || $formeJuridique === '') {
        return '';
    }

    try {
        $stmt = $pdo->prepare("SELECT template_folder FROM ref_formes_juridiques WHERE forme_juridique = :fj LIMIT 1");
        $stmt->execute(['fj' => $formeJuridique]);
        $row = $stmt->fetch();
        return $row ? (string) ($row['template_folder'] ?? '') : '';
    } catch (PDOException) {
        return '';
    }
}

function fetch_formes_juridiques_with_folders(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT forme_juridique, template_folder FROM ref_formes_juridiques ORDER BY sort_order ASC, forme_juridique ASC");
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function ensure_template_folder(string $folderName): bool
{
    if ($folderName === '') {
        return false;
    }

    $dir = __DIR__ . '/../templates/' . $folderName;

    if (is_dir($dir)) {
        return true;
    }

    return mkdir($dir, 0777, true);
}

// ─── Auth Helpers ───────────────────────────────────────────

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (!empty($_SESSION['_user_cache'])) {
        return $_SESSION['_user_cache'];
    }

    global $pdo;
    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT c.*, r.nom AS role_nom, r.id AS role_id
        FROM collaborateurs c
        LEFT JOIN roles r ON r.id = c.role_id
        WHERE c.id = :id AND c.can_login = 1 AND c.statut = \'actif\'
        LIMIT 1
    ');
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['_user_cache'], $_SESSION['_permissions_cache']);
        return null;
    }

    $_SESSION['_user_cache'] = $user;
    return $user;
}

function is_logged_in(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    // Verify user still exists and is active
    if (current_user() !== null) {
        return true;
    }

    return false;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Veuillez vous connecter pour accéder a cette page.');
        redirect_to('connexion', ['redirect' => $_SERVER['REQUEST_URI'] ?? '']);
    }
}

function get_user_permissions(): array
{
    if (!empty($_SESSION['_permissions_cache']) && is_array($_SESSION['_permissions_cache'])) {
        return $_SESSION['_permissions_cache'];
    }

    $user = current_user();
    if (!$user || empty($user['role_id'])) {
        $_SESSION['_permissions_cache'] = [];
        return [];
    }

    global $pdo;
    if (!$pdo) {
        return [];
    }

    $stmt = $pdo->prepare('
        SELECT p.permission_key
        FROM role_permissions rp
        JOIN permissions p ON p.id = rp.permission_id
        WHERE rp.role_id = :role_id
    ');
    $stmt->execute(['role_id' => (int) $user['role_id']]);
    $perms = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    // Check collaborateur-specific overrides
    $stmt = $pdo->prepare('
        SELECT p.permission_key, cp.granted
        FROM collaborateur_permissions cp
        JOIN permissions p ON p.id = cp.permission_id
        WHERE cp.collaborateur_id = :cid
    ');
    $stmt->execute(['cid' => (int) $user['id']]);
    $overrides = $stmt->fetchAll();

    foreach ($overrides as $ov) {
        if ((int) $ov['granted'] === 1) {
            if (!in_array($ov['permission_key'], $perms, true)) {
                $perms[] = $ov['permission_key'];
            }
        } else {
            $perms = array_values(array_filter($perms, static fn(string $k) => $k !== $ov['permission_key']));
        }
    }

    $_SESSION['_permissions_cache'] = $perms;
    return $perms;
}

function has_permission(string $key): bool
{
    // Super Admin (role_id = 1) always has all permissions
    $user = current_user();
    if ($user && (int) ($user['role_id'] ?? 0) === 1) {
        return true;
    }

    $permissions = get_user_permissions();
    return in_array($key, $permissions, true);
}

function require_permission(string $key): void
{
    if (!has_permission($key)) {
        set_flash('error', 'Vous n\'avez pas les droits nécessaires pour accéder a cette page.');
        redirect_to('dashboard');
    }
}

function get_page_permission(string $page): ?string
{
    $map = [
        'dashboard' => 'dashboard.view',

        'societes' => 'societes.view',
        'societe' => 'societes.view',

        'associes' => 'associes.view',
        'associe' => 'associes.view',

        'contrats' => 'contrats.view',

        'collaborateurs' => 'collaborateurs.view',
        'collaborateur' => 'collaborateurs.view',

        'creation' => 'wizard.create',

        'templates' => 'templates.view',

        'generation' => 'generation.use',
        'download_all' => 'generation.use',

        'documents' => 'documents.view',

        'configuration' => 'configuration.view',
        'formes-juridiques' => 'configuration.view',
        'tribunaux' => 'configuration.view',
        'villes' => 'configuration.view',
        'nationalites' => 'configuration.view',
        'lieux-naissance' => 'configuration.view',
        'adresses' => 'configuration.view',
        'qualites-associe' => 'configuration.view',
        'fonctions' => 'configuration.view',
        'activites' => 'configuration.view',
        'activites-ompic' => 'configuration.view',

        'analyse-couverture' => 'analyse.view',

        'variables' => 'variables.view',

        'defaults' => 'defaults.edit',

        'convert-word-pdf' => 'convert.use',

        'ai-assistant' => 'ai.use',

        'roles' => 'roles.manage',
        'role' => 'roles.manage',

        'activite' => 'roles.manage',

        'modifications' => 'modifications.view',
        'cessions' => 'cessions.view',
        'cession' => 'cessions.view',
        'cession_dossier' => 'cessions.view',
        'pv_ago' => 'pv_ago.view',
        'pvag' => 'pv_ago.view',
    ];

    return $map[$page] ?? null;
}

function require_page_access(string $page): void
{
    $perm = get_page_permission($page);
    if ($perm !== null) {
        require_permission($perm);
    }
}

function get_role_name(): string
{
    $user = current_user();
    return $user['role_nom'] ?? '—';
}

function clear_user_cache(): void
{
    unset($_SESSION['_user_cache'], $_SESSION['_permissions_cache']);
}

function log_activity(
    ?PDO $pdo,
    string $action,
    string $entity_type,
    ?int $entity_id = null,
    ?string $entity_label = null,
    ?string $details = null,
): void {
    if (!$pdo) return;
    $user = current_user();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, user_nom, action, entity_type, entity_id, entity_label, details, ip_address, created_at)
             VALUES (:uid, :unom, :act, :etype, :eid, :elabel, :det, :ip, NOW())'
        );
        $stmt->execute([
            'uid'    => $user['id'] ?? null,
            'unom'   => $user['nom_complet'] ?? null,
            'act'    => $action,
            'etype'  => $entity_type,
            'eid'    => $entity_id,
            'elabel' => $entity_label,
            'det'    => $details,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (PDOException) {
        // silently fail – logging must never break the app
    }

    // Auto-create notification for important actions
    auto_notify_action($pdo, $action, $entity_type, $entity_id, $entity_label, $details);
}

/**
 * Automatically creates a notification based on action + entity_type mapping.
 * Called from log_activity() — no manual calls needed.
 */
function auto_notify_action(
    ?PDO $pdo,
    string $action,
    string $entity_type,
    ?int $entity_id = null,
    ?string $entity_label = null,
    ?string $details = null,
): void {
    if (!$pdo) return;

    // Only notify for meaningful user actions
    $skipActions = ['connexion', 'deconnexion', 'export', 'view', 'search', 'ai_suggest'];
    if (in_array($action, $skipActions, true)) return;

    $map = [
        'create_societe' => [
            'target_role_id' => 1,
            'type' => 'success',
            'title' => 'Nouvelle société créée',
            'message' => fn($l) => "La société {$l} a été créée.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'create_associe' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Nouvel associé ajouté',
            'message' => fn($l) => "L'associé {$l} a été ajouté.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'create_contrat' => [
            'target_role_id' => 1,
            'type' => 'success',
            'title' => 'Nouveau contrat créé',
            'message' => fn($l) => "Un nouveau contrat a été créé pour {$l}.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'create_collaborateur' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Nouveau collaborateur',
            'message' => fn($l) => "Le collaborateur {$l} a été ajouté.",
            'link' => null,
        ],
        'create_dossier' => [
            'target_role_id' => 1,
            'type' => 'success',
            'title' => 'Nouveau dossier créé',
            'message' => fn($l) => "Le dossier complet de {$l} a été créé.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],

        'update_societe' => [
            'target_role_id' => 1,
            'type' => 'warning',
            'title' => 'Société modifiée',
            'message' => fn($l) => "La société {$l} a été modifiée.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'update_associe' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Associé modifié',
            'message' => fn($l) => "L'associé {$l} a été modifié.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'update_contrat' => [
            'target_role_id' => 1,
            'type' => 'warning',
            'title' => 'Contrat modifié',
            'message' => fn($l) => "Le contrat de {$l} a été modifié.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'update_collaborateur' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Collaborateur modifié',
            'message' => fn($l) => "Le collaborateur {$l} a été modifié.",
            'link' => null,
        ],
        'update_dossier' => [
            'target_role_id' => 1,
            'type' => 'warning',
            'title' => 'Dossier modifié',
            'message' => fn($l) => "Le dossier de {$l} a été modifié.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'update_cessions' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Cession de parts modifiée',
            'message' => fn($l) => "La cession de parts pour {$l} a été modifiée.",
            'link' => fn($id) => 'index.php?page=cession_dossier&id=' . $id,
        ],

        'delete_societe' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Société supprimée',
            'message' => fn($l) => "La société {$l} a été supprimée.",
            'link' => null,
        ],
        'delete_associe' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Associé supprimé',
            'message' => fn($l) => "L'associé {$l} a été supprimé.",
            'link' => null,
        ],
        'delete_contrat' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Contrat supprimé',
            'message' => fn($l) => "Le contrat de {$l} a été supprimé.",
            'link' => null,
        ],
        'delete_collaborateur' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Collaborateur supprimé',
            'message' => fn($l) => "Le collaborateur {$l} a été supprimé.",
            'link' => null,
        ],
        'delete_dossier' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Dossier supprimé',
            'message' => fn($l) => "Le dossier de {$l} a été supprimé.",
            'link' => null,
        ],
        'delete_cessions' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Cession de parts supprimée',
            'message' => fn($l) => "La cession de parts pour {$l} a été supprimée.",
            'link' => null,
        ],

        'generate_document' => [
            'target_role_id' => 1,
            'type' => 'success',
            'title' => 'Documents générés',
            'message' => fn($l) => "Des documents ont été générés pour {$l}.",
            'link' => fn($id) => 'index.php?page=societe&id=' . $id,
        ],
        'validate_document' => [
            'target_role_id' => 1,
            'type' => 'success',
            'title' => 'Document validé',
            'message' => fn($l) => "Le document {$l} a été validé.",
            'link' => null,
        ],
        'upload_document' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Document uploadé',
            'message' => fn($l) => "Le document {$l} a été uploadé.",
            'link' => null,
        ],
        'rename_variable' => [
            'target_role_id' => 1,
            'type' => 'info',
            'title' => 'Variable renommée',
            'message' => fn($l) => "La variable {$l} a été renommée dans les templates.",
            'link' => null,
        ],
        'bulk_rename_variable' => [
            'target_role_id' => 1,
            'type' => 'warning',
            'title' => 'Renommage groupé de variables',
            'message' => fn($l) => $l ? "Variables renommées : {$l}" : 'Plusieurs variables ont été renommées.',
            'link' => null,
        ],
        'bulk_delete_variable' => [
            'target_role_id' => 1,
            'type' => 'danger',
            'title' => 'Suppression groupée de variables',
            'message' => fn($l) => $l ? "Variables supprimées : {$l}" : 'Plusieurs variables ont été supprimées.',
            'link' => null,
        ],
    ];

    $key = $action . '_' . $entity_type;
    $config = $map[$key] ?? null;

    if (!$config) return;

    $user = current_user();
    $userName = $user['nom_complet'] ?? 'Un utilisateur';
    $label = $entity_label ?? ($entity_id ? '#' . $entity_id : '');
    $link = is_callable($config['link']) && $entity_id ? $config['link']($entity_id) : $config['link'];
    $message = is_callable($config['message']) ? $config['message']($label) : $config['message'];
    $message .= " — par {$userName}";

    // Avoid duplicate notifications for the same entity in the last hour
    try {
        $dupCheck = $pdo->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE entity_type = :et AND entity_id = :eid
              AND title = :title
              AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $dupCheck->execute([
            'et' => $entity_type,
            'eid' => $entity_id,
            'title' => $config['title'],
        ]);
        if ((int) $dupCheck->fetchColumn() > 0) return;
    } catch (PDOException) {
        return;
    }

    create_notification($pdo, [
        'target_role_id' => $config['target_role_id'] ?? null,
        'target_type' => 'interne',
        'type' => $config['type'] ?? 'info',
        'title' => $config['title'],
        'message' => $message,
        'link' => $link,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'is_global' => 0,
        'created_by' => $user['id'] ?? null,
    ]);
}

// ──────────────────────────────────────────────
// Notification System Helpers
// ──────────────────────────────────────────────

function create_notification(?PDO $pdo, array $data): ?int
{
    if (!$pdo) return null;
    try {
        $stmt = $pdo->prepare('
            INSERT INTO notifications (target_user_id, target_role_id, target_type, type, title, message, link, entity_type, entity_id, is_global, created_by, created_at)
            VALUES (:target_user_id, :target_role_id, :target_type, :type, :title, :message, :link, :entity_type, :entity_id, :is_global, :created_by, NOW())
        ');
        $stmt->execute([
            'target_user_id' => $data['target_user_id'] ?? null,
            'target_role_id' => $data['target_role_id'] ?? null,
            'target_type'    => $data['target_type'] ?? null,
            'type'           => $data['type'] ?? 'info',
            'title'          => $data['title'] ?? '',
            'message'        => $data['message'] ?? null,
            'link'           => $data['link'] ?? null,
            'entity_type'    => $data['entity_type'] ?? null,
            'entity_id'      => $data['entity_id'] ?? null,
            'is_global'      => (int) ($data['is_global'] ?? 0),
            'created_by'     => $data['created_by'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    } catch (PDOException) {
        return null;
    }
}

function get_user_notifications(?PDO $pdo, int $userId, int $roleId, ?string $collaboratorType, int $limit = 20, bool $unreadOnly = false): array
{
    if (!$pdo) return [];
    try {
        // A notification matches if:
        // 1. Directly targeted to this user, OR
        // 2. Targeted to this user's role (and no user-specific target), OR
        // 3. Targeted to this collaborator type (and no user/role target), OR
        // 4. Global (is_global = 1 and all targets are null)
        $orParts = [
            '(target_user_id = :uid)',
            '(target_user_id IS NULL AND target_role_id = :rid)',
        ];
        $params = ['uid' => $userId, 'rid' => $roleId];

        if ($collaboratorType !== null) {
            $orParts[] = '(target_user_id IS NULL AND target_role_id IS NULL AND target_type = :ctype)';
            $params['ctype'] = $collaboratorType;
        }

        $orParts[] = '(is_global = 1 AND target_user_id IS NULL AND target_role_id IS NULL AND target_type IS NULL)';

        $conditions = ['(' . implode(' OR ', $orParts) . ')'];

        if ($unreadOnly) {
            $conditions[] = 'is_read = 0';
        }

        $where = implode(' AND ', $conditions);

        $params['uid2'] = $userId;
        $params['rid2'] = $roleId;
        $params['lim'] = $limit;

        $stmt = $pdo->prepare("
            SELECT n.*, 
                   CASE 
                       WHEN n.target_user_id = :uid2 THEN 'direct'
                       WHEN n.target_role_id = :rid2 THEN 'role'
                       WHEN n.target_type IS NOT NULL THEN 'type'
                       ELSE 'global'
                   END AS delivery
            FROM notifications n
            WHERE $where
            ORDER BY n.created_at DESC
            LIMIT :lim
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function count_unread_notifications(?PDO $pdo, int $userId, int $roleId, ?string $collaboratorType): int
{
    if (!$pdo) return 0;
    try {
        $orParts = [
            '(target_user_id = :uid)',
            '(target_user_id IS NULL AND target_role_id = :rid)',
        ];
        $params = ['uid' => $userId, 'rid' => $roleId];

        if ($collaboratorType !== null) {
            $orParts[] = '(target_user_id IS NULL AND target_role_id IS NULL AND target_type = :ctype)';
            $params['ctype'] = $collaboratorType;
        }

        $orParts[] = '(is_global = 1 AND target_user_id IS NULL AND target_role_id IS NULL AND target_type IS NULL)';

        $where = '(is_read = 0) AND (' . implode(' OR ', $orParts) . ')';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications n WHERE $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

function mark_notification_read(?PDO $pdo, int $notifId, int $userId): bool
{
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id AND (target_user_id = :uid OR target_user_id IS NULL)');
        $stmt->execute(['id' => $notifId, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException) {
        return false;
    }
}

function mark_all_notifications_read(?PDO $pdo, int $userId, int $roleId, ?string $collaboratorType): bool
{
    if (!$pdo) return false;
    try {
        $orParts = [
            '(target_user_id = :uid)',
            '(target_user_id IS NULL AND target_role_id = :rid)',
        ];
        $params = ['uid' => $userId, 'rid' => $roleId];

        if ($collaboratorType !== null) {
            $orParts[] = '(target_user_id IS NULL AND target_role_id IS NULL AND target_type = :ctype)';
            $params['ctype'] = $collaboratorType;
        }

        $orParts[] = '(is_global = 1 AND target_user_id IS NULL AND target_role_id IS NULL AND target_type IS NULL)';

        $where = '(is_read = 0) AND (' . implode(' OR ', $orParts) . ')';
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE $where");
        $stmt->execute($params);
        return true;
    } catch (PDOException) {
        return false;
    }
}

function generate_auto_notifications(?PDO $pdo, int $createdBy): array
{
    if (!$pdo) return [];
    $generated = [];
    $today = date('Y-m-d');

    try {
        // 1. Societes sans associe (pour Super Admin + Admin)
        $stmt = $pdo->query("
            SELECT s.id, s.societe_raison_sociale FROM societes s
            LEFT JOIN associes a ON a.societe_id = s.id
            WHERE a.id IS NULL
        ");
        while ($row = $stmt->fetch()) {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE entity_type = 'societe' AND entity_id = :eid AND title LIKE '%associe%' AND created_at >= CURDATE()");
            $existing->execute(['eid' => $row['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                create_notification($pdo, [
                    'target_role_id' => 1,
                    'target_type'    => 'interne',
                    'type'           => 'warning',
                    'title'          => 'Societe sans associe',
                    'message'        => "{$row['societe_raison_sociale']} n'a pas encore d'associe.",
                    'link'           => app_url('societe', ['id' => $row['id']]),
                    'entity_type'    => 'societe',
                    'entity_id'      => $row['id'],
                    'is_global'      => 0,
                    'created_by'     => $createdBy,
                ]);
                $generated[] = "sans-associe-{$row['id']}";
            }
        }

        // 2. Societes sans contrat (Super Admin + Admin)
        $stmt = $pdo->query("
            SELECT s.id, s.societe_raison_sociale FROM societes s
            LEFT JOIN contrats c ON c.societe_id = s.id
            WHERE c.id IS NULL
        ");
        while ($row = $stmt->fetch()) {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE entity_type = 'societe' AND entity_id = :eid AND title LIKE '%contrat%' AND created_at >= CURDATE()");
            $existing->execute(['eid' => $row['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                create_notification($pdo, [
                    'target_role_id' => 1,
                    'target_type'    => 'interne',
                    'type'           => 'warning',
                    'title'          => 'Societe sans contrat',
                    'message'        => "{$row['societe_raison_sociale']} n'a pas encore de contrat.",
                    'link'           => app_url('societe', ['id' => $row['id']]),
                    'entity_type'    => 'societe',
                    'entity_id'      => $row['id'],
                    'is_global'      => 0,
                    'created_by'     => $createdBy,
                ]);
                $generated[] = "sans-contrat-{$row['id']}";
            }
        }

        // 3. CIN expirees (tous les internes)
        $stmt = $pdo->query("
            SELECT a.id, a.associe_nom_complet, a.associe_date_validite_cin, s.id AS sid, s.societe_raison_sociale
            FROM associes a
            JOIN societes s ON s.id = a.societe_id
            WHERE a.associe_date_validite_cin IS NOT NULL AND a.associe_date_validite_cin < CURDATE()
        ");
        while ($row = $stmt->fetch()) {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE entity_type = 'associe' AND entity_id = :eid AND title LIKE '%CIN%' AND created_at >= CURDATE()");
            $existing->execute(['eid' => $row['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                create_notification($pdo, [
                    'target_role_id' => 1,
                    'target_type'    => 'interne',
                    'type'           => 'danger',
                    'title'          => 'CIN expiree',
                    'message'        => "CIN de {$row['associe_nom_complet']} ( {$row['societe_raison_sociale']} ) expiree depuis le " . date('d/m/Y', strtotime($row['associe_date_validite_cin'])),
                    'link'           => app_url('societe', ['id' => $row['sid']]),
                    'entity_type'    => 'associe',
                    'entity_id'      => $row['id'],
                    'is_global'      => 0,
                    'created_by'     => $createdBy,
                ]);
                $generated[] = "cin-expiree-{$row['id']}";
            }
        }

        // 4. Contrats expirant dans 30 jours (tous)
        $stmt = $pdo->query("
            SELECT c.id, c.contrat_date_fin, s.societe_raison_sociale, s.id AS sid
            FROM contrats c
            JOIN societes s ON s.id = c.societe_id
            WHERE c.contrat_statut = 'actif' AND c.contrat_date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        while ($row = $stmt->fetch()) {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE entity_type = 'contrat' AND entity_id = :eid AND title LIKE '%expire%' AND created_at >= CURDATE()");
            $existing->execute(['eid' => $row['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                $daysLeft = (int) ((strtotime($row['contrat_date_fin']) - time()) / 86400);
                create_notification($pdo, [
                    'target_type'    => 'interne',
                    'type'           => $daysLeft <= 7 ? 'danger' : 'warning',
                    'title'          => 'Contrat proche d\'expiration',
                    'message'        => "Le contrat de {$row['societe_raison_sociale']} expire dans {$daysLeft} jours (" . date('d/m/Y', strtotime($row['contrat_date_fin'])) . ").",
                    'link'           => app_url('societe', ['id' => $row['sid']]),
                    'entity_type'    => 'contrat',
                    'entity_id'      => $row['id'],
                    'is_global'      => 0,
                    'created_by'     => $createdBy,
                ]);
                $generated[] = "contrat-exp-{$row['id']}";
            }
        }

        // 5. Societes sans documents (Super Admin + Admin)
        $stmt = $pdo->query("
            SELECT s.id, s.societe_raison_sociale FROM societes s
            WHERE EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
              AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
              AND NOT EXISTS (SELECT 1 FROM documents_generes d WHERE d.societe_id = s.id)
        ");
        while ($row = $stmt->fetch()) {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE entity_type = 'societe' AND entity_id = :eid AND title LIKE '%document%' AND created_at >= CURDATE()");
            $existing->execute(['eid' => $row['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                create_notification($pdo, [
                    'target_role_id' => 1,
                    'target_type'    => 'interne',
                    'type'           => 'info',
                    'title'          => 'Documents manquants',
                    'message'        => "{$row['societe_raison_sociale']} a des associes et un contrat mais aucun document genere.",
                    'link'           => app_url('generation', ['societe_id' => $row['id']]),
                    'entity_type'    => 'societe',
                    'entity_id'      => $row['id'],
                    'is_global'      => 0,
                    'created_by'     => $createdBy,
                ]);
                $generated[] = "sans-docs-{$row['id']}";
            }
        }

        // 6. Nouveaux collaborateurs externes (pour Super Admin)
        $stmt = $pdo->prepare("
            SELECT c.id, c.nom_complet, c.collaborateur_type, c.created_at
            FROM collaborateurs c
            WHERE c.collaborateur_type IN ('externe-pm', 'externe-pp')
              AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND c.created_by != :cby
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['cby' => $createdBy]);
        while ($row = $stmt->fetch()) {
            $existing = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE entity_type = 'collaborateur' AND entity_id = :eid AND created_at >= CURDATE()");
            $existing->execute(['eid' => $row['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                $typeLabel = $row['collaborateur_type'] === 'externe-pm' ? 'Personne Morale' : 'Personne Physique';
                create_notification($pdo, [
                    'target_role_id' => 1,
                    'type'           => 'info',
                    'title'          => 'Nouveau collaborateur externe',
                    'message'        => "{$row['nom_complet']} ({$typeLabel}) a ete ajoute recemment.",
                    'link'           => app_url('collaborateurs', ['id' => $row['id']]),
                    'entity_type'    => 'collaborateur',
                    'entity_id'      => $row['id'],
                    'is_global'      => 0,
                    'created_by'     => $createdBy,
                ]);
                $generated[] = "nv-collab-{$row['id']}";
            }
        }
    } catch (PDOException) {
        // silent
    }

    return $generated;
}

// ─── User Sessions & Online Tracking ─────────────────

function update_user_session(?PDO $pdo, string $currentPage): void
{
    if (!$pdo) return;
    $user = current_user();
    if (!$user) return;

    // Clean up stale sessions older than 1 hour
    try {
        $pdo->exec("DELETE FROM user_sessions WHERE last_active < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    } catch (PDOException) {}

    $sessionId = session_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, last_active, current_page, ip_address, user_agent, session_id)
            VALUES (:uid, NOW(), :page, :ip, :ua, :sid)
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                last_active = VALUES(last_active),
                current_page = VALUES(current_page),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");
        $stmt->execute([
            'uid'  => (int) $user['id'],
            'page' => $currentPage,
            'ip'   => $ip,
            'ua'   => $ua,
            'sid'  => $sessionId,
        ]);
    } catch (PDOException) {
        // silent
    }
}

function get_online_users(?PDO $pdo, int $minutes = 5): array
{
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare("
            SELECT us.user_id, c.nom_complet, c.role_id, r.nom AS role_nom,
                   us.current_page, us.last_active, us.ip_address
            FROM user_sessions us
            JOIN collaborateurs c ON c.id = us.user_id
            LEFT JOIN roles r ON r.id = c.role_id
            WHERE us.last_active >= DATE_SUB(NOW(), INTERVAL :min MINUTE)
            ORDER BY us.last_active DESC
        ");
        $stmt->execute(['min' => $minutes]);
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function get_most_visited_pages(?PDO $pdo, int $limit = 10): array
{
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare("
            SELECT entity_label AS page, COUNT(*) AS visits,
                   MAX(created_at) AS last_visit
            FROM activity_logs
            WHERE action = 'view' AND entity_type = 'page'
            GROUP BY entity_label
            ORDER BY visits DESC
            LIMIT :lim
        ");
        $stmt->execute(['lim' => $limit]);
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function log_page_view(?PDO $pdo, string $page): void
{
    if (!$pdo) return;
    $user = current_user();
    if (!$user) return;

    $viewed = $_SESSION['_viewed_pages'] ?? [];
    if (in_array($page, $viewed, true)) return;

    log_activity($pdo, 'view', 'page', null, $page);
    $_SESSION['_viewed_pages'][] = $page;
}

function page_display_name(string $page): string
{
    $base = explode('&', $page)[0];
    $map = [
        'dashboard' => 'Tableau de bord',
        'societes' => 'Sociétés',
        'societe' => 'Détail société',
        'creation' => 'Nouveau dossier',
        'associes' => 'Associés',
        'associe' => 'Détail associé',
        'contrats' => 'Contrats',
        'collaborateurs' => 'Collaborateurs',
        'collaborateur' => 'Nouveau collaborateur',
        'notifications' => 'Notifications',
        'templates' => 'Templates',
        'generation' => 'Génération de documents',
        'documents' => 'Documents générés',
        'configuration' => 'Configuration',
        'analyse-couverture' => 'Analyse de couverture',
        'variables' => 'Gestion des variables',
        'defaults' => 'Valeurs par défaut',
        'convert-word-pdf' => 'Conversion Word→PDF',
        'ai-assistant' => 'Assistant IA',
        'cesions' => 'Cessions de parts',
        'cession' => 'Nouvelle cession',
        'cession_dossier' => 'Dossier cession',
        'roles' => 'Gestion des rôles',
        'role' => 'Détail rôle',
        'activite' => "Journal d'activité",
        'modifications' => 'Modifications juridiques',
        'notifications-manage' => 'Gestion notifications',
        'connexion' => 'Connexion',
    ];
    return $map[$base] ?? $page;
}
