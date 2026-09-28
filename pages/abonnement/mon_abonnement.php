<?php

declare(strict_types=1);

/**
 * Espace adherent - "Mon abonnement".
 *
 * Lecture seule et strictement cloisonnee : toutes les requetes passent par
 * current_cabinet_id(), jamais par un parametre d'URL. Un compte Centre
 * (cabinet_id NULL) n'a rien a voir ici et n'obtient qu'un message.
 *
 * La page n bloque jamais : c'est un tableau de bord d'information. Le
 * bandeau de includes/bandeau_abonnement.php porte l'avertissement.
 */

$db = ($pdo ?? null) instanceof PDO ? $pdo : null;
$cabinetId = current_cabinet_id();

$abonnement = null;
$cabinet = null;
$plan = null;
$factures = [];
$paiements = [];
$historique = [];

if ($db && $cabinetId !== null) {
    $stmt = $db->prepare('SELECT * FROM cabinets WHERE id = :id');
    $stmt->execute(['id' => $cabinetId]);
    $cabinet = $stmt->fetch() ?: null;

    $stmt = $db->prepare('SELECT * FROM abonnements WHERE cabinet_id = :cid ORDER BY date_fin DESC, id DESC');
    $stmt->execute(['cid' => $cabinetId]);
    $all = $stmt->fetchAll();
    $historique = $all;

    // L'abonnement de reference est celui que le bandeau retient : le plus
    // lointain parmi essai / actif / suspendu.
    foreach ($all as $row) {
        if (in_array((string) $row['statut'], ['essai', 'actif', 'suspendu'], true)) {
            $abonnement = $row;
            break;
        }
    }
    $abonnement ??= $all[0] ?? null;

    if ($abonnement !== null && $abonnement['plan_id'] !== null) {
        $stmt = $db->prepare('SELECT * FROM plans WHERE id = :id');
        $stmt->execute(['id' => (int) $abonnement['plan_id']]);
        $plan = $stmt->fetch() ?: null;
    }

    $stmt = $db->prepare('SELECT id, numero, date_emission, date_echeance, montant_ht, tva_pct, montant_ttc, statut, paiement_id
                          FROM factures WHERE cabinet_id = :cid
                          ORDER BY date_emission DESC, id DESC');
    $stmt->execute(['cid' => $cabinetId]);
    $factures = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT id, montant, devise, mode, reference, date_paiement, periode_debut, periode_fin, statut
                          FROM paiements WHERE cabinet_id = :cid
                          ORDER BY date_paiement DESC, id DESC');
    $stmt->execute(['cid' => $cabinetId]);
    $paiements = $stmt->fetchAll();
}

$state = current_abonnement_state($db);
$stateStatut = (string) ($state['statut'] ?? 'centre');
$jours = $state['jours_restants'] === null ? null : (int) $state['jours_restants'];

// Quotas : null ou 0 = illimite (convention des plans).
function quota_label(mixed $value): string
{
    $n = (int) ($value ?? 0);

    return $n > 0 ? (string) $n : 'Illimite';
}

$totalDu = 0.0;
foreach ($factures as $f) {
    if (facture_display_statut($f) !== 'annulee') {
        $totalDu += (float) $f['montant_ttc'];
    }
}
$totalRegle = 0.0;
foreach ($paiements as $p) {
    $totalRegle += (float) $p['montant'];
}
$resteDu = $totalDu - $totalRegle;
?>

<?php if ($cabinetId === null): ?>
    <section class="card">
        <h2 class="section-title">Compte interne du Centre</h2>
        <p class="table-empty">
            Cette page est reservee aux adherents rattaches a un cabinet.
            Les comptes du Centre n'ont pas d'abonnement et n'en ont pas besoin.
        </p>
        <div class="sub-form-actions">
            <a class="btn btn-back" href="<?= e(app_url('dashboard')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour au tableau de bord</a>
        </div>
    </section>
<?php else: ?>

<section class="stack">
    <?php if ($stateStatut === 'absent'): ?>
        <div class="flash flash-error">
            Votre cabinet n'a pas d'abonnement actif. Contactez le Centre de Domiciliation pour regulariser votre situation.
        </div>
    <?php elseif ($stateStatut === 'suspendu'): ?>
        <div class="flash flash-error">Votre abonnement est suspendu. Contactez le Centre de Domiciliation.</div>
    <?php elseif ($stateStatut === 'actif' && $jours !== null && $jours < 0): ?>
        <div class="flash flash-error">Votre abonnement est expire depuis le <?= e(format_date($abonnement['date_fin'] ?? null)) ?>. Contactez le Centre de Domiciliation.</div>
    <?php elseif ($stateStatut === 'essai' && $jours !== null && $jours <= 15): ?>
        <div class="flash flash-warning">Votre essai se termine dans <?= $jours ?> jour(s). Contactez le Centre de Domiciliation pour renouveler.</div>
    <?php elseif ($stateStatut === 'actif' && $jours !== null && $jours <= 30): ?>
        <div class="flash flash-warning">Votre abonnement expire le <?= e(format_date($abonnement['date_fin'] ?? null)) ?>. Contactez le Centre de Domiciliation pour le renouveler.</div>
    <?php endif; ?>

    <section class="grid two">
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0">Votre abonnement</h2>
                <?php if ($abonnement !== null): ?>
                    <span class="badge <?= e(abonnement_statut_tone(abonnement_display_statut($abonnement))) ?>"><?= e(abonnement_statut_label(abonnement_display_statut($abonnement))) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($abonnement === null): ?>
                <p class="table-empty">Aucun abonnement enregistre pour votre cabinet.</p>
            <?php else: ?>
                <div class="info-grid">
                    <div class="info-cell">
                        <span>Formule</span>
                        <strong><?= e($plan !== null ? (string) $plan['nom'] : 'Sur mesure') ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Statut</span>
                        <strong><?= e(abonnement_statut_label(abonnement_display_statut($abonnement))) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Date de debut</span>
                        <strong><?= e(format_date($abonnement['date_debut'] ?? null)) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Date de fin</span>
                        <strong><?= e(format_date($abonnement['date_fin'] ?? null)) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Jours restants</span>
                        <strong>
                            <?php if ($jours === null): ?>
                                -
                            <?php elseif ($jours < 0): ?>
                                Expire depuis <?= abs($jours) ?> jour(s)
                            <?php else: ?>
                                <?= $jours ?> jour(s)
                            <?php endif; ?>
                        </strong>
                    </div>
                    <div class="info-cell">
                        <span>Prix annuel</span>
                        <strong><?= e(number_format((float) ($abonnement['prix_annuel_negocie'] ?? 0), 2, ',', ' ')) ?> <?= e((string) $abonnement['devise']) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Renouvellement</span>
                        <strong><?= (int) $abonnement['auto_renew'] === 1 ? 'Automatique' : 'Manuel' ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        </article>

        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0">Votre cabinet</h2>
            </div>

            <?php if ($cabinet === null): ?>
                <p class="table-empty">Fiche cabinet introuvable.</p>
            <?php else: ?>
                <div class="info-grid">
                    <div class="info-cell">
                        <span>Code</span>
                        <strong><?= e((string) $cabinet['code']) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Nom</span>
                        <strong><?= e((string) $cabinet['nom']) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Raison sociale</span>
                        <strong><?= e((string) ($cabinet['raison_sociale'] ?? '-')) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Statut</span>
                        <strong><span class="badge <?= e(cabinet_statut_tone($cabinet['statut'] ?? null)) ?>"><?= e(cabinet_statut_label($cabinet['statut'] ?? null)) ?></span></strong>
                    </div>
                    <div class="info-cell">
                        <span>Contact</span>
                        <strong><?= e((string) ($cabinet['email'] ?? '-')) ?></strong>
                    </div>
                    <div class="info-cell">
                        <span>Telephone</span>
                        <strong><?= e((string) ($cabinet['telephone'] ?? '-')) ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <?php if ($plan !== null): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0">Quotas de la formule</h2>
                <span class="page-count"><?= e((string) $plan['code']) ?></span>
            </div>
            <div class="info-grid">
                <div class="info-cell">
                    <span>Utilisateurs</span>
                    <strong><?= e(quota_label($plan['max_utilisateurs'] ?? null)) ?></strong>
                </div>
                <div class="info-cell">
                    <span>Societes</span>
                    <strong><?= e(quota_label($plan['max_societes'] ?? null)) ?></strong>
                </div>
                <div class="info-cell">
                    <span>Dossiers</span>
                    <strong><?= e(quota_label($plan['max_dossiers'] ?? null)) ?></strong>
                </div>
                <div class="info-cell">
                    <span>Essai</span>
                    <strong><?= (int) $plan['trial_jours'] > 0 ? ((int) $plan['trial_jours']) . ' jour(s)' : 'Aucun' ?></strong>
                </div>
            </div>
            <?php if ((string) ($plan['description'] ?? '') !== ''): ?>
                <p class="text-muted"><?= e((string) $plan['description']) ?></p>
            <?php endif; ?>
        </article>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <h2 class="section-title" style="border:none;padding:0;margin:0">Factures</h2>
            <span class="page-count"><?= count($factures) ?> facture(s)</span>
        </div>

        <?php if (!$factures): ?>
            <p class="table-empty">Aucune facture pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="mes-factures">
                    <thead>
                        <tr>
                            <th data-col="numero">Numero</th>
                            <th data-col="emission">Emission</th>
                            <th data-col="echeance">Echeance</th>
                            <th data-col="ht">Montant HT</th>
                            <th data-col="tva">TVA</th>
                            <th data-col="ttc">Montant TTC</th>
                            <th data-col="statut">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($factures as $f): ?>
                        <tr>
                            <td><strong><?= e((string) $f['numero']) ?></strong></td>
                            <td><?= e(format_date($f['date_emission'] ?? null)) ?></td>
                            <td><?= e(format_date($f['date_echeance'] ?? null)) ?></td>
                            <td><?= e(number_format((float) $f['montant_ht'], 2, ',', ' ')) ?></td>
                            <td><?= e(number_format((float) $f['tva_pct'], 2, ',', ' ')) ?> %</td>
                            <td><strong><?= e(number_format((float) $f['montant_ttc'], 2, ',', ' ')) ?></strong></td>
                            <td><span class="badge <?= e(facture_statut_tone($f)) ?>"><?= e(facture_display_statut_label($f)) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" style="text-align:right">Total du</th>
                            <th><strong><?= e(number_format($totalDu, 2, ',', ' ')) ?></strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-header">
            <h2 class="section-title" style="border:none;padding:0;margin:0">Paiements</h2>
            <span class="page-count"><?= count($paiements) ?> paiement(s)</span>
        </div>

        <?php if (!$paiements): ?>
            <p class="table-empty">Aucun paiement enregistre.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="mes-paiements">
                    <thead>
                        <tr>
                            <th data-col="date">Date</th>
                            <th data-col="montant">Montant</th>
                            <th data-col="mode">Mode</th>
                            <th data-col="reference">Reference</th>
                            <th data-col="periode">Periode couverte</th>
                            <th data-col="statut">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paiements as $p): ?>
                        <tr>
                            <td><?= e(format_date($p['date_paiement'] ?? null)) ?></td>
                            <td><strong><?= e(number_format((float) $p['montant'], 2, ',', ' ')) ?> <?= e((string) $p['devise']) ?></strong></td>
                            <td><?= e(ucfirst((string) $p['mode'])) ?></td>
                            <td><?= e((string) ($p['reference'] ?? '-')) ?></td>
                            <td>
                                <?= e(format_date($p['periode_debut'] ?? null)) ?>
                                <?php if (($p['periode_fin'] ?? null) !== null): ?>
                                    → <?= e(format_date($p['periode_fin'] ?? null)) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e(ucfirst((string) $p['statut'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total regle</th>
                            <th><strong><?= e(number_format($totalRegle, 2, ',', ' ')) ?></strong></th>
                            <th colspan="4" style="text-align:right">Reste du : <strong><?= e(number_format($resteDu, 2, ',', ' ')) ?></strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <?php if (count($historique) > 1): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="section-title" style="border:none;padding:0;margin:0">Historique des abonnements</h2>
            </div>
            <div class="table-scroll">
                <table data-sortable data-table="mon-historique">
                    <thead>
                        <tr>
                            <th data-col="debut">Debut</th>
                            <th data-col="fin">Fin</th>
                            <th data-col="plan">Formule</th>
                            <th data-col="prix">Prix annuel</th>
                            <th data-col="statut">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $formuleParAbo = [];
                    foreach ($historique as $h) {
                        if ($h['plan_id'] === null) continue;
                        $pid = (int) $h['plan_id'];
                        if (!isset($formuleParAbo[$pid])) {
                            $ps = $db->prepare('SELECT nom FROM plans WHERE id = :id');
                            $ps->execute(['id' => $pid]);
                            $formuleParAbo[$pid] = (string) ($ps->fetchColumn() ?: 'Plan ' . $pid);
                        }
                    }
                    foreach ($historique as $h):
                        $pid = $h['plan_id'] === null ? null : (int) $h['plan_id'];
                        ?>
                        <tr>
                            <td><?= e(format_date($h['date_debut'] ?? null)) ?></td>
                            <td><?= e(format_date($h['date_fin'] ?? null)) ?></td>
                            <td><?= e($pid === null ? 'Sur mesure' : (string) ($formuleParAbo[$pid] ?? 'Sur mesure')) ?></td>
                            <td><?= e(number_format((float) ($h['prix_annuel_negocie'] ?? 0), 2, ',', ' ')) ?> <?= e((string) $h['devise']) ?></td>
                            <td><span class="badge <?= e(abonnement_statut_tone(abonnement_display_statut($h))) ?>"><?= e(abonnement_statut_label(abonnement_display_statut($h))) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    <?php endif; ?>
</section>
<?php endif; ?>
