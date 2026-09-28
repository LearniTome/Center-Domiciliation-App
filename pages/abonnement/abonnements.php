<?php

declare(strict_types=1);

/**
 * Administration SaaS - Abonnements des cabinets.
 *
 * Vue transverse : un Super Admin voit tous les cabinets. Aucun filtre tenant
 * ici, volontairement -- c'est la seule page ou le cloisonnement cabinet_id
 * ne doit pas s'appliquer. Les ecrans adherents cloisonnes sont dans
 * mon_abonnement.php.
 */

$query = search_term();
$canCreate = has_permission('abonnements.create');
$canEdit = has_permission('abonnements.edit');
$canDelete = has_permission('abonnements.delete');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$cabinetOptions = fetch_cabinets_options($db);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$formOpen = isset($_GET['action']) && $_GET['action'] === 'new';
// En creation on ne propose que les plans actifs ; en edition on charge aussi
// les plans inactifs, sinon un abonnement rattache a un plan desactive verrait
// "Sur mesure (sans plan)" dans le select et perdrait sa formule et son prix
// negocie a la premiere sauvegarde, sans lever la moindre erreur.
$planOptions = fetch_plans_options($db, $editId === 0);

$formData = [
    'cabinet_id' => '', 'plan_id' => '', 'date_debut' => date('Y-m-d'), 'date_fin' => '',
    'statut' => 'actif', 'prix_annuel_negocie' => '', 'devise' => 'MAD',
    'auto_renew' => '1', 'notes' => '',
];
$formError = null;

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM abonnements WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Abonnement introuvable.');
        redirect_to('abonnements');
    }
}

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        if (!$canDelete) {
            require_permission('abonnements.delete');
        }

        $targetId = (int) ($_POST['id'] ?? 0);

        // fk_factures_abonnement : on refuse proprement si l'abonnement a deja
        // produit des factures, plutot que de laisser une exception SQL monter.
        $check = $db->prepare('SELECT COUNT(*) FROM factures WHERE abonnement_id = :aid');
        $check->execute(['aid' => $targetId]);
        $linked = (int) $check->fetchColumn();

        if ($linked > 0) {
            set_flash('error', 'Suppression impossible : ' . $linked . ' facture(s) sont rattachees a cet abonnement. Marquez-le resilie plutot.');
        } else {
            $stmt = $db->prepare('DELETE FROM abonnements WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'abonnement', $targetId);
            set_flash('success', 'Abonnement supprime.');
        }

        redirect_to('abonnements');
    }

    if ($action === 'renew') {
        if (!$canEdit) {
            require_permission('abonnements.edit');
        }

        $targetId = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT id, date_fin, cabinet_id FROM abonnements WHERE id = :id');
        $stmt->execute(['id' => $targetId]);
        $row = $stmt->fetch();

        if (!$row) {
            set_flash('error', 'Abonnement introuvable.');
            redirect_to('abonnements');
        }

        // On prolonge d'un an a partir de la fin en cours si elle est encore
        // devant nous, sinon a partir d'aujourd'hui : sans cela, renouveler un
        // abonnement deja echu le laisserait immediatement a nouveau expire.
        $base = (string) $row['date_fin'] > date('Y-m-d') ? (string) $row['date_fin'] : date('Y-m-d');
        $newEnd = date('Y-m-d', strtotime($base . ' +1 year'));

        $upd = $db->prepare("UPDATE abonnements SET date_fin = :fin, statut = 'actif' WHERE id = :id");
        $upd->execute(['fin' => $newEnd, 'id' => $targetId]);
        log_activity($db, 'update', 'abonnement', $targetId, 'Renouvellement jusqu au ' . $newEnd);
        set_flash('success', 'Abonnement renouvele jusqu\'au ' . date('d/m/Y', strtotime($newEnd)) . '.');

        redirect_to('abonnements');
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId > 0 && !$canEdit) {
            require_permission('abonnements.edit');
        }
        if ($targetId === 0 && !$canCreate) {
            require_permission('abonnements.create');
        }

        $cabinetId = (int) (int_value($_POST, 'cabinet_id') ?? 0);
        $planId = int_value($_POST, 'plan_id');
        $dateDebut = field_value($_POST, 'date_debut');
        $dateFin = field_value($_POST, 'date_fin');
        $statut = field_value($_POST, 'statut', 'actif');

        if (!in_array($statut, abonnement_statut_options(), true)) {
            $statut = 'actif';
        }

        $errors = [];

        if ($cabinetId <= 0) {
            $errors[] = 'Selectionnez un cabinet.';
        } else {
            $exists = $db->prepare('SELECT id FROM cabinets WHERE id = :id');
            $exists->execute(['id' => $cabinetId]);
            if (!$exists->fetch()) {
                // Un cabinet forgee ne doit jamais atteindre la table.
                $errors[] = 'Ce cabinet est introuvable.';
            }
        }

        $plan = null;
            if ($planId !== null && $planId > 0) {
                $pstmt = $db->prepare('SELECT id, prix_annuel, devise, trial_jours, actif FROM plans WHERE id = :id');
                $pstmt->execute(['id' => $planId]);
                $plan = $pstmt->fetch();
                if (!$plan) {
                    $errors[] = 'Ce plan tarifaire est introuvable.';
                }
                // On permet d'editer un abonnement dont le plan est devenu inactif
                // (retrocompatibilite historique), mais on exige qu'il existe.
            }

        if ($dateDebut === '' || strtotime($dateDebut) === false) {
            $errors[] = 'La date de debut est obligatoire.';
        }
        if ($dateFin === '' || strtotime($dateFin) === false) {
            // Essai : si la fin n'est pas saisie, on la derive de la duree du plan.
            if ($statut === 'essai' && $dateDebut !== '' && strtotime($dateDebut) !== false && $plan !== null && (int) $plan['trial_jours'] > 0) {
                $dateFin = date('Y-m-d', strtotime($dateDebut . ' +' . (int) $plan['trial_jours'] . ' days'));
            }
            if ($dateFin === '' || strtotime($dateFin) === false) {
                $errors[] = 'La date de fin est obligatoire.';
            }
        }

        if ($errors !== []) {
            $formError = implode(' ', $errors);
            $formOpen = true;
            $formData = array_merge($formData, $_POST);
        } else {
            $prix = money_value($_POST, 'prix_annuel_negocie');
            if ($prix === null) {
                $prix = $plan !== null ? (float) $plan['prix_annuel'] : 0.0;
            }

            $devise = strtoupper(field_value($_POST, 'devise', ''));
            if ($devise === '') {
                $devise = $plan !== null ? (string) $plan['devise'] : 'MAD';
            }

            $payload = [
                'cabinet_id' => $cabinetId,
                'plan_id' => $planId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'statut' => $statut,
                'prix_annuel_negocie' => number_format($prix, 2, '.', ''),
                'devise' => $devise,
                'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0,
                'notes' => field_value($_POST, 'notes'),
            ];

            $insertedId = 0;

            if ($targetId > 0) {
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE abonnements SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'abonnement', $targetId);
                set_flash('success', 'Abonnement mis a jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO abonnements (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $insertedId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'abonnement', $insertedId);
                set_flash('success', 'Abonnement enregistre.');
            }

            // Avertissement non bloquant : plusieurs abonnements en cours sur le
            // meme cabinet rendent current_abonnement_state() ambigu.
            if ($targetId === 0 && $statut !== 'resilie') {
                $check = $db->prepare("SELECT COUNT(*) FROM abonnements WHERE cabinet_id = :cid AND statut <> 'resilie' AND id <> :id");
                $check->execute(['cid' => $cabinetId, 'id' => $insertedId]);
                $others = (int) $check->fetchColumn();
                if ($others > 0) {
                    set_flash('error', 'Ce cabinet a deja ' . $others . ' autre(s) abonnement(s) non resilie(s). Le bandeau adherents retiendra celui dont la date de fin est la plus eloignee.');
                }
            }

            redirect_to('abonnements');
        }
    }
}

