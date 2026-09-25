<?php

declare(strict_types=1);

/**
 * Page de suivi des contrats de domiciliation.
 *
 * Vue par defaut : les contrats actifs. Les autres onglets sont des liens
 * (pas de JavaScript), conformement au pattern utilise par la page
 * d'analyse de couverture.
 *
 * Les seuils viennent de config/defaults.json (section "seuils") afin que la
 * page de suivi, la fiche contrat et le tableau de bord partagent la meme
 * valeur.
 */
$seuils = contrat_seuils();
$seuilRenouvellement = $seuils['renouvellement'];
$seuilAlerte = $seuils['alerte'];
$seuilCritique = $seuils['critique'];

$vues = contrat_vues();
$vue = (string) ($_GET['vue'] ?? 'actifs');
if (!isset($vues[$vue])) {
    $vue = 'actifs';
}

$canView = has_permission('contrats.view');

$stats = ['actifs' => 0, 'renouvellement' => 0, 'echus' => 0, 'resilies' => 0];
$contrats = [];

if ($canView && ($pdo ?? null) instanceof PDO) {
    // Meme restriction que la liste des contrats : un utilisateur non admin
    // ne voit que les contrats des societes qu'il a creees.
    $filtreUser = contrat_user_filter(current_user());
    $stmt = $pdo->prepare('
        SELECT c.*, s.societe_raison_sociale,
               s.societe_dossier_domiciliation_number,
               s.societe_dossier_creation_number
          FROM contrats c
          INNER JOIN societes s ON s.id = c.societe_id
         WHERE 1 = 1
        ' . $filtreUser['sql'] . '
         ORDER BY c.id DESC
    ');
    $stmt->execute($filtreUser['params']);
    $toutes = $stmt->fetchAll();

    foreach ($toutes as $c) {
        $statut = (string) ($c['contrat_statut'] ?? '');
        $jours = contrat_jours_avant_echeance($c['contrat_date_fin'] ?? null);
        $c['jours_restants'] = $jours;

        if ($statut === 'resilie') {
            $stats['resilies']++;
        } elseif ($statut === 'expire') {
            $stats['echus']++;
        } elseif ($statut === 'actif') {
            if ($jours !== null && $jours < 0) {
                // Actif mais la date de fin est dépassée : à traiter comme
                // échu, sinon il disparaît de l'onglet "Actifs" sans contrepartie.
                $stats['echus']++;
            } else {
                $stats['actifs']++;
            }
            if ($jours !== null && $jours >= 0 && $jours <= $seuilRenouvellement) {
                $stats['renouvellement']++;
            }
        }

        $concorde = contrat_dans_vue($vue, $statut, $jours);

        if ($concorde) {
            $contrats[] = $c;
        }
    }
}

/**
 * Classe CSS du badge d'echeance. Un contrat sans date de fin n'a pas de
 * badge : l'absence d'information n'est pas une urgence.
 */
function echeance_classe(?int $jours, int $critique, int $alerte): string
{
    if ($jours === null) {
        return '';
    }
    if ($jours < 0 || $jours <= $critique) {
        return 'retard';
    }
    if ($jours <= $alerte) {
        return 'bientot';
    }

    return '';
}

$compteurs = [
    'actifs' => $stats['actifs'],
    'renouvellement' => $stats['renouvellement'],
    'echus' => $stats['echus'],
    'resilies' => $stats['resilies'],
];
$classesCompteur = [
    'actifs' => 'bg-success',
    'renouvellement' => 'bg-warning',
    'echus' => 'bg-danger',
    'resilies' => 'bg-secondary',
];
$filtreClasse = static fn(string $cle): string => 'btn btn-sm ' . ($vue === $cle ? 'btn-next' : 'btn-secondary');
?>
<section class="stats">
    <article class="stat">
        <span>Contrats actifs</span>
        <strong><?= (int) $stats['actifs'] ?></strong>
    </article>
    <article class="stat">
        <span>À renouveler (<?= (int) $seuilRenouvellement ?> j)</span>
        <strong class="text-danger"><?= (int) $stats['renouvellement'] ?></strong>
    </article>
    <article class="stat">
        <span>Échus non résolus</span>
        <strong class="text-danger"><?= (int) $stats['echus'] ?></strong>
    </article>
    <article class="stat">
        <span>Résiliés</span>
        <strong><?= (int) $stats['resilies'] ?></strong>
    </article>
</section>

<section class="card">
    <div class="section-header">
        <div class="analyse-filter-bar">
            <span class="analyse-filter-label">Filtrer :</span>
            <?php foreach ($vues as $cle => $libelle): ?>
                <a class="<?= e($filtreClasse($cle)) ?>" href="<?= e(app_url('contrats_suivi', ['vue' => $cle])) ?>">
                    <?= e($libelle) ?> <span class="badge <?= e($classesCompteur[$cle]) ?>"><?= (int) $compteurs[$cle] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (has_permission('contrats.export')): ?>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('contrats', ['export' => 'csv', 'vue' => $vue])) ?>">
                    <span class="material-symbols-outlined">download</span> Exporter CSV
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$canView): ?>
        <p class="table-empty">Vous n'êtes pas autorisé à consulter les contrats.</p>
    <?php elseif ($contrats === []): ?>
        <p class="table-empty">Aucun contrat dans cette vue.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="raison">Société</th>
                        <th data-col="dossier">N° dossier</th>
                        <th data-col="type">Type</th>
                        <th data-col="statut">Statut</th>
                        <th data-col="debut">Début</th>
                        <th data-col="fin">Fin de validité</th>
                        <th data-col="jours">Jours restants</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contrats as $c): ?>
                        <?php
                        $jours = $c['jours_restants'];
                        $statut = (string) ($c['contrat_statut'] ?? '');
                        $dossier = $c['societe_dossier_domiciliation_number']
                            ?: ($c['societe_dossier_creation_number'] ?: '—');
                        ?>
                        <tr>
                            <td><?= e((string) $c['societe_raison_sociale']) ?></td>
                            <td><?= e((string) $dossier) ?></td>
                            <td><?= e((string) ($c['contrat_type'] ?: '—')) ?></td>
                            <td>
                                <span class="statut-badge <?= e($statut) ?>"><?= e(contrat_statut_libelle($statut)) ?></span>
                            </td>
                            <td><?= e(format_date($c['contrat_date_debut'] ?? null)) ?></td>
                            <td><?= e(format_date($c['contrat_date_fin'] ?? null)) ?></td>
                            <td>
                                <?php if ($statut === 'resilie' || $jours === null): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <?php $classe = echeance_classe($jours, $seuilCritique, $seuilAlerte); ?>
                                    <span class="statut-badge <?= e($classe) ?>">
                                        <?= $jours > 0 ? $jours . ' j' : 'Dépassé de ' . abs($jours) . ' j' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <a class="btn-icon primary" href="<?= e(app_url('contrat', ['id' => (int) $c['id']])) ?>" title="Voir le contrat"><span class="material-symbols-outlined">visibility</span></a>
                                <a class="btn-icon info" href="<?= e(app_url('societe', ['id' => (int) $c['societe_id']])) ?>" title="Voir la société"><span class="material-symbols-outlined">domain</span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
