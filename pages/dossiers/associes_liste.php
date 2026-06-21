<?php

declare(strict_types=1);

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRecord = $editId > 0 ? fetch_record($pdo ?? null, 'associes', $editId) : null;

$qualitesOptions = [];
if ($editRecord && ($pdo ?? null) instanceof PDO) {
    $qualitesOptions = fetch_reference_options($pdo, 'ref_qualites_associe', 'qualite_associe');
}

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM associes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'associe', (int) $_POST['id']);
        set_flash('success', 'Associe supprime avec succes.');
        redirect_to('associes');
    }

    if ($action === 'update' && $editRecord) {
        $stmt = $pdo->prepare('
            UPDATE associes SET
                associe_civilite = :associe_civilite, associe_nom = :associe_nom, associe_prenom = :associe_prenom, associe_nom_complet = :associe_nom_complet,
                associe_cin = :associe_cin, associe_date_validite_cin = :associe_date_validite_cin,
                associe_date_naissance = :associe_date_naissance, associe_lieu_naissance = :associe_lieu_naissance, associe_nationalite = :associe_nationalite,
                associe_adresse = :associe_adresse, associe_telephone = :associe_telephone, associe_email = :associe_email,
                associe_qualite = :associe_qualite, associe_parts = :associe_parts,
                associe_capital_detenu = :associe_capital_detenu, associe_part_percent = :associe_part_percent, associe_est_gerant = :associe_est_gerant
            WHERE id = :id
        ');
        $stmt->execute([
            'associe_civilite' => field_value($_POST, 'associe_civilite'),
            'associe_nom' => field_value($_POST, 'associe_nom'),
            'associe_prenom' => field_value($_POST, 'associe_prenom'),
            'associe_nom_complet' => field_value($_POST, 'associe_nom_complet'),
            'associe_cin' => field_value($_POST, 'associe_cin'),
            'associe_date_validite_cin' => field_value($_POST, 'associe_date_validite_cin'),
            'associe_date_naissance' => field_value($_POST, 'associe_date_naissance'),
            'associe_lieu_naissance' => field_value($_POST, 'associe_lieu_naissance'),
            'associe_nationalite' => field_value($_POST, 'associe_nationalite'),
            'associe_adresse' => field_value($_POST, 'associe_adresse'),
            'associe_telephone' => field_value($_POST, 'associe_telephone'),
            'associe_email' => field_value($_POST, 'associe_email'),
            'associe_qualite' => field_value($_POST, 'associe_qualite'),
            'associe_parts' => int_value($_POST, 'associe_parts'),
            'associe_capital_detenu' => money_value($_POST, 'associe_capital_detenu'),
            'associe_part_percent' => money_value($_POST, 'associe_part_percent'),
            'associe_est_gerant' => (field_value($_POST, 'associe_est_gerant') === '1') ? 1 : 0,
            'id' => $editId,
        ]);
        log_activity($pdo, 'update', 'associe', $editId, field_value($_POST, 'associe_nom_complet'));
        set_flash('success', 'Associe mis a jour.');
        redirect_to('associes');
    }
}

$query = search_term();
$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$userFilter = '';
$userParams = [];
if (!$isAdmin && $user) {
    $userFilter = ' AND societes.created_by = :user_id';
    $userParams['user_id'] = (int) $user['id'];
}

