<?php

declare(strict_types=1);

/**
 * Administration SaaS - Factures et encaissements.
 *
 * Le montant TTC est recalcule cote serveur a partir du HT et de la TVA : le
 * champ TTC du formulaire n'est qu'un apercu, jamais une source de verite.
 * L'encaissement cree la ligne `paiements` puis bascule la facture en 'payee'
 * dans une transaction, pour ne jamais laisser une facture payee sans
 * paiement rattache (ou l'inverse).
 */

$query = search_term();
$canCreate = has_permission('factures.create');
$canEdit = has_permission('factures.edit');
$canDelete = has_permission('factures.delete');
$canPay = has_permission('paiements.create');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$cabinetOptions = fetch_cabinets_options($db);
$abonnementOptions = [];
if ($db) {
    // Seuls les abonnements non resilies sont proposables : facturer un
    // abonnement resilie n'a pas de sens metier.
    $stmt = $db->query("
        SELECT a.id, c.nom AS cabinet_nom, a.date_debut, a.date_fin
        FROM abonnements a
        LEFT JOIN cabinets c ON c.id = a.cabinet_id
        WHERE a.statut <> 'resilie'
        ORDER BY c.nom ASC, a.date_debut DESC
    ");
    foreach ($stmt->fetchAll() as $row) {
        $abonnementOptions[(int) $row['id']] = (string) $row['cabinet_nom']
            . ' — ' . format_date((string) $row['date_debut']) . ' → ' . format_date((string) $row['date_fin']);
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$payId = isset($_GET['encaisser']) ? (int) $_GET['encaisser'] : 0;
$formOpen = (isset($_GET['action']) && $_GET['action'] === 'new') || $payId > 0;

$formData = [
    'numero' => '', 'cabinet_id' => '', 'abonnement_id' => '',
    'date_emission' => date('Y-m-d'), 'date_echeance' => '',
    'montant_ht' => '0', 'tva_pct' => '20', 'statut' => 'brouillon', 'notes' => '',
];
$formError = null;
$payData = ['montant' => '', 'mode' => 'virement', 'reference' => '', 'date_paiement' => date('Y-m-d')];
$payTarget = null;

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM factures WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Facture introuvable.');
        redirect_to('factures');
    }
}

if ($db && $payId > 0) {
    $stmt = $db->prepare('SELECT * FROM factures WHERE id = :id');
    $stmt->execute(['id' => $payId]);
    $payTarget = $stmt->fetch();
    if (!$payTarget) {
        set_flash('error', 'Facture introuvable.');
        redirect_to('factures');
    }
    if ($payTarget['paiement_id'] !== null) {
        set_flash('error', 'Cette facture est deja rattachee a un paiement.');
        redirect_to('factures');
    }
    // On propose par defaut le TTC restant du.
    $payData['montant'] = number_format((float) $payTarget['montant_ttc'], 2, '.', '');
}

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        if (!$canDelete) {
            require_permission('factures.delete');
        }

        $targetId = (int) ($_POST['id'] ?? 0);

        $check = $db->prepare('SELECT paiement_id FROM factures WHERE id = :id');
        $check->execute(['id' => $targetId]);
        $row = $check->fetch();

        if ($row && $row['paiement_id'] !== null) {
            set_flash('error', 'Suppression impossible : cette facture est rattachee a un paiement. Annulez-la plutot.');
        } else {
            $stmt = $db->prepare('DELETE FROM factures WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'facture', $targetId);
            set_flash('success', 'Facture supprimee.');
        }

        redirect_to('factures');
    }

    if ($action === 'pay') {
        if (!$canPay) {
            require_permission('paiements.create');
        }

        $targetId = (int) ($_POST['id'] ?? 0);
        $montant = money_value($_POST, 'montant');
        $mode = field_value($_POST, 'mode', 'virement');
        $datePaiement = field_value($_POST, 'date_paiement');

        if (!in_array($mode, paiement_mode_options(), true)) {
            $mode = 'virement';
        }

        $errors = [];
        if ($montant === null || $montant <= 0) {
            $errors[] = 'Le montant encaisse doit etre superieur a zero.';
        }
        if ($datePaiement === '' || strtotime($datePaiement) === false) {
            $errors[] = 'La date de paiement est obligatoire.';
        }

        $fact = $db->prepare('SELECT id, cabinet_id, abonnement_id, montant_ttc, paiement_id, numero FROM factures WHERE id = :id');
        $fact->execute(['id' => $targetId]);
        $facture = $fact->fetch();

        if (!$facture) {
            $errors[] = 'Facture introuvable.';
        } elseif ($facture['paiement_id'] !== null) {
            $errors[] = 'Cette facture est deja encaissee.';
        }

        if ($errors !== []) {
            $formError = implode(' ', $errors);
            $formOpen = true;
            $payId = $targetId;
        } else {
            $db->beginTransaction();
            try {
                $ins = $db->prepare('
                    INSERT INTO paiements (abonnement_id, cabinet_id, montant, devise, mode, reference, date_paiement, periode_debut, periode_fin, statut, notes)
                    VALUES (:abid, :cid, :montant, :devise, :mode, :ref, :date, NULL, NULL, \'encaisse\', NULL)
                ');

                $periode = $db->prepare('SELECT date_debut, date_fin FROM abonnements WHERE id = :id');
                $periode->execute(['id' => $facture['abonnement_id'] === null ? 0 : (int) $facture['abonnement_id']]);
                $abo = $periode->fetch();

                $ins->execute([
                    'abid' => $facture['abonnement_id'] === null ? null : (int) $facture['abonnement_id'],
                    'cid' => (int) $facture['cabinet_id'],
                    'montant' => number_format((float) $montant, 2, '.', ''),
                    'devise' => 'MAD',
                    'mode' => $mode,
                    'ref' => field_value($_POST, 'reference'),
                    'date' => $datePaiement,
                ]);
                $paiementId = (int) $db->lastInsertId();

                if ($abo) {
                    $updPeriode = $db->prepare('UPDATE paiements SET periode_debut = :d, periode_fin = :f WHERE id = :id');
                    $updPeriode->execute([
                        'd' => $abo['date_debut'],
                        'f' => $abo['date_fin'],
                        'id' => $paiementId,
                    ]);
                }

                $upd = $db->prepare("UPDATE factures SET paiement_id = :pid, statut = 'payee' WHERE id = :id");
                $upd->execute(['pid' => $paiementId, 'id' => $targetId]);

                $db->commit();
                log_activity($db, 'create', 'paiement', $paiementId, 'Facture ' . (string) $facture['numero']);
                set_flash('success', 'Paiement enregistre : facture ' . (string) $facture['numero'] . ' passee en payee.');
            } catch (PDOException $ex) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                set_flash('error', 'Encaissement impossible : ' . $ex->getMessage());
            }

            redirect_to('factures');
        }
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId > 0 && !$canEdit) {
            require_permission('factures.edit');
        }
        if ($targetId === 0 && !$canCreate) {
            require_permission('factures.create');
        }

        $cabinetId = (int) (int_value($_POST, 'cabinet_id') ?? 0);
        $abonnementId = int_value($_POST, 'abonnement_id');
        $dateEmission = field_value($_POST, 'date_emission');
        $dateEcheance = field_value($_POST, 'date_echeance');
        $statut = field_value($_POST, 'statut', 'brouillon');

        if (!in_array($statut, facture_statut_options(), true)) {
            $statut = 'brouillon';
        }

        $errors = [];

        if ($cabinetId <= 0) {
            $errors[] = 'Selectionnez un cabinet.';
        } else {
            $exists = $db->prepare('SELECT id FROM cabinets WHERE id = :id');
            $exists->execute(['id' => $cabinetId]);
            if (!$exists->fetch()) {
                $errors[] = 'Ce cabinet est introuvable.';
            }
        }

        if ($abonnementId !== null && $abonnementId > 0) {
            $link = $db->prepare('SELECT cabinet_id FROM abonnements WHERE id = :id');
            $link->execute(['id' => $abonnementId]);
            $linked = $link->fetch();
            if (!$linked) {
                $errors[] = 'Cet abonnement est introuvable.';
            } elseif ((int) $linked['cabinet_id'] !== $cabinetId) {
                // Un abonnement ne peut pas etre facture sur un autre cabinet.
                $errors[] = 'L\'abonnement selectionne n\'appartient pas a ce cabinet.';
            }
        } else {
            $abonnementId = null;
        }

        if ($dateEmission === '' || strtotime($dateEmission) === false) {
            $errors[] = 'La date d\'emission est obligatoire.';
        }
        if ($dateEcheance !== '' && strtotime($dateEcheance) === false) {
            $errors[] = 'La date d\'echeance est invalide.';
        }
        if ($dateEcheance !== '' && $dateEmission !== '' && strtotime($dateEcheance) !== false && $dateEcheance < $dateEmission) {
            $errors[] = 'La date d\'echeance doit suivre la date d\'emission.';
        }

        $montantHt = money_value($_POST, 'montant_ht');
        if ($montantHt === null || $montantHt < 0) {
            $errors[] = 'Le montant HT est invalide.';
        }
        $tva = money_value($_POST, 'tva_pct');
        if ($tva === null || $tva < 0 || $tva > 100) {
            $errors[] = 'Le taux de TVA doit etre compris entre 0 et 100.';
        }

        $numero = field_value($_POST, 'numero');
        if ($numero === '') {
            $numero = next_facture_number($db);
        } else {
            $dup = $db->prepare('SELECT id FROM factures WHERE numero = :num AND id <> :id');
            $dup->execute(['num' => $numero, 'id' => $targetId]);
            if ($dup->fetch()) {
                $errors[] = 'Ce numero de facture est deja utilise.';
            }
        }

        if ($errors !== []) {
            $formError = implode(' ', $errors);
            $formOpen = true;
            $formData = array_merge($formData, $_POST);
        } else {
            $montantHt = (float) $montantHt;
            $tva = (float) $tva;
            $ttc = round($montantHt * (1 + $tva / 100), 2);

            $payload = [
                'numero' => $numero,
                'cabinet_id' => $cabinetId,
                'abonnement_id' => $abonnementId,
                'date_emission' => $dateEmission,
                'date_echeance' => $dateEcheance !== '' ? $dateEcheance : null,
                'montant_ht' => number_format($montantHt, 2, '.', ''),
                'tva_pct' => number_format($tva, 2, '.', ''),
                'montant_ttc' => number_format($ttc, 2, '.', ''),
                'statut' => $statut,
                'notes' => field_value($_POST, 'notes'),
            ];

            if ($targetId > 0) {
                // Une facture deja encaissee garde son paiement : on ne touche
                // ni au paiement_id ni au statut 'payee' via ce formulaire.
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    if ($col === 'statut' && (string) $val === 'payee') {
                        continue;
                    }
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE factures SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'facture', $targetId, $numero);
                set_flash('success', 'Facture ' . $numero . ' mise a jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO factures (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $insertedId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'facture', $insertedId, $numero);
                set_flash('success', 'Facture ' . $numero . ' creee.');
            }

            redirect_to('factures');
        }
    }
}

