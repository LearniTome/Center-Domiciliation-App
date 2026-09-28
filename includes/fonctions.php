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
        $baseUrl = $protocol . '://' . $host . rtrim(str_replace('\\', '/', $scriptDir), '/');
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

function date_value(array $source, string $key): ?string
{
    $value = field_value($source, $key);
    return $value !== '' ? $value : null;
}

function next_dossier_number(?PDO $pdo, string $prefix, string $column): string
{
    $allowedColumns = ['societe_dossier_domiciliation_number', 'societe_dossier_creation_number'];
    if (!in_array($column, $allowedColumns, true)) {
        $column = 'societe_dossier_domiciliation_number';
    }
    $currentYear = date('Y');
    $num = 1;
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX({$column}, '-', -1) AS UNSIGNED)), 0) FROM societes WHERE {$column} LIKE :pattern");
        $stmt->execute(['pattern' => "{$prefix}-{$currentYear}-%"]);
        $num = (int) $stmt->fetchColumn() + 1;
    }
    return sprintf('%s-%s-%03d', $prefix, $currentYear, $num);
}

function dashboard_count(?PDO $pdo, string $table): int
{
    if (!$pdo) {
        return 0;
    }

    $allowed = ['societes', 'associes', 'contrats', 'collaborateurs'];
    if (!in_array($table, $allowed, true)) {
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

    // Cloisonnement applique ici, au point d'entree unique de toute lecture
    // par identifiant : aucune page detail ne peut contourner le filtre en
    // changeant l'id dans l'URL. Une ligne d'un autre cabinet est traitee
    // comme inexistante (on ne confirme pas son existence a l'appelant).
    if (!assert_tenant_access($pdo, $table, $id)) {
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

    // Cloisonnement par defaut : cet helper alimente les listes deroulantes de
    // presque toutes les pages. Sans lui, un adherent de cabinet pouvait
    // choisir une societe d'un autre tenant.
    $sql = 'SELECT id, societe_raison_sociale, societe_ice, societe_ville FROM societes';
    $params = [];

    $where = [];
    $tenant = tenant_scope();
    if ($tenant['sql'] !== '') {
        $where[] = $tenant['sql'];
        $params += $tenant['params'];
    }
    if ($userId !== null) {
        $where[] = 'created_by = :user_id';
        $params['user_id'] = $userId;
    }
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY societe_raison_sociale ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Vocabulary unique du statut d'un contrat de domiciliation.
 *
 * Source de verite partagee par la liste, la page de suivi, le detail et le
 * tableau de bord. Le tableau de bord comptait un 'resilie' qui n'etait
 * saisissable nulle part, et les listes proposaient un 'expire' qu'aucun code
 * ne produisait : les compteurs et les libelles divergeaient.
 *
 * 'expire' reste une valeur a part entière : un contrat echu qui n'a pas ete
 * resilie est un cas metier reel, distinct du contrat resilie.
 *
 * @return array<string, string> code => libelle affiche
 */
function contrat_statuts(): array
{
    return [
        'brouillon' => 'Brouillon',
        'actif' => 'Actif',
        'expire' => 'Echu',
        'resilie' => 'Resilie',
    ];
}

/**
 * Seuils de suivi des contrats, lus depuis config/defaults.json (section
 * "seuils"). Centralises pour que la page de suivi, la fiche contrat et le
 * tableau de bord n'aient pas chacun leur constante en dur.
 */
function contrat_seuils(): array
{
    $seuils = load_defaults('seuils');

    return [
        'renouvellement' => (int) ($seuils['contrat_renouvellement_jours'] ?? 30),
        'alerte' => (int) ($seuils['contrat_alerte_jours'] ?? 90),
        'critique' => (int) ($seuils['contrat_critique_jours'] ?? 15),
    ];
}

/** Les onglets du suivi des contrats, dans leur ordre d'affichage. */
function contrat_vues(): array
{
    return [
        'actifs' => 'Actifs',
        'renouvellement' => 'À renouveler',
        'echus' => 'Échus non résolus',
        'resilies' => 'Résiliés',
    ];
}

/**
 * Appartenance d'un contrat a une vue de suivi.
 *
 * Utilisee par la page de suivi et par l'export de la liste, afin qu'un CSV
 * produit depuis l'onglet "Résiliés" ne contienne pas les contrats actifs.
 */
function contrat_dans_vue(string $vue, ?string $statut, ?int $jours): bool
{
    $statut = trim((string) $statut);
    $seuil = contrat_seuils()['renouvellement'];

    return match ($vue) {
        'renouvellement' => $statut === 'actif' && $jours !== null && $jours >= 0 && $jours <= $seuil,
        'echus' => $statut === 'expire' || ($statut === 'actif' && $jours !== null && $jours < 0),
        'resilies' => $statut === 'resilie',
        default => $statut === 'actif' && ($jours === null || $jours >= 0),
    };
}

/**
 * Verifie qu'une chaine est une date ISO reelle (YYYY-MM-DD).
 *
 * Le seul test /^\d{4}-\d{2}-\d{2}$/ laisse passer "2026-13-45" : MySQL le
 * stocke alors en 0000-00-00, ce qui casse ensuite tous les calculs d'ecart
 * de la page de suivi.
 */
function date_iso_valide(?string $date): bool
{
    if ($date === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    [$annee, $mois, $jour] = array_map('intval', explode('-', $date));

    return checkdate($mois, $jour, $annee);
}

/**
 * Filtre SQL restreignant les contrats aux societes creees par l'utilisateur
 * connecte, sauf pour les administrateurs (roles 1 et 2).
 *
 * Les pages liste, suivi et fiche contrat doivent toutes l'appliquer : sinon
 * un utilisateur non admin voit sur le suivi les contrats de societes qu'il
 * n'a pas crees, alors que la liste des contrats les lui masque.
 *
 * @param  array|null  $user  current_user()
 * @param  string      $alias Alias de `societes` DANS la requete appelante.
 * @return array{sql: string, params: array<string, int>}
 */
function contrat_user_filter(?array $user, string $alias = 'societes'): array
{
    // Delegue a list_scope() : le test (int) $user['role_id'] contre [1, 2]
    // est remplace par la permission dossiers.view_all, et le cloisonnement
    // par cabinet est ajoute. Le parametre $user est conserve pour la
    // signature publique (3 appelants), mais n'est plus utilise.
    unset($user);

    // `$alias` est indispensable : `list_scope()` prefixe la colonne par
    // l'alias, et une table aliasee s'appelle par son alias. Un appelant
    // écrivant `INNER JOIN societes s` tout en passant le nom complet
    // produisait `societes.cabinet_id`, que MySQL rejette
    // (« Unknown column ») : la page levait une PDOException au lieu
    // d'afficher une liste cloisonnee.
    return list_scope($alias);
}

/**
 * Convertit une valeur issue de la base (decimal MySQL renvoye en chaine)
 * en float, ou null si la valeur est absente. Indispensable avec
 * declare(strict_types=1) : format_money() attend un ?float et refuse une
 * chaine numerique.
 */
function money_from(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    return is_numeric((string) $value) ? (float) $value : null;
}

/** Libelle lisible d'un statut de contrat, avec repli sur le code brut. */
function contrat_statut_libelle(?string $statut): string
{
    $statut = trim((string) $statut);
    if ($statut === '') {
        return 'Non renseigne';
    }

    return contrat_statuts()[$statut] ?? $statut;
}

/**
 * Nombre de jours avant echeance d'un contrat.
 * Negatif si l'echeance est depassee, null si aucune date n'est renseignee.
 */
function contrat_jours_avant_echeance(?string $dateFin): ?int
{
    if ($dateFin === null || trim($dateFin) === '') {
        return null;
    }

    try {
        // On compare deux dates normalisees a minuit : l'ecart doit tomber
        // sur un nombre entier de jours, quelle que soit l'heure de
        // consultation. Une soustraction d'entiers YYYYMMDD est fausse
        // (20261005 - 20260925 vaut 80 au lieu de 10) car elle effectue un
        // emprunt sur les mois. diff() donne l'ecart reel et gere en plus
        // les changements d'heure.
        $echeance = (new DateTimeImmutable($dateFin))->setTime(0, 0);
        $aujourdhui = new DateTimeImmutable('today');
    } catch (Exception) {
        return null;
    }

    $ecart = $aujourdhui->diff($echeance);

    return $ecart->invert ? -(int) $ecart->days : (int) $ecart->days;
}

/**
 * Calcule le code dossier d'un intermediaire cote serveur.
 *
 * Le champ "Code (genere)" de la quick-create est readonly et alimente en
 * JavaScript : ce n'est qu'un confort d'affichage. La valeur autoritative est
 * recalculee ici, sinon un POST direct (ou deux creations simultanees)
 * enregistrerait un code arbitraire — ou vide — alors qu'un index unique
 * existe sur collaborateur_code.
 *
 * Les comptes internes (can_login) et les gens morales (den_ste) ne sont pas
 * des intermediaires nommes : ils conservent le code fourni, souvent vide.
 *
 * @param array $data  Ligne en cours d'insertion ou de mise a jour.
 * @param int|null $excludeId Collaborateur ignore (edition en cours).
 */
function code_collaborateur_intermediaire(?PDO $pdo, array $data, ?int $excludeId = null): string
{
    $estIntermediaire = trim((string) ($data['collaborateur_nom'] ?? '')) !== ''
        || trim((string) ($data['collaborateur_prenom'] ?? '')) !== '';
    if (!$estIntermediaire || !class_exists('DossierNaming')) {
        return trim((string) ($data['collaborateur_code'] ?? ''));
    }

    $qualiteCode = '';
    $qualiteId = (int) ($data['qualite_intermediaire_id'] ?? 0);
    if ($qualiteId > 0 && $pdo) {
        $stmt = $pdo->prepare('SELECT code FROM ref_qualites_intermediaire WHERE id = :id');
        $stmt->execute(['id' => $qualiteId]);
        $qualiteCode = (string) ($stmt->fetchColumn() ?: '');
    }

    $code = DossierNaming::codeCollaborateur(
        $pdo,
        $qualiteCode,
        (string) ($data['collaborateur_nom'] ?? ''),
        (string) ($data['collaborateur_prenom'] ?? ''),
        $excludeId
    );

    // Aucun nom exploitable : on laisse la colonne vide plutot que d'ecraser
    // une saisie manuelle par un code generique "COLLAB".
    if ($code === 'COLLAB' && trim((string) ($data['collaborateur_code'] ?? '')) !== '') {
        return trim((string) $data['collaborateur_code']);
    }

    return $code;
}

/**
 * Un collaborateur ne peut pas exister en double : la regle porte sur le nom
 * complet. La colonne est en utf8mb4_unicode_ci, la comparaison est donc
 * insensible a la casse et aux accents ("Amrani Salma" = "amrani salma").
 *
 * Le nom seul n'identifie pas une personne (deux homonymes sont legitimes),
 * mais il est le seul identifiant saisi sur toutes les entrees (quick-create,
 * import Excel, formulaire) : c'est donc le seul point de controle commun.
 * collaborateur_code, lui, est genere et ne peut rien attraper.
 *
 * @param int|null $excludeId Collaborateur ignore (edition en cours).
 */
function collaborateur_nom_existe(?PDO $pdo, string $nomComplet, ?int $excludeId = null): bool
{
    $nomComplet = trim($nomComplet);
    if ($pdo === null || $nomComplet === '') {
        return false;
    }

    $sql = 'SELECT 1 FROM collaborateurs WHERE nom_complet = :nom';
    $params = [':nom' => $nomComplet];
    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' AND id <> :id';
        $params[':id'] = $excludeId;
    }
    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn() !== false;
}

function fetch_collaborateurs_options(?PDO $pdo, bool $actifsSeulement = true): array
{
    if (!$pdo) {
        return [];
    }

    $sql = "SELECT c.id, c.nom_complet, c.collaborateur_code, c.collaborateur_type,
                   q.code AS qualite_code, q.libelle AS qualite_libelle
              FROM collaborateurs c
              LEFT JOIN ref_qualites_intermediaire q ON q.id = c.qualite_intermediaire_id";
    if ($actifsSeulement) {
        $sql .= " WHERE c.statut = 'actif'";
    }
    $sql .= " ORDER BY c.nom_complet ASC";

    try {
        $stmt = $pdo->query($sql);
        $options = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $label = (string) $row['nom_complet'];
            $detail = array_filter([
                $row['qualite_libelle'] ?? null,
                $row['collaborateur_code'] ?? null,
            ], static fn($v) => $v !== null && $v !== '');
            if ($detail !== []) {
                $label .= ' — ' . implode(' / ', $detail);
            }
            $options[(int) $row['id']] = $label;
        }
        return $options;
    } catch (PDOException) {
        return [];
    }
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
        'ref_activites_statuts' => 'activite',
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

    // Cloisonnement applique ICI et non laisse au calcul de l appelant.
    // `$userId` vaut null pour un adherent de cabinet : ne s en remettre
    // qu'a lui revenait a afficher la production documentaire de tous les
    // tenants. Le filtre sur `created_by` reste un complement, il restreint
    // dans le perimetre du cabinet, il ne le definit pas.
    $tenant = tenant_scope('d');
    if ($tenant['sql'] !== '') {
        $sql .= ' AND ' . $tenant['sql'];
        $params += $tenant['params'];
    }

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

function nombre_en_lettres(int $nombre, string $unite = ''): string
{
    $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    $dizaines = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];
    if ($nombre === 0) return 'zéro' . ($unite ? ' ' . $unite : '');
    if ($nombre < 0) return 'moins ' . nombre_en_lettres(-$nombre, $unite);
    $words = '';
    if ($nombre >= 1000000) {
        $millions = intdiv($nombre, 1000000);
        $words .= nombre_en_lettres($millions) . ' million' . ($millions > 1 ? 's' : '') . ' ';
        $nombre %= 1000000;
    }
    if ($nombre >= 1000) {
        $milliers = intdiv($nombre, 1000);
        if ($milliers > 1) $words .= nombre_en_lettres($milliers) . ' ';
        $words .= 'mille ';
        $nombre %= 1000;
    }
    if ($nombre >= 100) {
        $centaines = intdiv($nombre, 100);
        if ($centaines > 1) $words .= $unites[$centaines] . ' ';
        $words .= 'cent' . ($centaines > 1 && $nombre % 100 === 0 ? 's' : '') . ' ';
        $nombre %= 100;
    }
    if ($nombre > 0) {
        if ($nombre < 20) {
            $words .= $unites[$nombre] . ' ';
        } else {
            $d = intdiv($nombre, 10);
            $u = $nombre % 10;
            if ($d === 7 || $d === 9) {
                $words .= $dizaines[$d] . '-' . $unites[10 + $u] . ' ';
            } else {
                $words .= $dizaines[$d];
                if ($u === 1 && $d > 1) {
                    $words .= ' et un ';
                } elseif ($u > 0) {
                    $words .= '-' . $unites[$u] . ' ';
                } else {
                    $words .= $d === 8 ? 's' : '';
                    $words .= ' ';
                }
            }
        }
    }
    $result = trim($words);
    if ($unite) $result .= ' ' . $unite;
    return $result;
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

/**
 * Libellé de section à afficher dans la barre supérieure pour les pages
 * de configuration (Centre d'affaires / Accès & audit / Référentiels), au lieu
 * du titre de l'onglet courant.
 */
function config_section_title_for_page(string $page): ?string
{
    $map = [
        'roles' => "Accès & audit",
        'activite' => "Accès & audit",
        'notifications-manage' => "Accès & audit",
        'centre' => "Centre d'affaires",
        'formes-juridiques' => 'Référentiels',
        'tribunaux' => 'Référentiels',
        'villes' => 'Référentiels',
        'nationalites' => 'Référentiels',
        'lieux-naissance' => 'Référentiels',
        'adresses' => 'Référentiels',
        'qualites-associe' => 'Référentiels',
        'fonctions' => 'Référentiels',
        'activites_statuts' => 'Référentiels',
        'activites-ompic' => 'Référentiels',
    ];

    return $map[$page] ?? null;
}

/**
 * Fiche singleton du centre d'affaires (table centre_affaires, id = 1).
 * Retourne un tableau de valeurs par defaut si la table est absente ou vide.
 */
function get_centre_affaires(?PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $defaults = [
        'denomination' => '',
        'adresse' => '',
        'numero_if' => '',
        'numero_ice' => '',
        'numero_rc' => '',
        'numero_tp' => '',
        'numero_cnss' => '',
        'adresse_dgi' => '',
        'adresse_cnss' => '',
        'logo_path' => '',
    ];

    if (!$pdo instanceof PDO) {
        $cache = $defaults;

        return $cache;
    }

    try {
        $row = $pdo->query(
            'SELECT denomination, adresse, numero_if, numero_ice, numero_rc, numero_tp, numero_cnss, adresse_dgi, adresse_cnss, logo_path FROM centre_affaires WHERE id = 1'
        )->fetch();
        $cache = array_merge($defaults, is_array($row) ? $row : []);
    } catch (PDOException) {
        $cache = $defaults;
    }

    return $cache;
}

/**
 * Chemin relatif du logo du centre d'affaires ('' si aucun logo).
 */
function get_centre_logo_path(?PDO $pdo): string
{
    return trim((string) (get_centre_affaires($pdo)['logo_path'] ?? ''));
}

/**
 * Matching tolerant d'un doc_type contre des motifs de prefixe.
 * Normalise la casse/ponctuation et ignore les variantes "_Template v2" du nom de fichier.
 * Ex : "Attestation-Domiciliation-Template v2" correspond au motif "Attestation-Domiciliation".
 */
function doc_type_matches_patterns(string $docType, array $patterns): bool
{
    $normalize = static function (string $s): string {
        $s = strtolower($s);
        $s = preg_replace('/template.*$/', '', $s) ?? $s;

        return preg_replace('/[^a-z0-9]/', '', $s) ?? $s;
    };

    $nDoc = $normalize($docType);
    if ($nDoc === '') {
        return false;
    }

    foreach ($patterns as $pattern) {
        $nPattern = $normalize((string) $pattern);
        if ($nPattern !== '' && str_starts_with($nDoc, $nPattern)) {
            return true;
        }
    }

    return false;
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

    // `users` est l'identite de connexion (interne Centre ou adherent d'un
    // cabinet). `collaborateurs` reste la donnee metier : on la joint pour
    // exposer collaborateur_type, utilise par le wizard pour distinguer un
    // dossier interne d'un dossier externalise.
    $stmt = $pdo->prepare('
        SELECT u.*, r.nom AS role_nom, r.scope AS role_scope, r.is_system AS role_is_system,
               c.collaborateur_type
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        LEFT JOIN collaborateurs c ON c.id = u.collaborateur_id
        WHERE u.id = :id AND u.statut = \'actif\'
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

/**
 * Identifiant du collaborateur metier associe au compte connecte.
 *
 * Les colonnes `created_by` des tables dossier (societes, cessions, pv_ago,
 * societe_suivi_etapes) designent un COLLABORATEUR, pas un compte de
 * connexion : un adherent de cabinet n'a pas de fiche collaborateur, il
 * s'appuie sur le filtre `cabinet_id`. C'est pourquoi l'identite de
 * connexion vit dans `users` et celle du dossier dans `collaborateurs`.
 */
function current_collaborateur_id(): ?int
{
    $user = current_user();
    $cid = $user['collaborateur_id'] ?? null;

    return $cid === null ? null : (int) $cid;
}

/** Tenant du compte connecte : null = employe interne du Centre (acces total). */
function current_cabinet_id(): ?int
{
    $user = current_user();
    $cid = $user['cabinet_id'] ?? null;

    return $cid === null ? null : (int) $cid;
}

/** Vrai si le compte est un employe interne du Centre (porteur de la plateforme). */
function is_centre_user(): bool
{
    return current_cabinet_id() === null;
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

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_SECONDS = 20 * 60;
const LOGIN_BACKOFF_SECONDS = 60;
const LOGIN_BACKOFF_CAP = 3600;

/**
 * Rate-limiting connexion : compte les echecs recents (email OU IP) sur une
 * fenetre glissante de 20 min, purge les traces de plus de 24 h.
 * Au-delà de 5 echecs, delai croissant (60 s puis x2, plafonne a 1 h).
 *
 * @return array{blocked: bool, count: int, retry_after: int}
 */
function login_throttle_state(?PDO $pdo, string $email, string $ip): array
{
    $state = ['blocked' => false, 'count' => 0, 'retry_after' => 0];

    if (!$pdo instanceof PDO) {
        return $state;
    }

    // Purge des traces de plus de 24 h (evite la croissance illimitee de la table)
    $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 24 HOUR');

    $stmt = $pdo->prepare('
        SELECT COUNT(*) AS c, MAX(attempted_at) AS last_attempt,
               TIMESTAMPDIFF(SECOND, MAX(attempted_at), NOW()) AS elapsed_s
        FROM login_attempts
        WHERE attempted_at >= NOW() - INTERVAL 20 MINUTE
          AND (email = :email OR ip_address = :ip)
    ');
    $stmt->execute(['email' => $email, 'ip' => $ip]);
    $row = $stmt->fetch();

    $count = (int) ($row['c'] ?? 0);
    $state['count'] = $count;

    if ($count < LOGIN_MAX_ATTEMPTS) {
        return $state;
    }

    // Delai croissant : 60 s apres 5 echecs, x2 a chaque echec supplementaire (plafonne a 1 h).
    // elapsed_s evite tout melange de fuseaux PHP/MySQL : le delai restant est
    // calcule par rapport a l'horloge du serveur de base de donnees.
    $backoff = min(LOGIN_BACKOFF_CAP, LOGIN_BACKOFF_SECONDS * 2 ** ($count - LOGIN_MAX_ATTEMPTS));
    $retryAfter = $backoff - (int) ($row['elapsed_s'] ?? 0);

    if ($retryAfter > 0) {
        $state['blocked'] = true;
        $state['retry_after'] = $retryAfter;
    }

    return $state;
}

function login_throttle_register_failure(?PDO $pdo, string $email, string $ip): void
{
    if (!$pdo instanceof PDO || $email === '') {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
    $stmt->execute(['email' => $email, 'ip' => $ip]);
}

function login_throttle_clear(?PDO $pdo, string $email, string $ip): void
{
    if (!$pdo instanceof PDO) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip');
    $stmt->execute(['email' => $email, 'ip' => $ip]);
}

function get_user_permissions(): array
{
    if (!empty($_SESSION['_permissions_cache']) && is_array($_SESSION['_permissions_cache'])) {
        return $_SESSION['_permissions_cache'];
    }

    $user = current_user();
    if (!$user) {
        $_SESSION['_permissions_cache'] = [];
        return [];
    }

    global $pdo;
    if (!$pdo) {
        return [];
    }

    // Permissions de TOUS les roles portes par le compte, reunion de deux
    // sources :
    //  - `user_roles` : le pivot multi-roles, source de reference ;
    //  - `users.role_id` : le role primaire denormalise.
    //
    // `role_id` est consulte meme quand le pivot existe deja : la migration
    // d'installation (20260927_100003) l'a recopie une seule fois, a une
    // epoque ou il n'y avait qu'un role par compte. Depuis, tout compte cree
    // avec seulement `role_id` (creation d'utilisateur Phase 5, import,
    // saisie directe) n'a AUCUNE ligne de pivot, donc ZERO permission.
    //
    // L'effet est un verrouillage total et silencieux : `require_permission()`
    // renvoie l'utilisateur vers `dashboard`, qui exige `dashboard.view`,
    // que l'utilisateur n'a pas non plus. Le garde refuse donc `dashboard` et
    // y renvoie a nouveau : boucle de redirection (ERR_TOO_MANY_REDIRECTS) sur
    // le dashboard, sans aucun message. Aucune page n'est plus accessible.
    //
    // L'union rend le provisionnement tolerant aux deux ecritures : un compte
    // peut etre cree via `role_id`, via le pivot, ou par les deux.
    $stmt = $pdo->prepare('
        SELECT DISTINCT p.permission_key
        FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        WHERE rp.role_id IN (
            SELECT role_id FROM user_roles WHERE user_id = :uid_roles
            UNION
            SELECT role_id FROM users WHERE id = :uid_primary AND role_id IS NOT NULL
        )
    ');
    $stmt->execute([
        'uid_roles' => (int) $user['id'],
        'uid_primary' => (int) $user['id'],
    ]);
    $perms = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    // Surcharges individuelles (grant/refuse) : le refuse l'emporte.
    $stmt = $pdo->prepare('
        SELECT p.permission_key, up.granted
        FROM user_permissions up
        JOIN permissions p ON p.id = up.permission_id
        WHERE up.user_id = :uid
    ');
    $stmt->execute(['uid' => (int) $user['id']]);
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
    $user = current_user();

    // Role systeme (Super Admin) : acces total. On lit le drapeau
    // `roles.is_system` plutot qu'un role_id fige, pour que le Super Admin ne
    // soit plus identifie par un entier magique.
    if ($user && (int) ($user['role_is_system'] ?? 0) === 1) {
        return true;
    }

    $permissions = get_user_permissions();
    return in_array($key, $permissions, true);
}

/** Raccourci : le compte connecte porte-t-il un role d administration du Centre ? */
function is_centre_admin(): bool
{
    $user = current_user();

    return $user !== null && (int) ($user['role_is_system'] ?? 0) === 1;
}

/**
 * Le compte connecte voit-il les dossiers de tous les collaborateurs ?
 *
 * Remplace les anciens tests `(int) $user['role_id']` contre [1, 2] : la
 * decision porte sur une permission nommee, donc elle survit a un
 * renumerotation des roles. Un adherent de cabinet repond toujours non : sa
 * portee est fixee par `cabinet_id`, pas par son role.
 */
function sees_all_dossiers(): bool
{
    return has_permission('dossiers.view_all');
}

/**
 * Page en cours de service, telle que resolue par le front controller.
 *
 * `index.php` resout `$page` avant d'appeler `require_page_access()` : la
 * globale est donc deja fiable a cet instant. `$_GET` sert de repli pour les
 * appels qui n emmanent pas du routage.
 */
function current_page(): string
{
    $page = $GLOBALS['page'] ?? null;
    if (!is_string($page) || $page === '') {
        $page = $_GET['page'] ?? '';
    }

    return is_string($page) ? $page : '';
}

function require_permission(string $key): void
{
    if (has_permission($key)) {
        return;
    }

    set_flash('error', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');

    // La destination de repli est `dashboard`. Y renvoyer l'utilisateur
    // alors qu'il demande deja `dashboard` produit une boucle de
    // redirection : le navigateur abandonne sur ERR_TOO_MANY_REDIRECTS et
    // l'utilisateur ne voit meme pas le message. Ce cas se produit quand le
    // role ne porte pas `dashboard.view`.
    //
    // Aucun repli n'etant possible, on repond 403 et on arrete le rendu.
    // C est un etat reel et atteignable : un role cree sans aucune
    // permission, ou un compte dont le `role_id` est invalide, y conduit.
    if (current_page() === 'dashboard') {
        http_response_code(403);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $deconnexion = e(app_url('deconnexion'));
        echo '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Accès refusé</title></head><body>'
            . '<h1>Accès refusé</h1>'
            . '<p>Votre rôle ne donne pas accès au tableau de bord. '
            . 'Contactez un administrateur pour qu\'il vous attribue les droits '
            . 'correspondants.</p>'
            . '<p><a href="' . $deconnexion . '">Se déconnecter</a></p>'
            . '</body></html>';
        exit;
    }

    redirect_to('dashboard');
}

// ─── Multi-tenancy : isolation des données par cabinet ───────────

/**
 * Fragment SQL restreignant une table metier au tenant du compte connecte.
 *
 * Base partagee, une seule ligne par tenant :
 *   - employe interne du Centre (`cabinet_id` de session = null) : aucune
 *     restriction, il porte la plateforme et voit tous les tenants ;
 *   - adherent de cabinet : uniquement les lignes de SON cabinet.
 *
 * Le discriminant est un NULL, pas une valeur sentinelle : les ~10 000 lignes
 * deja en base restent propriete du Centre, aucune migration de donnees.
 *
 * @return array{sql: string, params: array<string, int>}
 */
function tenant_scope(?string $alias = null): array
{
    $column = ($alias !== null && $alias !== '' ? $alias . '.' : '') . 'cabinet_id';
    $cabinetId = current_cabinet_id();

    if ($cabinetId === null) {
        return ['sql' => '', 'params' => []];
    }

    return ['sql' => $column . ' = :tenant_id', 'params' => ['tenant_id' => $cabinetId]];
}

/**
 * Meme chose mais en incluant les lignes du Centre, pour les listes ou le
 * Centre doit voir ses propres dossiers a cote de ceux de ses clients.
 */
function tenant_scope_with_centre(?string $alias = null): array
{
    $column = ($alias !== null && $alias !== '' ? $alias . '.' : '') . 'cabinet_id';
    $cabinetId = current_cabinet_id();

    if ($cabinetId === null) {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => '(' . $column . ' = :tenant_id OR ' . $column . ' IS NULL)',
        'params' => ['tenant_id' => $cabinetId],
    ];
}

/**
 * Garde anti-IDOR : verifie qu'une ligne appartient bien au tenant courant.
 *
 * A appeler sur TOUTE page detail qui lit par `?id=` (`societe`, `associe`,
 * `contrat`, `collaborateur`, `cession_dossier`, `societe_suivi`...). Sans
 * ce controle, un adherent de cabinet lit le dossier d'un autre cabinet en
 * changeant l'identifiant dans l'URL.
 *
 * @return bool false si la ligne existe mais appartient a un autre tenant
 *              (le appelant doit alors repondre 403)
 */
function assert_tenant_access(?PDO $pdo, string $table, int $id, string $column = 'id'): bool
{
    $cabinetId = current_cabinet_id();

    // Le Centre n'est pas confine : acces total.
    if ($cabinetId === null) {
        return true;
    }

    if (!$pdo instanceof PDO || $id <= 0) {
        return false;
    }

    if (!in_array($table, tenant_scoped_tables(), true)) {
        // Table non cloisonnee : le confinement ne s'applique pas.
        return true;
    }

    if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $column)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("SELECT cabinet_id FROM `{$table}` WHERE `{$column}` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
    } catch (PDOException) {
        return false;
    }

    if (!$row) {
        return true; // ligne inexistante : le appelant gere le 404
    }

    $rowCabinet = $row['cabinet_id'] === null ? null : (int) $row['cabinet_id'];

    return $rowCabinet === $cabinetId;
}

/** Tables cloisonnees : la liste est fermee, un oubli passerait inapercu. */
function tenant_scoped_tables(): array
{
    return [
        'societes', 'associes', 'contrats', 'collaborateurs', 'cessions', 'pv_ago',
        'documents_generes', 'uploaded_docs', 'societe_suivi_etapes',
        'societe_suivi_documents', 'cession_suivi_etapes', 'cession_suivi_documents',
        'cession_parts', 'activity_logs', 'notifications',
    ];
}

/** Gere l'echec du controle : 403 si la ligne existe, 404 sinon. */
function deny_tenant_access(bool $rowExists = true): never
{
    if ($rowExists) {
        http_response_code(403);
        set_flash('error', 'Acces refuse : ce dossier appartient a un autre cabinet.');
    } else {
        http_response_code(404);
        set_flash('error', 'Dossier introuvable.');
    }

    redirect_to('dashboard');
}

/**
 * Ne conserver que les identifiants reellement accessibles au tenant connecte.
 *
 * Complementaire d'`assert_tenant_access()` pour les operations de LOT. Un
 * formulaire de suppression ou de validation soumet `$_POST['selected_files']` :
 * ce tableau est une donnee d'attaque, pas une preuve de droits. Sans ce
 * filtrage, un adherent de cabinet supprime les fichiers d'un autre cabinet en
 * forgeant des identifiants dans la requete.
 *
 * Le comportement retenu est l'echec PARTIEL et non le refus global : l'ecart
 * entre le nombre demande et le nombre reellement traite doit etre annonce,
 * sinon l'utilisateur croit a tort que son lot a ete pris en compte.
 *
 * @param  array<int|string> $ids
 * @return array{ids: list<int>, ignores: int}
 */
function filter_accessible_ids(?PDO $pdo, string $table, array $ids, string $column = 'id'): array
{
    $ids = array_values(array_unique(array_filter(
        array_map('intval', $ids),
        static fn(int $id): bool => $id > 0
    )));

    if ($ids === [] || !$pdo instanceof PDO) {
        return ['ids' => [], 'ignores' => count($ids)];
    }

    $visibles = array_values(array_filter(
        $ids,
        static fn(int $id): bool => assert_tenant_access($pdo, $table, $id, $column)
    ));

    return ['ids' => $visibles, 'ignores' => count($ids) - count($visibles)];
}

/**
 * Message de flash decrivant un traitement de lot partiellement refuse.
 *
 * Un compte Centre n'a aucun element hors perimetre : on n'emet rien, le
 * silence evite d'alourdir chaque operation de la page.
 */
function flash_partial_batch(?string $action, int $ignores): void
{
    if ($ignores <= 0) {
        return;
    }

    set_flash(
        'error',
        $ignores . ' element(s) ignores : ' . $action . ' n\'a ete execute que sur '
        . 'les elements accessibles a votre cabinet.'
    );
}

/**
 * Portee de lecture d'une liste metier, en un seul fragment SQL.
 *
 * Regroupe les deux filtres qui coexistaient sans ordre de priorite dans les
 * pages liste :
 *
 *   1. cloisonnement par cabinet — un adherent voit l'integralite de SON
 *      cabinet, et uniquement celle-la ;
 *   2. portee individuelle — un employe interne qui n'est pas responsable
 *      voit les dossiers dont il est `created_by`.
 *
 * Le cloisonnement l'emporte : un adherent de cabinet n'a pas de fiche
 * collaborateur, filtrer sur `created_by` le viderait de toute liste.
 *
 * Si un employe interne n'a aucune fiche collaborateur, le filtre tombe a
 * `1 = 0` (etat refuse) plutot que de disparaitre : l'absence de portee doit
 * se lire comme un refus, jamais comme un acces total.
 *
 * @return array{sql: string, params: array<string, int>}
 */
function list_scope(string $alias = '', ?string $createdByColumn = 'created_by'): array
{
    $prefix = $alias !== '' ? $alias . '.' : '';

    if (!is_centre_user()) {
        $cabinetId = current_cabinet_id();

        if ($cabinetId === null) {
            return ['sql' => '1 = 0', 'params' => []];
        }

        return [
            'sql' => $prefix . 'cabinet_id = :scope_cabinet',
            'params' => ['scope_cabinet' => $cabinetId],
        ];
    }

    if (!sees_all_dossiers() && $createdByColumn !== null) {
        $collaborateurId = current_collaborateur_id();

        if ($collaborateurId === null) {
            return ['sql' => '1 = 0', 'params' => []];
        }

        return [
            'sql' => $prefix . $createdByColumn . ' = :scope_collaborateur',
            'params' => ['scope_collaborateur' => $collaborateurId],
        ];
    }

    return ['sql' => '', 'params' => []];
}

/**
 * Garde de suppression / mutation sur une ligne metier.
 *
 * A appeler avant tout DELETE ou UPDATE cible par un identifiant venu du
 * formulaire : une requete `DELETE ... WHERE id = :id` sans controle laisse un
 * adherent de cabinet supprimer le dossier d'un concurrent en forgeant l'id.
 */
function require_tenant_row(?PDO $pdo, string $table, int $id, string $column = 'id'): void
{
    $exists = false;

    if ($pdo instanceof PDO && $id > 0 && preg_match('/^[a-z_]+$/', $table) && preg_match('/^[a-z_]+$/', $column)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE `{$column}` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $exists = $stmt->fetch() !== false;
        } catch (PDOException) {
            $exists = false;
        }
    }

    // Ligne absente : 404. Ligne presente mais d'un autre tenant : 403.
    if (!$exists) {
        deny_tenant_access(false);
    }

    if (!assert_tenant_access($pdo, $table, $id, $column)) {
        deny_tenant_access(true);
    }
}

/**
 * Etat d'abonnement du tenant, pour le bandeau d'entete et le blocage.
 *
 * @return array{statut: string, libelle: string, jours_restants: ?int, plan: ?string}
 */
function current_abonnement_state(?PDO $pdo = null): array
{
    $cabinetId = current_cabinet_id();
    $state = ['statut' => 'centre', 'libelle' => 'Compte interne', 'jours_restants' => null, 'plan' => null];

    if ($cabinetId === null) {
        return $state;
    }

    // On n'utilise surtout PAS `global $pdo` ici : le parametre etant
    // nomme `pdo`, l'instruction `global` ecrase sa valeur et la fonction
    // retomberait sur la connexion globale, ignorant le PDO de l'appelant.
    $db = $pdo instanceof PDO ? $pdo : null;
    if (!$db instanceof PDO) {
        $connexionGlobale = $GLOBALS['pdo'] ?? null;
        $db = $connexionGlobale instanceof PDO ? $connexionGlobale : null;
    }

    if (!$db instanceof PDO) {
        return $state;
    }

    try {
        $stmt = $db->prepare('
            SELECT a.statut, a.date_fin, p.nom AS plan_nom, p.code AS plan_code
            FROM abonnements a
            LEFT JOIN plans p ON p.id = a.plan_id
            WHERE a.cabinet_id = :cid
              AND a.statut IN (\'essai\', \'actif\', \'suspendu\')
            ORDER BY a.date_fin DESC
            LIMIT 1
        ');
        $stmt->execute(['cid' => $cabinetId]);
        $row = $stmt->fetch();
    } catch (PDOException) {
        return $state;
    }

    if (!$row) {
        $state['statut'] = 'absent';
        $state['libelle'] = 'Aucun abonnement actif';

        return $state;
    }

    $dateFin = (string) $row['date_fin'];
    $jours = (int) floor((strtotime($dateFin) - strtotime(date('Y-m-d'))) / 86400);

    $state['statut'] = (string) $row['statut'];
    $state['jours_restants'] = $jours;
    $state['plan'] = $row['plan_nom'] ?? null;

    $state['libelle'] = match ($row['statut']) {
        'essai' => 'Essai' . ($row['plan_nom'] !== null ? ' - ' . $row['plan_nom'] : ''),
        'suspendu' => 'Abonnement suspendu',
        'actif' => $jours < 0
            ? 'Abonnement expire'
            : ($jours <= 30 ? 'Expire dans ' . $jours . ' jours' : 'Actif jusqu\'au ' . date('d/m/Y', strtotime($dateFin))),
        default => (string) $row['statut'],
    };

    return $state;
}

/**
 * Blocage des adherents dont l'abonnement n'est plus valide.
 * Le Centre n'est jamais bloque, ni les roles internes.
 */
function require_active_subscription(): void
{
    if (is_centre_user()) {
        return;
    }

    $state = current_abonnement_state();

    $bloque = in_array($state['statut'], ['absent', 'suspendu'], true)
        || ($state['statut'] === 'actif' && ($state['jours_restants'] ?? 1) < 0);

    if ($bloque) {
        set_flash('error', 'Votre abonnement n\'est pas actif : ' . $state['libelle'] . '. Contactez le Centre de Domiciliation.');
        redirect_to('mon_abonnement');
    }
}

/* ---------------------------------------------------------------------------
 * Facturation SaaS : referentiels partages par les ecrans Centre et adherent.
 * ------------------------------------------------------------------------ */

/**
 * Numerotation de facture : FAC-YYYY-NNN, incrementee sur l'annee en cours.
 * `factures.numero` porte un index UNIQUE : on repart du plus grand numero
 * existant pour l'annee, jamais d'un simple COUNT (des numeros peuvent avoir
 * ete supprimes entre-temps).
 */
function next_facture_number(?PDO $pdo, string $prefix = 'FAC'): string
{
    $year = date('Y');
    $fallback = $prefix . '-' . $year . '-001';

    if (!$pdo instanceof PDO) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare('SELECT numero FROM factures WHERE numero LIKE :prefix ORDER BY id DESC LIMIT 1');
        $stmt->execute(['prefix' => $prefix . '-' . $year . '-%']);
        $last = $stmt->fetchColumn();
    } catch (PDOException) {
        return $fallback;
    }

    if (!is_string($last) || $last === '') {
        return $fallback;
    }

    // On ne fait confiance qu'a un numero de la forme exacte attendue.
    if (preg_match('/^' . preg_quote($prefix, '/') . '-' . $year . '-(\d+)$/', $last, $m) !== 1) {
        return $fallback;
    }

    return sprintf('%s-%s-%03d', $prefix, $year, ((int) $m[1]) + 1);
}

/** Cabinets clients, pour les selects de l'administration Centre. */
function fetch_cabinets_options(?PDO $pdo, bool $actifsSeulement = false): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }

    $where = $actifsSeulement ? " WHERE statut = 'actif'" : '';

    try {
        $stmt = $pdo->query('SELECT id, code, nom, statut FROM cabinets' . $where . ' ORDER BY nom ASC');
    } catch (PDOException) {
        return [];
    }

    $options = [];
    foreach ($stmt->fetchAll() as $row) {
        $options[(int) $row['id']] = (string) $row['nom'] . ' (' . (string) $row['code'] . ')';
    }

    return $options;
}

/**
 * Code d'un nouveau cabinet : CAB-NNN, sequence jamais reinitialisee.
 *
 * Contrairement a `next_facture_number()`, on retient le plus grand suffixe
 * trouve et non le dernier code insere (`ORDER BY id DESC`). Une valeur saisie
 * a la main peut casser l'alignement entre l'ordre des id et l'ordre des
 * numeros (TST-004 insere avant TST-003) : la lecture « dernier insere »
 * proposerait alors TST-004, deja employe, et `uq_cabinets_code` refuserait
 * l'enregistrement. Le plus grand suffixe ne peut pas retomber sur un code
 * existant.
 *
 * Seul le prefixe exacte est lu : un code libre comme `CAB-SUD` ou `A7K-3QP` est
 * ignore, il ne doit ni faire boucler la sequence ni etre propose. La
 * comparaison est insensible a la casse pour rester alignee sur la collation
 * `utf8mb4_unicode_ci` : `uq_cabinets_code` et le `LIKE` ci-dessus refusent
 * `cab-001` face a `CAB-001`, notre numerotation doit voir la meme chose.
 */
function next_cabinet_code(?PDO $pdo, string $prefix = 'CAB'): string
{
    if (!$pdo instanceof PDO) {
        return $prefix . '-001';
    }

    try {
        $stmt = $pdo->prepare('SELECT code FROM cabinets WHERE code LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '-%']);
        $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException) {
        return $prefix . '-001';
    }

    $max = 0;
    $motif = '/^' . preg_quote($prefix, '/') . '-(\d+)$/i';
    foreach ($codes as $code) {
        if (is_string($code) && preg_match($motif, trim($code), $m) === 1) {
            $max = max($max, (int) $m[1]);
        }
    }

    // Au-dela de 999, le suffixe s'elargit plutot que de reboucler sur 000.
    return sprintf('%s-%0' . max(3, strlen((string) ($max + 1))) . 'd', $prefix, $max + 1);
}

/** Plans tarifaires, pour les selects d'abonnement. */
function fetch_plans_options(?PDO $pdo, bool $actifsSeulement = true): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }

    $where = $actifsSeulement ? ' WHERE actif = 1' : '';

    try {
        $stmt = $pdo->query('SELECT id, nom, prix_annuel, devise, trial_jours FROM plans' . $where . ' ORDER BY sort_order ASC, nom ASC');
    } catch (PDOException) {
        return [];
    }

    $options = [];
    foreach ($stmt->fetchAll() as $row) {
        $prix = number_format((float) $row['prix_annuel'], 2, ',', ' ');
        $libelle = (string) $row['nom'] . ' - ' . $prix . ' ' . (string) $row['devise'] . '/an';

        if ((int) $row['trial_jours'] > 0) {
            $libelle .= ' (' . (int) $row['trial_jours'] . ' j essai)';
        }

        $options[(int) $row['id']] = $libelle;
    }

    return $options;
}

function abonnement_statut_options(): array
{
    return ['essai', 'actif', 'suspendu', 'resilie'];
}

function abonnement_statut_label(?string $statut): string
{
    return match ($statut) {
        'essai' => 'Essai',
        'actif' => 'Actif',
        'suspendu' => 'Suspendu',
        'resilie' => 'Resilie',
        'expire' => 'Expire',
        default => $statut !== null && $statut !== '' ? ucfirst($statut) : '—',
    };
}

/**
 * Statut affiche d'un abonnement : "expire" est derive, jamais stocke.
 * Un abonnement actif dont la date de fin est passee reste 'actif' en base
 * (l'historique ne doit pas se reecrire tout seul), mais doit etre signale
 * comme expire a l'ecran.
 */
function abonnement_display_statut(array $row): string
{
    $statut = (string) ($row['statut'] ?? '');

    if (in_array($statut, ['actif', 'essai'], true) && !empty($row['date_fin']) && (string) $row['date_fin'] < date('Y-m-d')) {
        return 'expire';
    }

    return $statut;
}

function abonnement_jours_restants(array $row): ?int
{
    if (empty($row['date_fin'])) {
        return null;
    }

    return (int) floor((strtotime((string) $row['date_fin']) - strtotime(date('Y-m-d'))) / 86400);
}

function cabinet_statut_options(): array
{
    return ['actif', 'suspendu', 'ferme'];
}

function cabinet_statut_label(?string $statut): string
{
    return match ($statut) {
        'actif' => 'Actif',
        'suspendu' => 'Suspendu',
        'ferme' => 'Ferme',
        default => $statut !== null && $statut !== '' ? ucfirst($statut) : '—',
    };
}

function facture_statut_options(): array
{
    return ['brouillon', 'emise', 'payee', 'annulee'];
}

function facture_statut_label(?string $statut): string
{
    return match ($statut) {
        'brouillon' => 'Brouillon',
        'emise' => 'Emise',
        'payee' => 'Payee',
        'annulee' => 'Annulee',
        default => $statut !== null && $statut !== '' ? ucfirst($statut) : '—',
    };
}

function paiement_mode_options(): array
{
    return ['virement', 'cheque', 'especes', 'carte', 'prelevement'];
}

function paiement_statut_options(): array
{
    return ['encaisse', 'en_attente', 'rejete', 'rembourse'];
}

/**
 * Statut affiche d'une facture : "en retard" est derive, jamais stocke.
 * Une echeance depassee ne rend pas la facture "en retard" si elle a deja ete
 * payee ou annulee, ni si le statut est encore brouillon.
 */
function facture_display_statut(array $facture): string
{
    $statut = (string) ($facture['statut'] ?? '');

    if ($statut === 'emise' && !empty($facture['date_echeance']) && (string) $facture['date_echeance'] < date('Y-m-d')) {
        return 'en_retard';
    }

    return $statut;
}

function facture_display_statut_label(array $facture): string
{
    $statut = facture_display_statut($facture);

    return $statut === 'en_retard' ? 'En retard' : facture_statut_label($statut);
}

/** Classe de pastille associee au statut affiche d'une facture. */
function facture_statut_tone(array $facture): string
{
    return match (facture_display_statut($facture)) {
        'payee' => 'badge-success',
        'en_retard' => 'badge-danger',
        'emise' => 'badge-warning',
        default => 'badge-secondary',
    };
}

function abonnement_statut_tone(?string $statut): string
{
    return match ($statut) {
        'actif' => 'badge-success',
        'essai' => 'badge-info',
        'suspendu' => 'badge-danger',
        'expire' => 'badge-warning',
        default => 'badge-secondary',
    };
}

function cabinet_statut_tone(?string $statut): string
{
    return match ($statut) {
        'actif' => 'badge-success',
        'suspendu' => 'badge-warning',
        'ferme' => 'badge-secondary',
        default => 'badge-secondary',
    };
}

/**
 * Warning non bloquant d'abonnement, rendu dans l'entete pour les comptes
 * adherents. Retourne null si aucun avertissement n'est necessaire : compte
 * Centre (statut 'centre'), base injoignable, ou abonnement actif confortable.
 */
function abonnement_bandeau(): ?array
{
    $state = current_abonnement_state();

    // 'centre' couvre aussi la base injoignable : on n'alerte jamais sur une panne.
    $statut = (string) ($state['statut'] ?? 'centre');
    if ($statut === 'centre') {
        return null;
    }

    $jours = $state['jours_restants'] === null ? null : (int) $state['jours_restants'];

    if ($statut === 'absent' || $statut === 'suspendu' || ($statut === 'actif' && $jours !== null && $jours < 0)) {
        return [
            'tone' => 'error',
            'message' => (string) $state['libelle'] . ' - contactez le Centre de Domiciliation pour regulariser votre situation.',
        ];
    }

    if ($statut === 'essai' && $jours !== null && $jours <= 15) {
        return [
            'tone' => 'warning',
            'message' => 'Votre essai se termine dans ' . $jours . ' jour(s) - pensez a renouveler votre abonnement.',
        ];
    }

    if ($statut === 'actif' && $jours !== null && $jours <= 30) {
        return [
            'tone' => 'warning',
            'message' => 'Votre abonnement expire dans ' . $jours . ' jour(s) - renouveler avant cette date pour eviter toute interruption.',
        ];
    }

    return null;
}

function get_page_permission(string $page): ?string
{
    $map = [
        'dashboard' => 'dashboard.view',

        'societes' => 'societes.view',
        'creations' => 'societes.view',
        'domiciliations' => 'societes.view',
        'societe' => 'societes.view',
        'societe_suivi' => 'societes.suivi',
        'suivi_pdf' => 'societes.suivi',

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
        'activites_statuts' => 'configuration.view',
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
        'pv_ago' => 'pv_ago.view',

        'cabinets' => 'cabinets.view',
        'plans' => 'plans.view',
        'abonnements' => 'abonnements.view',
        'factures' => 'factures.view',
        'mon_abonnement' => 'mon_abonnement.view',
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
        // `cabinet_id` rattache la trace a son tenant : le Centre peut ainsi
        // relire l'historique d'un cabinet precis, et un adherent ne voit que
        // le sien.
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, cabinet_id, user_nom, action, entity_type, entity_id, entity_label, details, ip_address, created_at)
             VALUES (:uid, :cid, :unom, :act, :etype, :eid, :elabel, :det, :ip, NOW())'
        );
        $stmt->execute([
            'uid'    => $user['id'] ?? null,
            'cid'    => $user['cabinet_id'] ?? null,
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

        // Cloisonnement par cabinet : une notification ciblee par role
        // ("Administrateur Cabinet") est adressee a TOUS les utilisateurs de ce
        // role, donc aussi a ceux des autres cabinets. Sans ce filtre, un
        // adherent du cabinet B lirait les alertes du cabinet A. Les
        // notifications emises par le Centre (cabinet_id NULL) restent
        // visibles de tous.
        $tenant = notification_tenant_clause($params);

        $where = '(is_read = 0) AND (' . implode(' OR ', $orParts) . ')' . $tenant;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications n WHERE $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

/**
 * Clause de cloisonnement des notifications, commune au comptage et au
 * "tout marquer comme lu".
 *
 * @param array<string, int|string|null> $params Complete par reference.
 */
function notification_tenant_clause(array &$params): string
{
    $cabinetId = current_cabinet_id();

    if ($cabinetId === null) {
        return '';
    }

    $params['notif_cid'] = $cabinetId;

    return ' AND (n.cabinet_id = :notif_cid OR n.cabinet_id IS NULL)';
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

        $tenant = notification_tenant_clause($params);

        $where = '(is_read = 0) AND (' . implode(' OR ', $orParts) . ')' . $tenant;
        $stmt = $pdo->prepare("UPDATE notifications n SET is_read = 1, read_at = NOW() WHERE $where");
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
            INSERT INTO user_sessions (user_id, cabinet_id, last_active, current_page, ip_address, user_agent, session_id)
            VALUES (:uid, :cid, NOW(), :page, :ip, :ua, :sid)
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                cabinet_id = VALUES(cabinet_id),
                last_active = VALUES(last_active),
                current_page = VALUES(current_page),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");
        $stmt->execute([
            'uid'  => (int) $user['id'],
            'cid'  => $user['cabinet_id'] ?? null,
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
            SELECT us.user_id, u.nom_complet, u.role_id, u.cabinet_id, r.nom AS role_nom,
                   us.current_page, us.last_active, us.ip_address
            FROM user_sessions us
            JOIN users u ON u.id = us.user_id
            LEFT JOIN roles r ON r.id = u.role_id
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
        'creations' => 'Créations',
        'domiciliations' => 'Domiciliations',
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
        'pv-templates' => 'Modèles de résolutions PV',
    ];
    return $map[$base] ?? $page;
}

/**
 * Build a safe IN clause with named placeholders for PDO.
 * Returns ['sql' => ':ph0,:ph1,...', 'params' => ['ph0' => 1, 'ph1' => 2, ...]]
 */
function build_in_params(array $ids, string $prefix = 'ph'): array
{
    $clean = [];
    foreach ($ids as $raw) {
        $v = (int) $raw;
        if ($v > 0) {
            $clean[] = $v;
        }
    }
    if ($clean === []) {
        return ['sql' => 'NULL', 'params' => []];
    }
    $ph = [];
    $params = [];
    foreach ($clean as $i => $id) {
        $key = "{$prefix}{$i}";
        $ph[] = ":{$key}";
        $params[$key] = $id;
    }
    return ['sql' => implode(',', $ph), 'params' => $params];
}