$abonnements = [];
if ($db) {
    $baseSql = 'SELECT a.*, c.nom AS cabinet_nom, c.code AS cabinet_code, p.nom AS plan_nom, p.code AS plan_code
                FROM abonnements a
                LEFT JOIN cabinets c ON c.id = a.cabinet_id
                LEFT JOIN plans p ON p.id = a.plan_id';

    if ($query !== '') {
        $like = like_term($query);
        $stmt = $db->prepare($baseSql . '
            WHERE c.nom LIKE :t1 OR c.code LIKE :t2 OR p.nom LIKE :t3 OR a.statut LIKE :t4
            ORDER BY a.date_fin DESC, a.id DESC
        ');
        $stmt->execute(['t1' => $like, 't2' => $like, 't3' => $like, 't4' => $like]);
        $abonnements = $stmt->fetchAll();
    } else {
        $stmt = $db->prepare($baseSql . ' ORDER BY a.date_fin DESC, a.id DESC');
        $stmt->execute();
        $abonnements = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $r): array {
            return [
                $r['id'],
                (string) ($r['cabinet_code'] ?? '-') . ' ' . (string) ($r['cabinet_nom'] ?? '-'),
                $r['plan_nom'] ?? 'Sur mesure',
                format_date($r['date_debut'] ?? null),
                format_date($r['date_fin'] ?? null),
                abonnement_statut_label(abonnement_display_statut($r)),
                number_format((float) ($r['prix_annuel_negocie'] ?? 0), 2, ',', ' ') . ' ' . $r['devise'],
                (int) $r['auto_renew'] === 1 ? 'oui' : 'non',
            ];
        }, $abonnements);

        $headers = ['ID', 'Cabinet', 'Plan', 'Debut', 'Fin', 'Statut', 'Prix annuel', 'Renouvellement auto'];

        if ($exportType === 'csv') {
            export_csv('abonnements.csv', $headers, $rows);
        } else {
            export_excel('abonnements.xlsx', $headers, $rows);
        }
    }
}