$factures = [];
$paiements = [];
if ($db) {
    $baseSql = 'SELECT f.*, c.nom AS cabinet_nom, c.code AS cabinet_code
                FROM factures f
                LEFT JOIN cabinets c ON c.id = f.cabinet_id';

    if ($query !== '') {
        $like = like_term($query);
        $stmt = $db->prepare($baseSql . '
            WHERE f.numero LIKE :t1 OR c.nom LIKE :t2 OR c.code LIKE :t3 OR f.statut LIKE :t4
            ORDER BY f.date_emission DESC, f.id DESC
        ');
        $stmt->execute(['t1' => $like, 't2' => $like, 't3' => $like, 't4' => $like]);
        $factures = $stmt->fetchAll();
    } else {
        $stmt = $db->prepare($baseSql . ' ORDER BY f.date_emission DESC, f.id DESC');
        $stmt->execute();
        $factures = $stmt->fetchAll();
    }

    $pst = $db->query('
        SELECT p.*, c.nom AS cabinet_nom, f.numero AS facture_numero
        FROM paiements p
        LEFT JOIN cabinets c ON c.id = p.cabinet_id
        LEFT JOIN factures f ON f.id = (SELECT id FROM factures WHERE paiement_id = p.id LIMIT 1)
        ORDER BY p.date_paiement DESC, p.id DESC
    ');
    $paiements = $pst->fetchAll();

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static fn (array $r): array => [
            $r['numero'],
            (string) ($r['cabinet_code'] ?? '-') . ' ' . (string) ($r['cabinet_nom'] ?? '-'),
            format_date($r['date_emission'] ?? null),
            format_date($r['date_echeance'] ?? null),
            number_format((float) $r['montant_ht'], 2, ',', ' '),
            number_format((float) $r['tva_pct'], 2, ',', ' ') . ' %',
            number_format((float) $r['montant_ttc'], 2, ',', ' '),
            facture_display_statut_label($r),
        ], $factures);

        $headers = ['Numero', 'Cabinet', 'Emission', 'Echeance', 'Montant HT', 'TVA', 'Montant TTC', 'Statut'];

        if ($exportType === 'csv') {
            export_csv('factures.csv', $headers, $rows);
        } else {
            export_excel('factures.xlsx', $headers, $rows);
        }
    }
}

