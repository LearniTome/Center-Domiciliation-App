<?php

declare(strict_types=1);

/**
 * Administration SaaS - Cabinets clients.
 *
 * Ecran prealable a la facturation : un abonnement se rattache a un cabinet.
 * Seuls les comptes Centre (cabinet_id IS NULL) possessing cabinets.* accedent
 * a cette page, la garde est faite par get_page_permission() / require_page_access().
 */

$query = search_term();
$canCreate = has_permission('cabinets.create');
$canEdit = has_permission('cabinets.edit');
$canDelete = has_permission('cabinets.delete');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$formOpen = isset($_GET['action']) && $_GET['action'] === 'new';

$formData = [
    'code' => '', 'nom' => '', 'raison_sociale' => '', 'email' => '', 'telephone' => '',
    'adresse' => '', 'ville' => '', 'ice' => '', 'rc' => '', 'statut' => 'actif', 'notes' => '',
];
$formError = null;

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM cabinets WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Cabinet introuvable.');
        redirect_to('cabinets');
    }
}

// Creation : on propose le prochain code de sequence, que l'utilisateur peut
// remplacer. Place AVANT le handler POST pour qu'un code deja saisi, ou une
// erreur de validation, ne soit jamais ecrase par une nouvelle proposition
// (`$formData` est fusionne avec $_POST juste apres, en cas d'erreur).
if ($formOpen && $editId === 0 && (string) $formData['code'] === '') {
    $formData['code'] = next_cabinet_code($db);
}

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        if (!$canDelete) {
            require_permission('cabinets.delete');
        }

        $targetId = (int) ($_POST['id'] ?? 0);

        // MySQL refuserait la suppression via les cles etrangeres
        // fk_users_cabinet / fk_abonnements_cabinet / fk_factures_cabinet /
        // fk_paiements_cabinet. On verifie en amont pour donner un message
        // exploitable au lieu d'une exception SQL.
        $deps = [];
        foreach (['users' => 'utilisateurs', 'abonnements' => 'abonnements', 'factures' => 'factures', 'paiements' => 'paiements'] as $table => $label) {
            $check = $db->prepare("SELECT COUNT(*) FROM `$table` WHERE cabinet_id = :cid");
            $check->execute(['cid' => $targetId]);
            $count = (int) $check->fetchColumn();
            if ($count > 0) {
                $deps[] = $count . ' ' . $label;
            }
        }

        if ($deps !== []) {
            set_flash('error', 'Suppression impossible : ce cabinet est encore rattache a ' . implode(', ', $deps) . '. Supprimez ou reassignez ces elements d\'abord.');
        } else {
            $stmt = $db->prepare('DELETE FROM cabinets WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'cabinet', $targetId);
            set_flash('success', 'Cabinet supprime.');
        }

        redirect_to('cabinets');
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId > 0 && !$canEdit) {
            require_permission('cabinets.edit');
        }
        if ($targetId === 0 && !$canCreate) {
            require_permission('cabinets.create');
        }

        $code = strtoupper(field_value($_POST, 'code'));
        $nom = field_value($_POST, 'nom');
        $email = field_value($_POST, 'email');
        $statut = field_value($_POST, 'statut', 'actif');

        if (!in_array($statut, cabinet_statut_options(), true)) {
            $statut = 'actif';
        }

        $errors = [];
        if ($code === '') {
            $errors[] = 'Le code du cabinet est obligatoire.';
        }
        if ($nom === '') {
            $errors[] = 'Le nom du cabinet est obligatoire.';
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'L\'adresse email est invalide.';
        }

        if ($code !== '') {
            $dup = $db->prepare('SELECT id FROM cabinets WHERE code = :code AND id <> :id');
            $dup->execute(['code' => $code, 'id' => $targetId]);
            if ($dup->fetch()) {
                $errors[] = 'Ce code de cabinet est deja utilise.';
            }
        }

        if ($errors !== []) {
            $formError = implode(' ', $errors);
            $formOpen = true;
            $formData = array_merge($formData, $_POST);
        } else {
            $payload = [
                'code' => $code,
                'nom' => $nom,
                'raison_sociale' => field_value($_POST, 'raison_sociale'),
                'email' => $email,
                'telephone' => field_value($_POST, 'telephone'),
                'adresse' => field_value($_POST, 'adresse'),
                'ville' => field_value($_POST, 'ville'),
                'ice' => field_value($_POST, 'ice'),
                'rc' => field_value($_POST, 'rc'),
                'statut' => $statut,
                'notes' => field_value($_POST, 'notes'),
            ];

            if ($targetId > 0) {
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE cabinets SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'cabinet', $targetId, $nom);
                set_flash('success', 'Cabinet « ' . $nom . ' » mis a jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO cabinets (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $newId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'cabinet', $newId, $nom);
                set_flash('success', 'Cabinet « ' . $nom . ' » cree.');
            }

            redirect_to('cabinets');
        }
    }
}

