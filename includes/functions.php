<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
        ob_clean();
    }
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

function fetch_societes_options(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }

    $stmt = $pdo->query('SELECT id, raison_sociale FROM societes ORDER BY raison_sociale ASC');
    return $stmt->fetchAll();
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
        'ref_nationalites' => 'nationalite',
        'ref_lieux_naissance' => 'lieu_naissance',
        'ref_formes_juridiques' => 'forme_juridique',
        'ref_villes' => 'ville',
        'ref_qualites_associe' => 'qualite_associe',
    ];

    if (($allowed[$table] ?? null) !== $column) {
        return [];
    }

    $stmt = $pdo->query("SELECT {$column} FROM {$table} ORDER BY sort_order ASC, {$column} ASC");
    return array_map(static fn (array $row): string => (string) $row[$column], $stmt->fetchAll());
}

function fetch_all_documents(?PDO $pdo, ?int $societe_id = null, ?string $q = null): array
{
    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT d.*, s.raison_sociale
            FROM documents_generes d
            JOIN societes s ON s.id = d.societe_id
            WHERE 1=1';
    $params = [];

    if ($societe_id !== null) {
        $sql .= ' AND d.societe_id = :societe_id';
        $params['societe_id'] = $societe_id;
    }

    if ($q !== null && $q !== '') {
        $sql .= ' AND (s.raison_sociale LIKE :q OR d.doc_type LIKE :q2)';
        $params['q'] = like_term($q);
        $params['q2'] = like_term($q);
    }

    $sql .= ' ORDER BY d.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_document(?PDO $pdo, int $id): ?array
{
    if (!$pdo) {
        return null;
    }

    $sql = 'SELECT d.*, s.raison_sociale
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
