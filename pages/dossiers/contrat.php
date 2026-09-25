<?php

declare(strict_types=1);

/**
 * Fiche detaillee d'un contrat de domiciliation.
 *
 * Trois blocs : identite du contrat, facturation, cycle de vie
 * (renouvellement + resiliation). La resiliation est destructive dans le
 * sens metier : elle clot le contrat et n'est donc pas un simple edition
 * de statut. Elle exige une confirmation explicite et reste separate du
 * formulaire de renouvellement.
 */
$seuils = contrat_seuils();
$seuilRenouvellement = $seuils['renouvellement'];

$contratId = (int) ($_GET['id'] ?? 0);
$canView = has_permission('contrats.view');
$canEdit = has_permission('contrats.edit');

$contrat = null;
$collaborateur = null;
$alertes = [];

if ($contratId > 0 && ($pdo ?? null) instanceof PDO) {
    // Meme restriction que la liste et le suivi : un utilisateur non admin
    // ne peut pas ouvrir en direct l'URL d'un contrat d'une societe qu'il
    // n'a pas creee.
    $filtreUser = contrat_user_filter(current_user());
    $stmt = $pdo->prepare('
        SELECT c.*, s.societe_raison_sociale, s.societe_ville,
               s.societe_dossier_domiciliation_number,
               s.societe_dossier_creation_number, s.societe_forme_juridique
          FROM contrats c
          INNER JOIN societes s ON s.id = c.societe_id
         WHERE c.id = :id
        ' . $filtreUser['sql'] . '
    ');
    $stmt->execute(['id' => $contratId] + $filtreUser['params']);
    $contrat = $stmt->fetch() ?: null;

    if ($contrat !== null) {
        // Collaborateur responsable du dossier : lien principal en priorite,
        // sinon premier affecte, pour que la fiche ne soit jamais vide.
        $stmt = $pdo->prepare('
            SELECT co.*
              FROM collaborateur_societes cs
              INNER JOIN collaborateurs co ON co.id = cs.collaborateur_id
             WHERE cs.societe_id = :sid
             ORDER BY cs.is_principal DESC, cs.id ASC
             LIMIT 1
        ');
        $stmt->execute(['sid' => (int) $contrat['societe_id']]);
        $collaborateur = $stmt->fetch() ?: null;
    }
}

if (is_post() && $canEdit && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'renouveler' && $contrat !== null) {
        $dateDebut = field_value($_POST, 'contrat_date_debut');
        $dateFin = field_value($_POST, 'contrat_date_fin');
        $dateDebut = date_iso_valide($dateDebut) ? $dateDebut : $dateFin;

        if (!date_iso_valide($dateFin)) {
            set_flash('error', 'Date de fin invalide.');
        } elseif ($dateDebut > $dateFin) {
            set_flash('error', 'La date de debut doit preceder la date de fin.');
        } else {
            $stmt = $pdo->prepare('
                UPDATE contrats
                   SET contrat_date_debut = :debut,
                       contrat_date_fin = :fin,
                       contrat_type_renouvellement = :type,
                       contrat_renouv_tva_pourcent = :tva,
                       contrat_renouv_loyer_ht = :loyer,
                       contrat_statut = :statut
                 WHERE id = :id
            ');
            $stmt->execute([
                'debut' => $dateDebut,
                'fin' => $dateFin,
                'type' => field_value($_POST, 'contrat_type_renouvellement'),
                'tva' => money_value($_POST, 'contrat_renouv_tva_pourcent'),
                'loyer' => money_value($_POST, 'contrat_renouv_loyer_ht'),
                'statut' => 'actif',
                'id' => $contratId,
            ]);
            log_activity($pdo, 'update', 'contrat', $contratId, null, 'Renouvellement enregistre');
            set_flash('success', 'Renouvellement enregistre.');
            redirect_to('contrat', ['id' => $contratId]);
        }
    }

    if ($action === 'resilier' && $contrat !== null) {
        $dateResiliation = field_value($_POST, 'contrat_date_resiliation');
        $motif = field_value($_POST, 'contrat_motif_resiliation');

        if (!date_iso_valide($dateResiliation)) {
            set_flash('error', 'Date de resiliation invalide.');
        } elseif ($motif === '') {
            set_flash('error', 'Le motif de resiliation est obligatoire.');
        } elseif ($dateResiliation > (string) date('Y-m-d')) {
            set_flash('error', 'La date de resiliation ne peut pas etre dans le futur.');
        } else {
            $stmt = $pdo->prepare('
                UPDATE contrats
                   SET contrat_statut = :statut,
                       contrat_date_resiliation = :date_res,
                       contrat_motif_resiliation = :motif
                 WHERE id = :id
            ');
            $stmt->execute([
                'statut' => 'resilie',
                'date_res' => $dateResiliation,
                'motif' => $motif,
                'id' => $contratId,
            ]);
            log_activity($pdo, 'update', 'contrat', $contratId, null, 'Contrat resilie : ' . $motif);
            set_flash('success', 'Contrat resilie.');
            redirect_to('contrat', ['id' => $contratId]);
        }
    }

    if ($action === 'retablir' && $contrat !== null) {
        $stmt = $pdo->prepare("UPDATE contrats SET contrat_statut = 'actif', contrat_date_resiliation = NULL, contrat_motif_resiliation = NULL WHERE id = :id");
        $stmt->execute(['id' => $contratId]);
        log_activity($pdo, 'update', 'contrat', $contratId, null, 'Contrat retabli');
        set_flash('success', 'Contrat retabli.');
        redirect_to('contrat', ['id' => $contratId]);
    }
}

$statut = (string) ($contrat['contrat_statut'] ?? '');
$jours = $contrat !== null ? contrat_jours_avant_echeance($contrat['contrat_date_fin'] ?? null) : null;
$estResilie = $statut === 'resilie';

if ($contrat !== null && !$estResilie) {
    if ($jours !== null && $jours < 0) {
        $alertes['urgent'][] = 'Contrat echu depuis le ' . format_date($contrat['contrat_date_fin'] ?? null) . ' (' . abs((int) $jours) . ' jours).';
    } elseif ($jours !== null && $jours <= $seuilRenouvellement) {
        $alertes['warning'][] = 'Contrat a renouveler sous ' . $jours . ' jour(s) (echeance le ' . format_date($contrat['contrat_date_fin'] ?? null) . ').';
    }
    if (trim((string) ($contrat['contrat_date_fin'] ?? '')) === '') {
        $alertes['warning'][] = 'Aucune date de fin de validite renseignee : le suivi des echeances est desactive pour ce contrat.';
    }
}
?>
<?php if (!$canView || $contrat === null): ?>
    <section class="card">
        <p class="table-empty">Contrat introuvable ou acces refuse.</p>
        <div class="table-actions" style="justify-content:flex-end;">
            <a class="btn btn-secondary" href="<?= e(app_url('contrats')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        </div>
    </section>
<?php else: ?>
    <section class="grid two">
        <article class="card stack">
            <div class="section-header">
                <h2 style="margin:0;"><?= e((string) $contrat['societe_raison_sociale']) ?></h2>
                <span class="statut-badge <?= e($statut) ?>"><?= e(contrat_statut_libelle($statut)) ?></span>
            </div>

            <?php if ($alertes !== []): ?>
                <div class="alerts-list">
                    <div class="alert-group">
                        <?php foreach ($alertes as $niveau => $messages): ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="alert-item <?= e($niveau) ?>">
                                    <span class="material-symbols-outlined" style="color:<?= $niveau === 'urgent' ? 'var(--danger)' : 'var(--warning)' ?>">warning</span>
                                    <span><?= e($message) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="info-grid">
                <div><span>Type de contrat</span><strong><?= e((string) $contrat['contrat_type']) ?></strong></div>
                <div><span>Type de domiciliation</span><strong><?= e((string) ($contrat['contrat_type_domiciliation'] ?: '—')) ?></strong></div>
                <div><span>N° dossier</span><strong><?= e((string) ($contrat['societe_dossier_domiciliation_number'] ?: $contrat['societe_dossier_creation_number'] ?: '—')) ?></strong></div>
                <div><span>Forme juridique</span><strong><?= e((string) ($contrat['societe_forme_juridique'] ?: '—')) ?></strong></div>
                <div><span>Durée</span><strong><?= $contrat['contrat_duree_mois'] ? e((string) $contrat['contrat_duree_mois']) . ' mois' : '—' ?></strong></div>
                <div><span>Date de contrat</span><strong><?= e(format_date($contrat['contrat_date'] ?? null)) ?></strong></div>
                <div><span>Début de validité</span><strong><?= e(format_date($contrat['contrat_date_debut'] ?? null)) ?></strong></div>
                <div><span>Fin de validité</span><strong><?= e(format_date($contrat['contrat_date_fin'] ?? null)) ?></strong></div>
                <div><span>Mode de signature</span><strong><?= e((string) ($contrat['contrat_mode_signature'] ?: '—')) ?></strong></div>
                <div>
                    <span>Collaborateur responsable</span>
                    <strong>
                        <?php if ($collaborateur !== null): ?>
                            <a href="<?= e(app_url('collaborateur', ['id' => (int) $collaborateur['id']])) ?>"><?= e((string) ($collaborateur['nom_complet'] ?: $collaborateur['den_ste'] ?: '—')) ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </strong>
                </div>
            </div>
        </article>

        <article class="card stack">
            <div class="section-header"><h2 style="margin:0;">Facturation</h2></div>
            <div class="info-grid">
                <div><span>Loyer HT</span><strong><?= e(format_money(money_from($contrat['contrat_loyer_ht'] ?? null))) ?></strong></div>
                <div><span>Loyer TTC</span><strong><?= e(format_money(money_from($contrat['contrat_loyer_ttc'] ?? null))) ?></strong></div>
                <div><span>TVA</span><strong><?= $contrat['contrat_tva_pourcent'] !== null ? e((string) $contrat['contrat_tva_pourcent']) . ' %' : '—' ?></strong></div>
                <div><span>Frais d'intermédiaire</span><strong><?= e(format_money(money_from($contrat['contrat_frais_intermediaire'] ?? null))) ?></strong></div>
                <div><span>Caution</span><strong><?= e(format_money(money_from($contrat['contrat_caution'] ?? null))) ?></strong></div>
                <div><span>Pack TTC</span><strong><?= e(format_money(money_from($contrat['contrat_pack_montant_ttc'] ?? null))) ?></strong></div>
                <div><span>Total HT</span><strong><?= e(format_money(money_from($contrat['contrat_total_ht'] ?? null))) ?></strong></div>
            </div>
        </article>
    </section>

    <section class="grid two">
        <article class="card stack">
            <div class="section-header"><h2 style="margin:0;">Renouvellement</h2></div>
            <div class="info-grid">
                <div><span>Type de renouvellement</span><strong><?= e((string) ($contrat['contrat_type_renouvellement'] ?: '—')) ?></strong></div>
                <div><span>Loyer HT renouvelé</span><strong><?= e(format_money(money_from($contrat['contrat_renouv_loyer_ht'] ?? null))) ?></strong></div>
                <div><span>Loyer TTC renouvelé</span><strong><?= e(format_money(money_from($contrat['contrat_renouv_loyer_ttc'] ?? null))) ?></strong></div>
                <div><span>TVA renouvelée</span><strong><?= $contrat['contrat_renouv_tva_pourcent'] !== null ? e((string) $contrat['contrat_renouv_tva_pourcent']) . ' %' : '—' ?></strong></div>
            </div>
            <?php if ($canEdit && !$estResilie): ?>
                <form method="post" class="stack">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="renouveler">
                    <div class="form-grid">
                        <div class="field">
                            <label for="renouv_date_debut">Nouvelle date de début</label>
                            <input type="date" id="renouv_date_debut" name="contrat_date_debut" value="<?= e((string) date('Y-m-d')) ?>">
                        </div>
                        <div class="field">
                            <label for="renouv_date_fin">Nouvelle date de fin</label>
                            <input type="date" id="renouv_date_fin" name="contrat_date_fin" value="<?= e((string) date('Y-m-d', strtotime('+1 year'))) ?>" required>
                        </div>
                        <div class="field">
                            <label for="renouv_type">P&eacute;riodicité</label>
                            <select id="renouv_type" name="contrat_type_renouvellement">
                                <?php foreach (['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'] as $periodicite): ?>
                                    <option value="<?= e($periodicite) ?>" <?= (string) $contrat['contrat_type_renouvellement'] === $periodicite ? 'selected' : '' ?>><?= e($periodicite) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="renouv_tva">TVA (%)</label>
                            <input type="text" inputmode="decimal" id="renouv_tva" name="contrat_renouv_tva_pourcent" value="<?= e((string) ($contrat['contrat_renouv_tva_pourcent'] ?? '20')) ?>">
                        </div>
                        <div class="field">
                            <label for="renouv_loyer">Loyer HT renouvel&eacute;</label>
                            <input type="text" inputmode="decimal" id="renouv_loyer" name="contrat_renouv_loyer_ht" value="<?= e((string) ($contrat['contrat_renouv_loyer_ht'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="table-actions" style="justify-content:flex-end;">
                        <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">autorenew</span> Enregistrer le renouvellement</button>
                    </div>
                </form>
            <?php endif; ?>
        </article>

        <article class="card stack">
            <div class="section-header"><h2 style="margin:0;">Résiliation</h2></div>
            <?php if ($estResilie): ?>
                <div class="info-grid">
                    <div><span>Date de résiliation</span><strong><?= e(format_date($contrat['contrat_date_resiliation'] ?? null)) ?></strong></div>
                    <div class="full"><span>Motif</span><strong><?= e((string) ($contrat['contrat_motif_resiliation'] ?: '—')) ?></strong></div>
                </div>
                <?php if ($canEdit): ?>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="retablir">
                        <div class="table-actions" style="justify-content:flex-end;">
                            <button type="submit" class="btn btn-back" data-confirm="Rétablir ce contrat comme actif ? La date et le motif de résiliation seront effacés."><span class="material-symbols-outlined">undo</span> Rétablir le contrat</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php elseif ($canEdit): ?>
                <form method="post" class="stack">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="resilier">
                    <div class="field">
                        <label for="contrat_date_resiliation">Date de résiliation</label>
                        <input type="date" id="contrat_date_resiliation" name="contrat_date_resiliation" value="<?= e((string) date('Y-m-d')) ?>" max="<?= e((string) date('Y-m-d')) ?>" required>
                    </div>
                    <div class="field">
                        <label for="contrat_motif_resiliation">Motif de résiliation</label>
                        <textarea id="contrat_motif_resiliation" name="contrat_motif_resiliation" rows="2" required placeholder="Démission du gérant, non-paiement, résiliation à l'amiable…"></textarea>
                    </div>
                    <div class="table-actions" style="justify-content:flex-end;">
                        <button type="submit" class="btn btn-danger" data-confirm="Résilier définitivement ce contrat ? Cette action clos le contrat et modifie son statut."><span class="material-symbols-outlined">block</span> Résilier le contrat</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="table-empty">Aucune résiliation enregistrée.</p>
            <?php endif; ?>
        </article>
    </section>

    <section class="card">
        <div class="section-header"><h2 style="margin:0;">Notes</h2></div>
        <?php if (trim((string) ($contrat['contrat_notes'] ?? '')) === ''): ?>
            <p class="table-empty">Aucune note.</p>
        <?php else: ?>
            <p><?= nl2br(e((string) $contrat['contrat_notes'])) ?></p>
        <?php endif; ?>
    </section>

    <div class="table-actions" style="justify-content:flex-end;margin-top:1rem;">
        <a class="btn btn-secondary" href="<?= e(app_url('contrats_suivi')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour au suivi</a>
        <a class="btn btn-info" href="<?= e(app_url('societe', ['id' => (int) $contrat['societe_id']])) ?>"><span class="material-symbols-outlined">domain</span> Fiche société</a>
    </div>
<?php endif; ?>