$cabinets = [];
if ($db) {
    if ($query !== '') {
        $like = like_term($query);
        $stmt = $db->prepare('
            SELECT c.*,
                   (SELECT COUNT(*) FROM abonnements a WHERE a.cabinet_id = c.id) AS nb_abonnements,
                   (SELECT COUNT(*) FROM users u WHERE u.cabinet_id = c.id) AS nb_utilisateurs
            FROM cabinets c
            WHERE c.code LIKE :t1 OR c.nom LIKE :t2 OR c.raison_sociale LIKE :t3
               OR c.email LIKE :t4 OR c.ville LIKE :t5 OR c.ice LIKE :t6 OR c.rc LIKE :t7
            ORDER BY c.nom ASC
        ');
        $stmt->execute([
            't1' => $like, 't2' => $like, 't3' => $like,
            't4' => $like, 't5' => $like, 't6' => $like, 't7' => $like,
        ]);
        $cabinets = $stmt->fetchAll();
    } else {
        $stmt = $db->query('
            SELECT c.*,
                   (SELECT COUNT(*) FROM abonnements a WHERE a.cabinet_id = c.id) AS nb_abonnements,
                   (SELECT COUNT(*) FROM users u WHERE u.cabinet_id = c.id) AS nb_utilisateurs
            FROM cabinets c
            ORDER BY c.nom ASC
        ');
        $cabinets = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static fn (array $r): array => [
            $r['code'],
            $r['nom'],
            $r['raison_sociale'] ?? '-',
            $r['email'] ?? '-',
            $r['telephone'] ?? '-',
            $r['ville'] ?? '-',
            $r['ice'] ?? '-',
            $r['rc'] ?? '-',
            cabinet_statut_label((string) $r['statut']),
            $r['nb_utilisateurs'] ?? 0,
            $r['nb_abonnements'] ?? 0,
        ], $cabinets);

        $headers = ['Code', 'Nom', 'Raison sociale', 'Email', 'Telephone', 'Ville', 'ICE', 'RC', 'Statut', 'Utilisateurs', 'Abonnements'];

        if ($exportType === 'csv') {
            export_csv('cabinets.csv', $headers, $rows);
        } else {
            export_excel('cabinets.xlsx', $headers, $rows);
        }
    }
}

$statutOptions = [];
foreach (cabinet_statut_options() as $s) {
    $statutOptions[$s] = cabinet_statut_label($s);
}
?>
<section class="stack">
    <?php if ($formOpen && ($canCreate || $canEdit)): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0"><?= $editId > 0 ? 'Modifier le cabinet' : 'Nouveau cabinet' ?></h2>
                <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Fermer</a>
            </div>

            <?php if ($formError !== null): ?>
                <div class="flash flash-error" style="margin-bottom:12px"><?= e($formError) ?></div>
            <?php endif; ?>

            <form method="post" class="sub-form-grid">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editId > 0): ?>
                    <input type="hidden" name="id" value="<?= e((string) $editId) ?>">
                <?php endif; ?>

                <label class="field">
                    <span>Code *</span>
                    <input type="text" name="code" required maxlength="40" value="<?= e((string) ($formData['code'] ?? '')) ?>" placeholder="CAB-001">
                    <?php if ($editId === 0): ?>
                        <span class="help-text">Code proposé automatiquement, modifiable.</span>
                    <?php endif; ?>
                </label>
                <label class="field">
                    <span>Nom *</span>
                    <input type="text" name="nom" required maxlength="150" value="<?= e((string) ($formData['nom'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Raison sociale</span>
                    <input type="text" name="raison_sociale" maxlength="190" value="<?= e((string) ($formData['raison_sociale'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="statut">
                        <?php foreach ($statutOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>"<?= (string) ($formData['statut'] ?? 'actif') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" maxlength="190" value="<?= e((string) ($formData['email'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Telephone</span>
                    <input type="text" name="telephone" maxlength="40" value="<?= e((string) ($formData['telephone'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>ICE</span>
                    <input type="text" name="ice" maxlength="40" value="<?= e((string) ($formData['ice'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>RC</span>
                    <input type="text" name="rc" maxlength="60" value="<?= e((string) ($formData['rc'] ?? '')) ?>">
                </label>
                <label class="field full">
                    <span>Adresse</span>
                    <input type="text" name="adresse" maxlength="255" value="<?= e((string) ($formData['adresse'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Ville</span>
                    <input type="text" name="ville" maxlength="120" value="<?= e((string) ($formData['ville'] ?? '')) ?>">
                </label>
                <label class="field full">
                    <span>Notes</span>
                    <textarea name="notes" rows="2"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
                </label>

                <div class="sub-form-actions field full">
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">save</span> Enregistrer</button>
                    <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($cabinets) ?> cabinet(s)</span>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('cabinets', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-info" href="<?= e(app_url('cabinets', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="cabinets">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par code, nom, ville, ICE ou RC" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$cabinets): ?>
            <p class="table-empty">
                <?= $query !== '' ? 'Aucun cabinet ne correspond a cette recherche.' : 'Aucun cabinet enregistre. Les abonnements se rattachent a un cabinet : creez d\'abord votre premier client.' ?>
            </p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="cabinets">
                    <thead>
                        <tr>
                            <th data-col="code">Code</th>
                            <th data-col="nom">Nom</th>
                            <th data-col="raison">Raison sociale</th>
                            <th data-col="contact">Contact</th>
                            <th data-col="ville">Ville</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="users">Utilisateurs</th>
                            <th data-col="abos">Abonnements</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cabinets as $cab): ?>
                        <tr>
                            <td><strong><?= e((string) $cab['code']) ?></strong></td>
                            <td><?= e((string) $cab['nom']) ?></td>
                            <td><?= e((string) ($cab['raison_sociale'] ?? '-')) ?></td>
                            <td>
                                <?= e((string) ($cab['email'] ?? '-')) ?>
                                <?php if (!empty($cab['telephone'])): ?>
                                    <br><small><?= e((string) $cab['telephone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($cab['ville'] ?? '-')) ?></td>
                            <td><span class="badge <?= e(cabinet_statut_tone((string) $cab['statut'])) ?>"><?= e(cabinet_statut_label((string) $cab['statut'])) ?></span></td>
                            <td><?= (int) ($cab['nb_utilisateurs'] ?? 0) ?></td>
                            <td><?= (int) ($cab['nb_abonnements'] ?? 0) ?></td>
                            <td class="table-actions">
                                <?php if ($canEdit): ?>
                                    <a class="btn-icon info" href="<?= e(app_url('cabinets', ['edit' => (int) $cab['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $cab['id']) ?>">
                                        <button class="btn-icon danger" type="submit" data-confirm="Supprimer le cabinet « <?= e((string) $cab['nom']) ?> » ?" data-confirm-title="Supprimer le cabinet" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>