// Compteurs du bandeau de synthese.
$stats = ['actifs' => 0, 'essais' => 0, 'expires' => 0, 'suspendus' => 0, 'revenu' => 0.0];
foreach ($abonnements as $row) {
    $display = abonnement_display_statut($row);
    if ($display === 'actif') {
        $stats['actifs']++;
        $stats['revenu'] += (float) ($row['prix_annuel_negocie'] ?? 0);
    } elseif ($display === 'essai') {
        $stats['essais']++;
    } elseif ($display === 'expire') {
        $stats['expires']++;
    } elseif ($display === 'suspendu') {
        $stats['suspendus']++;
    }
}

$statutOptions = [];
foreach (abonnement_statut_options() as $s) {
    $statutOptions[$s] = abonnement_statut_label($s);
}
?>
<section class="stack">
    <section class="stats">
        <article class="stat">
            <span>Abonnements actifs</span>
            <strong><?= $stats['actifs'] ?></strong>
        </article>
        <article class="stat">
            <span>Essais en cours</span>
            <strong><?= $stats['essais'] ?></strong>
        </article>
        <article class="stat">
            <span>Expires</span>
            <strong><?= $stats['expires'] ?></strong>
        </article>
        <article class="stat">
            <span>Suspendus</span>
            <strong><?= $stats['suspendus'] ?></strong>
        </article>
        <article class="stat">
            <span>Revenu annuel actif</span>
            <strong><?= e(number_format($stats['revenu'], 2, ',', ' ')) ?> <small style="font-size:1rem">MAD</small></strong>
        </article>
    </section>

    <?php if ($formOpen && ($canCreate || $canEdit)): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0"><?= $editId > 0 ? 'Modifier l\'abonnement' : 'Nouvel abonnement' ?></h2>
                <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>"><span class="material-symbols-outlined">close</span> Fermer</a>
            </div>

            <?php if ($formError !== null): ?>
                <div class="flash flash-error" style="margin-bottom:12px"><?= e($formError) ?></div>
            <?php endif; ?>

            <?php if ($cabinetOptions === []): ?>
                <div class="flash flash-warning" style="margin-bottom:12px">
                    Aucun cabinet n'est enregistre. <a href="<?= e(app_url('cabinets', ['action' => 'new'])) ?>">Creez d'abord un cabinet</a> pour pouvoir)y rattacher un abonnement.
                </div>
            <?php elseif ($planOptions === []): ?>
                <div class="flash flash-warning" style="margin-bottom:12px">
                    Aucun plan tarifaire actif. <a href="<?= e(app_url('plans', ['action' => 'new'])) ?>">Creez d'abord un plan</a>.
                </div>
            <?php endif; ?>

            <form method="post" class="sub-form-grid">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editId > 0): ?>
                    <input type="hidden" name="id" value="<?= e((string) $editId) ?>">
                <?php endif; ?>

                <label class="field">
                    <span>Cabinet *</span>
                    <select name="cabinet_id" required<?= $cabinetOptions === [] ? ' disabled' : '' ?>>
                        <option value="">— Selectionner —</option>
                        <?php foreach ($cabinetOptions as $id => $lbl): ?>
                            <option value="<?= e((string) $id) ?>"<?= (int) ($formData['cabinet_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Plan</span>
                    <select name="plan_id">
                        <option value="">Sur mesure (sans plan)</option>
                        <?php foreach ($planOptions as $id => $lbl): ?>
                            <option value="<?= e((string) $id) ?>"<?= (int) ($formData['plan_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Date de debut *</span>
                    <input type="date" name="date_debut" required value="<?= e((string) ($formData['date_debut'] ?? date('Y-m-d'))) ?>">
                </label>
                <label class="field">
                    <span>Date de fin *</span>
                    <input type="date" name="date_fin" value="<?= e((string) ($formData['date_fin'] ?? '')) ?>">
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
                    <span>Prix annuel negocie</span>
                    <input type="text" name="prix_annuel_negocie" inputmode="decimal" placeholder="herit du plan" value="<?= e((string) ($formData['prix_annuel_negocie'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Devise</span>
                    <input type="text" name="devise" maxlength="3" placeholder="MAD" value="<?= e((string) ($formData['devise'] ?? 'MAD')) ?>">
                </label>
                <div class="field">
                    <span>Options</span>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;padding-top:6px">
                        <label class="sub-form-check"><input type="checkbox" name="auto_renew" value="1"<?= (int) ($formData['auto_renew'] ?? 1) === 1 ? ' checked' : '' ?>> Renouvellement automatique</label>
                    </div>
                </div>
                <label class="field full">
                    <span>Notes</span>
                    <textarea name="notes" rows="2"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
                </label>

                <div class="sub-form-actions field full">
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">save</span> Enregistrer</button>
                    <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($abonnements) ?> abonnement(s)</span>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('abonnements', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-info" href="<?= e(app_url('abonnements', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="abonnements">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par cabinet, plan ou statut" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$abonnements): ?>
            <p class="table-empty">
                <?= $query !== '' ? 'Aucun abonnement ne correspond a cette recherche.' : 'Aucun abonnement enregistre. Creez un cabinet puis souscrivez son premier abonnement.' ?>
            </p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="abonnements">
                    <thead>
                        <tr>
                            <th data-col="cabinet">Cabinet</th>
                            <th data-col="plan">Plan</th>
                            <th data-col="debut">Debut</th>
                            <th data-col="fin">Fin</th>
                            <th data-col="restants">Jours restants</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="prix">Prix annuel</th>
                            <th data-col="renew">Renouvellement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($abonnements as $abo): ?>
                        <?php
                        $display = abonnement_display_statut($abo);
                        $jours = abonnement_jours_restants($abo);
                        ?>
                        <tr>
                            <td>
                                <strong><?= e((string) ($abo['cabinet_nom'] ?? '-')) ?></strong>
                                <br><small><?= e((string) ($abo['cabinet_code'] ?? '')) ?></small>
                            </td>
                            <td><?= e((string) ($abo['plan_nom'] ?? 'Sur mesure')) ?></td>
                            <td><?= e(format_date($abo['date_debut'] ?? null)) ?></td>
                            <td><?= e(format_date($abo['date_fin'] ?? null)) ?></td>
                            <td>
                                <?php if ($jours === null): ?>
                                    -
                                <?php elseif ($jours < 0): ?>
                                    <span style="color:var(--danger)">expire depuis <?= abs($jours) ?> j</span>
                                <?php else: ?>
                                    <?= $jours ?> j
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= e(abonnement_statut_tone($display)) ?>"><?= e(abonnement_statut_label($display)) ?></span></td>
                            <td><?= e(number_format((float) ($abo['prix_annuel_negocie'] ?? 0), 2, ',', ' ')) ?> <?= e((string) $abo['devise']) ?></td>
                            <td><?= (int) $abo['auto_renew'] === 1 ? 'Auto' : 'Manuel' ?></td>
                            <td class="table-actions">
                                <?php if ($canEdit): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="renew">
                                        <input type="hidden" name="id" value="<?= e((string) $abo['id']) ?>">
                                        <button class="btn-icon primary" type="submit" data-confirm="Prolonger cet abonnement d'un an ?" data-confirm-title="Renouveler l'abonnement" data-confirm-ok="Renouveler" data-confirm-tone="primary" title="Renouveler d'un an"><span class="material-symbols-outlined">autorenew</span></button>
                                    </form>
                                    <a class="btn-icon info" href="<?= e(app_url('abonnements', ['edit' => (int) $abo['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $abo['id']) ?>">
                                        <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet abonnement ?" data-confirm-title="Supprimer l'abonnement" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
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
