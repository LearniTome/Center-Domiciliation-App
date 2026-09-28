<?php

declare(strict_types=1);

/**
 * Administration SaaS - Plans tarifaires.
 *
 * Un seul droit d'ecriture (plans.edit) couvre la creation, la modification et
 * la suppression : le referentiel ne distingue pas create/edit/delete.
 */

$query = search_term();
$canEdit = has_permission('plans.edit');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$formOpen = isset($_GET['action']) && $_GET['action'] === 'new';

$formData = [
    'code' => '', 'nom' => '', 'description' => '', 'prix_annuel' => '0', 'devise' => 'MAD',
    'max_utilisateurs' => '', 'max_societes' => '', 'max_dossiers' => '',
    'trial_jours' => '0', 'auto_renew' => '1', 'actif' => '1', 'sort_order' => '0',
];
$formError = null;

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM plans WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Plan introuvable.');
        redirect_to('plans');
    }
}

if (is_post() && $db) {
    verify_csrf();
    require_permission('plans.edit');
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $targetId = (int) ($_POST['id'] ?? 0);

        // fk_abonnements_plan empeche MySQL de supprimer un plan reference :
        // on le signale proprement plutot que de laisser remonter l'exception.
        $check = $db->prepare('SELECT COUNT(*) FROM abonnements WHERE plan_id = :pid');
        $check->execute(['pid' => $targetId]);
        $used = (int) $check->fetchColumn();

        if ($used > 0) {
            set_flash('error', 'Suppression impossible : ce plan est utilise par ' . $used . ' abonnement(s). Desactivez-le plutot.');
        } else {
            $stmt = $db->prepare('DELETE FROM plans WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'plan', $targetId);
            set_flash('success', 'Plan supprime.');
        }

        redirect_to('plans');
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        $code = strtoupper(field_value($_POST, 'code'));
        $nom = field_value($_POST, 'nom');
        $devise = strtoupper(field_value($_POST, 'devise', 'MAD'));

        $errors = [];
        if ($code === '') {
            $errors[] = 'Le code du plan est obligatoire.';
        } elseif (preg_match('/^[A-Z0-9_-]{2,40}$/', $code) !== 1) {
            $errors[] = 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.';
        }
        if ($nom === '') {
            $errors[] = 'Le nom du plan est obligatoire.';
        }
        if (strlen($devise) !== 3) {
            $errors[] = 'La devise doit contenir 3 lettres (ex. MAD, EUR).';
        }

        $prix = money_value($_POST, 'prix_annuel');
        if ($prix === null) {
            $prix = 0.0;
        }
        if ($prix < 0) {
            $errors[] = 'Le prix annuel ne peut pas etre negatif.';
        }

        if ($code !== '') {
            $dup = $db->prepare('SELECT id FROM plans WHERE code = :code AND id <> :id');
            $dup->execute(['code' => $code, 'id' => $targetId]);
            if ($dup->fetch()) {
                $errors[] = 'Ce code de plan est deja utilise.';
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
                'description' => field_value($_POST, 'description'),
                'prix_annuel' => number_format($prix, 2, '.', ''),
                'devise' => $devise,
                'max_utilisateurs' => int_value($_POST, 'max_utilisateurs'),
                'max_societes' => int_value($_POST, 'max_societes'),
                'max_dossiers' => int_value($_POST, 'max_dossiers'),
                'trial_jours' => max(0, (int) (int_value($_POST, 'trial_jours') ?? 0)),
                'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0,
                'actif' => isset($_POST['actif']) ? 1 : 0,
                'sort_order' => (int) (int_value($_POST, 'sort_order') ?? 0),
            ];

            if ($targetId > 0) {
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE plans SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'plan', $targetId, $nom);
                set_flash('success', 'Plan « ' . $nom . ' » mis a jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO plans (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $newId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'plan', $newId, $nom);
                set_flash('success', 'Plan « ' . $nom . ' » cree.');
            }

            redirect_to('plans');
        }
    }
}