$stats = ['emises' => 0, 'retard' => 0, 'payees' => 0, 'ttc' => 0.0, 'encaisse' => 0.0];
foreach ($factures as $f) {
    $display = facture_display_statut($f);
    if ($display === 'payee') {
        $stats['payees']++;
    } elseif ($display === 'en_retard') {
        $stats['retard']++;
        $stats['emises']++;
    } elseif ($display === 'emise') {
        $stats['emises']++;
    }

    if ($display !== 'annulee') {
        $stats['ttc'] += (float) $f['montant_ttc'];
    }
}
foreach ($paiements as $p) {
    $stats['encaisse'] += (float) $p['montant'];
}

$factureStatutOptions = [];
foreach (facture_statut_options() as $s) {
    $factureStatutOptions[$s] = facture_statut_label($s);
}

$modeOptions = [];
foreach (paiement_mode_options() as $m) {
    $modeOptions[$m] = ucfirst($m);
}
?>
<section class="stack">
    <section class="stats">
        <article class="stat">
            <span>Factures emises</span>
            <strong><?= $stats['emises'] ?></strong>
        </article>
        <article class="stat">
            <span>En retard</span>
            <strong><?= $stats['retard'] ?></strong>
        </article>
        <article class="stat">
            <span>Payees</span>
            <strong><?= $stats['payees'] ?></strong>
        </article>
        <article class="stat">
            <span>Total TTC facturé</span>
            <strong><?= e(number_format($stats['ttc'], 2, ',', ' ')) ?> <small style="font-size:1rem">MAD</small></strong>
        </article>
        <article class="stat">
            <span>Total encaisse</span>
            <strong><?= e(number_format($stats['encaisse'], 2, ',', ' ')) ?> <small style="font-size:1rem">MAD</small></strong>
        </article>
    </section>

    <?php if ($formOpen && $payTarget !== null && $canPay): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0">Encaisser la facture <?= e((string) $payTarget['numero']) ?></h2>
                <a class="btn btn-cancel" href="<?= e(app_url('factures')) ?>"><span class="material-symbols-outlined">close</span> Fermer</a>
            </div>

            <?php if ($formError !== null): ?>
                <div class="flash flash-error" style="margin-bottom:12px"><?= e($formError) ?></div>
            <?php endif; ?>

            <form method="post" class="sub-form-grid">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="id" value="<?= e((string) $payTarget['id']) ?>">

                <label class="field">
                    <span>Montant encaisse *</span>
                    <input type="text" name="montant" required inputmode="decimal" value="<?= e((string) ($payData['montant'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Mode</span>
                    <select name="mode">
                        <?php foreach ($modeOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>"<?= (string) ($payData['mode'] ?? 'virement') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Date de paiement *</span>
                    <input type="date" name="date_paiement" required value="<?= e((string) ($payData['date_paiement'] ?? date('Y-m-d'))) ?>">
                </label>
                <label class="field">
                    <span>Reference</span>
                    <input type="text" name="reference" maxlength="120" value="<?= e((string) ($payData['reference'] ?? '')) ?>">
                </label>

                <div class="sub-form-actions field full">
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">payments</span> Enregistrer le paiement</button>
                    <a class="btn btn-cancel" href="<?= e(app_url('factures')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            </form>
        </article>
    <?php elseif ($formOpen && ($canCreate || $canEdit)): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0"><?= $editId > 0 ? 'Modifier la facture' : 'Nouvelle facture' ?></h2>
                <a class="btn btn-cancel" href="<?= e(app_url('factures')) ?>"><span class="material-symbols-outlined">close</span> Fermer</a>
            </div>

            <?php if ($formError !== null): ?>
                <div class="flash flash-error" style="margin-bottom:12px"><?= e($formError) ?></div>
            <?php endif; ?>

            <?php if ($cabinetOptions === []): ?>
                <div class="flash flash-warning" style="margin-bottom:12px">
                    Aucun cabinet enregistre. <a href="<?= e(app_url('cabinets', ['action' => 'new'])) ?>">Creez d'abord un cabinet</a>.
                </div>
            <?php endif; ?>

            <form method="post" class="sub-form-grid">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editId > 0): ?>
                    <input type="hidden" name="id" value="<?= e((string) $editId) ?>">
                <?php endif; ?>

                <label class="field">
                    <span>Numero</span>
                    <input type="text" name="numero" maxlength="40" placeholder="<?= e($editId > 0 ? (string) $formData['numero'] : next_facture_number($db)) ?>" value="<?= e((string) ($formData['numero'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Cabinet *</span>
                    <select name="cabinet_id" required<?= $cabinetOptions === [] ? ' disabled' : '' ?>>
                        <option value="">— Selectionner —</option>
                        <?php foreach ($cabinetOptions as $id => $lbl): ?>
                            <option value="<?= e((string) $id) ?>"<?= (int) ($formData['cabinet_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field full">
                    <span>Abonnement rattache</span>
                    <select name="abonnement_id">
                        <option value="">Aucun (facture libre)</option>
                        <?php foreach ($abonnementOptions as $id => $lbl): ?>
                            <option value="<?= e((string) $id) ?>"<?= (int) ($formData['abonnement_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Date d'emission *</span>
                    <input type="date" name="date_emission" required value="<?= e((string) ($formData['date_emission'] ?? date('Y-m-d'))) ?>">
                </label>
                <label class="field">
                    <span>Date d'echeance</span>
                    <input type="date" name="date_echeance" value="<?= e((string) ($formData['date_echeance'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Montant HT *</span>
                    <input type="text" name="montant_ht" required inputmode="decimal" value="<?= e((string) ($formData['montant_ht'] ?? '0')) ?>">
                </label>
                <label class="field">
                    <span>TVA (%)</span>
                    <input type="text" name="tva_pct" inputmode="decimal" value="<?= e((string) ($formData['tva_pct'] ?? '20')) ?>">
                </label>
                <label class="field">
                    <span>Montant TTC</span>
                    <input type="text" readonly disabled value="<?= e(number_format(((float) ($formData['montant_ht'] ?? 0)) * (1 + ((float) ($formData['tva_pct'] ?? 0)) / 100), 2, ',', ' ')) ?> MAD">
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="statut">
                        <?php foreach ($factureStatutOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>"<?= (string) ($formData['statut'] ?? 'brouillon') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field full">
                    <span>Notes</span>
                    <textarea name="notes" rows="2"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
                </label>

                <div class="sub-form-actions field full">
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">save</span> Enregistrer</button>
                    <a class="btn btn-cancel" href="<?= e(app_url('factures')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($factures) ?> facture(s)</span>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('factures', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-info" href="<?= e(app_url('factures', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="factures">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par numero, cabinet ou statut" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('factures')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$factures): ?>
            <p class="table-empty"><?= $query !== '' ? 'Aucune facture ne correspond a cette recherche.' : 'Aucune facture. Creez un abonnement, puis emettez sa facture annuelle.' ?></p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="factures">
                    <thead>
                        <tr>
                            <th data-col="numero">Numero</th>
                            <th data-col="cabinet">Cabinet</th>
                            <th data-col="emission">Emission</th>
                            <th data-col="echeance">Echeance</th>
                            <th data-col="ht">Montant HT</th>
                            <th data-col="tva">TVA</th>
                            <th data-col="ttc">Montant TTC</th>
                            <th data-col="statut">Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($factures as $fact): ?>
                        <tr>
                            <td><strong><?= e((string) $fact['numero']) ?></strong></td>
                            <td>
                                <?= e((string) ($fact['cabinet_nom'] ?? '-')) ?>
                                <br><small><?= e((string) ($fact['cabinet_code'] ?? '')) ?></small>
                            </td>
                            <td><?= e(format_date($fact['date_emission'] ?? null)) ?></td>
                            <td>
                                <?= e(format_date($fact['date_echeance'] ?? null)) ?>
                                <?php if (facture_display_statut($fact) === 'en_retard'): ?>
                                    <br><small style="color:var(--danger)">Echue</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e(number_format((float) $fact['montant_ht'], 2, ',', ' ')) ?></td>
                            <td><?= e(number_format((float) $fact['tva_pct'], 2, ',', ' ')) ?> %</td>
                            <td><strong><?= e(number_format((float) $fact['montant_ttc'], 2, ',', ' ')) ?></strong></td>
                            <td><span class="badge <?= e(facture_statut_tone($fact)) ?>"><?= e(facture_display_statut_label($fact)) ?></span></td>
                            <td class="table-actions">
                                <?php if ($canPay && $fact['paiement_id'] === null && in_array((string) $fact['statut'], ['brouillon', 'emise'], true)): ?>
                                    <a class="btn-icon primary" href="<?= e(app_url('factures', ['encaisser' => (int) $fact['id']])) ?>" title="Encaisser"><span class="material-symbols-outlined">payments</span></a>
                                <?php endif; ?>
                                <?php if ($canEdit): ?>
                                    <a class="btn-icon info" href="<?= e(app_url('factures', ['edit' => (int) $fact['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $fact['id']) ?>">
                                        <button class="btn-icon danger" type="submit" data-confirm="Supprimer la facture <?= e((string) $fact['numero']) ?> ?" data-confirm-title="Supprimer la facture" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
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

    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($paiements) ?> paiement(s) enregistre(s)</span>
        </div>
        <?php if (!$paiements): ?>
            <p class="table-empty">Aucun paiement enregistre.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="paiements">
                    <thead>
                        <tr>
                            <th data-col="date">Date</th>
                            <th data-col="cabinet">Cabinet</th>
                            <th data-col="facture">Facture</th>
                            <th data-col="montant">Montant</th>
                            <th data-col="mode">Mode</th>
                            <th data-col="ref">Reference</th>
                            <th data-col="statut">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paiements as $pay): ?>
                        <tr>
                            <td><?= e(format_date($pay['date_paiement'] ?? null)) ?></td>
                            <td><?= e((string) ($pay['cabinet_nom'] ?? '-')) ?></td>
                            <td><?= e((string) ($pay['facture_numero'] ?? '-')) ?></td>
                            <td><strong><?= e(number_format((float) $pay['montant'], 2, ',', ' ')) ?> <?= e((string) $pay['devise']) ?></strong></td>
                            <td><?= e(ucfirst((string) $pay['mode'])) ?></td>
                            <td><?= e((string) ($pay['reference'] ?? '-')) ?></td>
                            <td><?= e(ucfirst((string) $pay['statut'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>