if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $likeTerm = like_term($query);
        $stmt = $pdo->prepare('
            SELECT associes.*, societes.societe_raison_sociale
            FROM associes
            INNER JOIN societes ON societes.id = associes.societe_id
            WHERE (associes.associe_nom_complet LIKE :term1
               OR societes.societe_raison_sociale LIKE :term2
               OR associes.associe_cin LIKE :term3)
            ' . $userFilter . '
            ORDER BY associes.id DESC
        ');
        $stmt->execute(['term1' => $likeTerm, 'term2' => $likeTerm, 'term3' => $likeTerm] + $userParams);
        $associes = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('
            SELECT associes.*, societes.societe_raison_sociale
            FROM associes
            INNER JOIN societes ON societes.id = associes.societe_id
            WHERE 1=1
            ' . $userFilter . '
            ORDER BY associes.id DESC
        ');
        $stmt->execute($userParams);
        $associes = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $a): array {
            return [
                $a['id'],
                $a['associe_nom_complet'],
                $a['societe_raison_sociale'],
                $a['associe_cin'],
                format_date($a['associe_date_naissance'] ?? null),
                $a['associe_lieu_naissance'],
                $a['associe_nationalite'],
                $a['associe_telephone'],
                $a['associe_email'],
                $a['associe_qualite'],
                $a['associe_parts'],
                (int) $a['associe_est_gerant'] === 1 ? 'Oui' : 'Non',
            ];
        }, $associes);

        $headers = [
            'ID', 'Nom complet', 'Societe', 'CIN', 'Date naissance',
            'Lieu naissance', 'Nationalite', 'Telephone', 'Email',
            'Qualite', 'Parts', 'Gerant',
        ];

        if ($exportType === 'csv') {
            export_csv('associes.csv', $headers, $rows);
        } else {
            export_excel('associes.xlsx', $headers, $rows);
        }
    }
} else {
    $associes = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($associes) ?> enregistrement(s)</span>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="associes">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par nom, societe ou CIN" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('associes')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($editRecord): ?>
            <form method="post" class="stack" style="margin-bottom:1rem">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <h3>Modifier l'associe</h3>
                <div class="form-grid">
                    <label class="field">
                        <span>Civilite</span>
                        <select name="associe_civilite">
                            <option value="">Selectionner</option>
                            <option value="Mr" <?= (string) $editRecord['associe_civilite'] === 'Mr' ? 'selected' : '' ?>>Mr</option>
                            <option value="Mme" <?= (string) $editRecord['associe_civilite'] === 'Mme' ? 'selected' : '' ?>>Mme</option>
                            <option value="Mlle" <?= (string) $editRecord['associe_civilite'] === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nom</span>
                        <input name="associe_nom" value="<?= e((string) $editRecord['associe_nom']) ?>">
                    </label>
                    <label class="field">
                        <span>Prenom</span>
                        <input name="associe_prenom" value="<?= e((string) $editRecord['associe_prenom']) ?>">
                    </label>
                    <label class="field">
                        <span>Nom complet</span>
                        <input name="associe_nom_complet" value="<?= e((string) $editRecord['associe_nom_complet']) ?>">
                    </label>
                    <label class="field">
                        <span>CIN</span>
                        <input name="associe_cin" value="<?= e((string) $editRecord['associe_cin']) ?>">
                    </label>
                    <label class="field">
                        <span>Date validite CIN</span>
                        <input type="date" name="associe_date_validite_cin" value="<?= e((string) $editRecord['associe_date_validite_cin']) ?>">
                    </label>
                    <label class="field">
                        <span>Date naissance</span>
                        <input type="date" name="associe_date_naissance" value="<?= e((string) $editRecord['associe_date_naissance']) ?>">
                    </label>
                    <label class="field">
                        <span>Lieu naissance</span>
                        <input name="associe_lieu_naissance" value="<?= e((string) $editRecord['associe_lieu_naissance']) ?>">
                    </label>
                    <label class="field">
                        <span>Nationalite</span>
                        <input name="associe_nationalite" value="<?= e((string) $editRecord['associe_nationalite']) ?>">
                    </label>
                    <label class="field">
                        <span>Telephone</span>
                        <input name="associe_telephone" value="<?= e((string) $editRecord['associe_telephone']) ?>">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="associe_email" value="<?= e((string) $editRecord['associe_email']) ?>">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea name="associe_adresse"><?= e((string) $editRecord['associe_adresse']) ?></textarea>
                    </label>
                    <label class="field">
                        <span>Qualite associe</span>
                        <select name="associe_qualite">
                            <option value="">Selectionner</option>
                            <?php foreach ($qualitesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $editRecord['associe_qualite'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Parts</span>
                        <input type="number" name="associe_parts" value="<?= e((string) $editRecord['associe_parts']) ?>">
                    </label>
                    <label class="field">
                        <span>Capital detenu (DH)</span>
                        <input type="number" step="0.01" name="associe_capital_detenu" value="<?= e((string) $editRecord['associe_capital_detenu']) ?>">
                    </label>
                    <label class="field">
                        <span>% Capital social</span>
                        <input type="number" step="0.01" name="associe_part_percent" value="<?= e((string) $editRecord['associe_part_percent']) ?>">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select name="associe_est_gerant">
                            <option value="0" <?= (string) $editRecord['associe_est_gerant'] === '0' ? 'selected' : '' ?>>Non</option>
                            <option value="1" <?= (string) $editRecord['associe_est_gerant'] === '1' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </label>
                </div>
                <div class="table-actions">
                    <a class="btn btn-secondary" href="<?= e(app_url('associes')) ?>">Annuler</a>
                    <button class="btn btn-next" type="submit">Enregistrer</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if (!$associes): ?>
            <p class="table-empty">Aucun associe pour le moment.</p>
        <?php else: ?>
            <div class="table-actions" style="margin-bottom:8px">
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
            </div>
            <div class="table-scroll">
            <table data-col-toggle data-sortable>
                <thead>
                <tr>
                    <th data-col="nom-complet">Nom complet</th>
                    <th data-col="societe">Societe</th>
                    <th data-col="cin">CIN</th>
                    <th data-col="date-naiss">Date naissance</th>
                    <th data-col="lieu-naiss">Lieu naissance</th>
                    <th data-col="nationalite">Nationalite</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="email">Email</th>
                    <th data-col="qualite">Qualite</th>
                    <th data-col="parts">Parts</th>
                    <th data-col="gerant">Gerant</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr>
                        <td><a href="<?= e(app_url('associe', ['id' => (int) $associe['id']])) ?>" class="table-link"><?= e($associe['associe_nom_complet']) ?></a></td>
                        <td><?= e($associe['societe_raison_sociale']) ?></td>
                        <td><?= e($associe['associe_cin'] ?? '-') ?></td>
                        <td><?= e(format_date($associe['associe_date_naissance'] ?? null)) ?></td>
                        <td><?= e($associe['associe_lieu_naissance'] ?? '-') ?></td>
                        <td><?= e($associe['associe_nationalite'] ?? '-') ?></td>
                        <td><?= e($associe['associe_telephone'] ?? '-') ?></td>
                        <td><?= e($associe['associe_email'] ?? '-') ?></td>
                        <td><?= e($associe['associe_qualite'] ?? '-') ?></td>
                        <td><?= $associe['associe_parts'] !== null ? e((string) $associe['associe_parts']) : '-' ?></td>
                        <td><?= (int) $associe['associe_est_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $associe['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $associe['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon info" href="<?= e(app_url('associe', ['id' => (int) $associe['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $associe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet associe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <div class="table-actions" style="margin-top:12px">
                <a class="btn btn-info" href="<?= e(app_url('associes', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('associes', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('associes.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="associes"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>

