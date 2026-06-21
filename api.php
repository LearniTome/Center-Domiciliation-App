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
require __DIR__ . '/includes/base_donnees.php';

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
    $data['created_by'] = (int) $user['id'];
    if (!isset($data['societe_source'])) {
        $data['societe_source'] = 'creation';
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
        log_activity($pdo, 'create', $table, $newId, $user['id'] ?? 0);
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