$plans = [];
if ($db) {
    if ($query !== '') {
        $like = like_term($query);
        $stmt = $db->prepare('
            SELECT p.*, (SELECT COUNT(*) FROM abonnements a WHERE a.plan_id = p.id) AS nb_abonnements
            FROM plans p
            WHERE p.code LIKE :t1 OR p.nom LIKE :t2 OR p.description LIKE :t3
            ORDER BY p.sort_order ASC, p.nom ASC
        ');
        $stmt->execute(['t1' => $like, 't2' => $like, 't3' => $like]);
        $plans = $stmt->fetchAll();
    } else {
        $stmt = $db->query('
            SELECT p.*, (SELECT COUNT(*) FROM abonnements a WHERE a.plan_id = p.id) AS nb_abonnements
            FROM plans p
            ORDER BY p.sort_order ASC, p.nom ASC
        ');
        $plans = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static fn (array $r): array => [
            $r['code'],
            $r['nom'],
            number_format((float) $r['prix_annuel'], 2, ',', ' ') . ' ' . $r['devise'],
            $r['max_utilisateurs'] ?? 'illimite',
            $r['max_societes'] ?? 'illimite',
            $r['max_dossiers'] ?? 'illimite',
            $r['trial_jours'] ?? 0,
            (int) $r['auto_renew'] === 1 ? 'oui' : 'non',
            (int) $r['actif'] === 1 ? 'actif' : 'inactif',
            $r['nb_abonnements'] ?? 0,
        ], $plans);

        $headers = ['Code', 'Nom', 'Prix annuel', 'Max utilisateurs', 'Max societes', 'Max dossiers', 'Jours essai', 'Renouvellement auto', 'Etat', 'Abonnements'];

        if ($exportType === 'csv') {
            export_csv('plans.csv', $headers, $rows);
        } else {
            export_excel('plans.xlsx', $headers, $rows);
        }
    }
}

$quotaValue = static function ($raw): string {
    return $raw === null || $raw === '' ? 'illimite' : (string) (int) $raw;
};
?>
<section class="stack">
    <?php if ($formOpen && $canEdit): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0"><?= $editId > 0 ? 'Modifier le plan' : 'Nouveau plan' ?></h2>
                <a class="btn btn-cancel" href="<?= e(app_url('plans')) ?>"><span class="material-symbols-outlined">close</span> Fermer</a>
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
                    <input type="text" name="code" required maxlength="40" value="<?= e((string) ($formData['code'] ?? '')) ?>" placeholder="PRO">
                </label>
                <label class="field">
                    <span>Nom *</span>
                    <input type="text" name="nom" required maxlength="120" value="<?= e((string) ($formData['nom'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Prix annuel</span>
                    <input type="text" name="prix_annuel" inputmode="decimal" value="<?= e((string) ($formData['prix_annuel'] ?? '0')) ?>">
                </label>
                <label class="field">
                    <span>Devise</span>
                    <input type="text" name="devise" maxlength="3" value="<?= e((string) ($formData['devise'] ?? 'MAD')) ?>">
                </label>
                <label class="field">
                    <span>Max utilisateurs</span>
                    <input type="number" name="max_utilisateurs" min="0" placeholder="illimite" value="<?= e($quotaValue($formData['max_utilisateurs'] ?? null)) === 'illimite' ? '' : e($quotaValue($formData['max_utilisateurs'] ?? null)) ?>">
                </label>
                <label class="field">
                    <span>Max societes</span>
                    <input type="number" name="max_societes" min="0" placeholder="illimite" value="<?= e($quotaValue($formData['max_societes'] ?? null)) === 'illimite' ? '' : e($quotaValue($formData['max_societes'] ?? null)) ?>">
                </label>
                <label class="field">
                    <span>Max dossiers</span>
                    <input type="number" name="max_dossiers" min="0" placeholder="illimite" value="<?= e($quotaValue($formData['max_dossiers'] ?? null)) === 'illimite' ? '' : e($quotaValue($formData['max_dossiers'] ?? null)) ?>">
                </label>
                <label class="field">
                    <span>Jours d'essai</span>
                    <input type="number" name="trial_jours" min="0" value="<?= e((string) ($formData['trial_jours'] ?? '0')) ?>">
                </label>
                <label class="field">
                    <span>Ordre d'affichage</span>
                    <input type="number" name="sort_order" value="<?= e((string) ($formData['sort_order'] ?? '0')) ?>">
                </label>
                <div class="field">
                    <span>Options</span>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;padding-top:6px">
                        <label class="sub-form-check"><input type="checkbox" name="actif" value="1"<?= (int) ($formData['actif'] ?? 1) === 1 ? ' checked' : '' ?>> Actif</label>
                        <label class="sub-form-check"><input type="checkbox" name="auto_renew" value="1"<?= (int) ($formData['auto_renew'] ?? 1) === 1 ? ' checked' : '' ?>> Renouvellement automatique</label>
                    </div>
                </div>
                <label class="field full">
                    <span>Description</span>
                    <input type="text" name="description" maxlength="255" value="<?= e((string) ($formData['description'] ?? '')) ?>">
                </label>

                <div class="sub-form-actions field full">
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">save</span> Enregistrer</button>
                    <a class="btn btn-cancel" href="<?= e(app_url('plans')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($plans) ?> plan(s)</span>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('plans', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-info" href="<?= e(app_url('plans', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="plans">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher un plan par code, nom ou description" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('plans')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$plans): ?>
            <p class="table-empty"><?= $query !== '' ? 'Aucun plan ne correspond a cette recherche.' : 'Aucun plan tarifaire. Creez-en un pour pouvoir souscrire un abonnement.' ?></p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="plans">
                    <thead>
                        <tr>
                            <th data-col="code">Code</th>
                            <th data-col="nom">Nom</th>
                            <th data-col="prix">Prix annuel</th>
                            <th data-col="quotas">Quotas</th>
                            <th data-col="essai">Essai</th>
                            <th data-col="renew">Renouvellement</th>
                            <th data-col="abos">Abonnements</th>
                            <th data-col="actif">Etat</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><strong><?= e((string) $plan['code']) ?></strong></td>
                            <td>
                                <?= e((string) $plan['nom']) ?>
                                <?php if (!empty($plan['description'])): ?>
                                    <br><small><?= e((string) $plan['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e(number_format((float) $plan['prix_annuel'], 2, ',', ' ')) ?> <?= e((string) $plan['devise']) ?></td>
                            <td>
                                <?= e($quotaValue($plan['max_utilisateurs'])) ?> util. /
                                <?= e($quotaValue($plan['max_societes'])) ?> soc. /
                                <?= e($quotaValue($plan['max_dossiers'])) ?> dossiers
                            </td>
                            <td><?= (int) ($plan['trial_jours'] ?? 0) ?> j</td>
                            <td><?= (int) $plan['auto_renew'] === 1 ? 'Automatique' : 'Manuel' ?></td>
                            <td><?= (int) ($plan['nb_abonnements'] ?? 0) ?></td>
                            <td>
                                <span class="badge <?= (int) $plan['actif'] === 1 ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= (int) $plan['actif'] === 1 ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <?php if ($canEdit): ?>
                                    <a class="btn-icon info" href="<?= e(app_url('plans', ['edit' => (int) $plan['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $plan['id']) ?>">
                                        <button class="btn-icon danger" type="submit" data-confirm="Supprimer le plan « <?= e((string) $plan['nom']) ?> » ?" data-confirm-title="Supprimer le plan" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
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
