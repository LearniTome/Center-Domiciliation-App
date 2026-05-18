<?php

declare(strict_types=1);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRecord = $editId > 0 ? fetch_record($pdo ?? null, 'contrats', $editId) : null;

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM contrats WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Contrat supprime avec succes.');
        redirect_to('contrats');
    }

    if ($action === 'update' && $editRecord) {
        $typeContratVal = field_value($_POST, 'contrat_type');
        $typeContratAutre = field_value($_POST, 'type_contrat_autre');
        if ($typeContratVal === 'autre' && $typeContratAutre !== '') {
            $typeContratVal = $typeContratAutre;
        }
        $stmt = $pdo->prepare('
            UPDATE contrats SET
                contrat_type = :contrat_type,
                contrat_date = :contrat_date, contrat_duree_mois = :contrat_duree_mois,
                contrat_type_domiciliation = :contrat_type_domiciliation,
                contrat_type_domiciliation_autre = :contrat_type_domiciliation_autre,
                contrat_date_debut = :contrat_date_debut, contrat_date_fin = :contrat_date_fin,
                contrat_tva_pourcent = :contrat_tva_pourcent,
                contrat_loyer_ht = :contrat_loyer_ht,
                contrat_loyer_ttc = :contrat_loyer_ttc,
                contrat_total_ht = :contrat_total_ht,
                contrat_type_renouvellement = :contrat_type_renouvellement,
                contrat_renouv_tva_pourcent = :contrat_renouv_tva_pourcent,
                contrat_renouv_loyer_ht = :contrat_renouv_loyer_ht,
                contrat_renouv_loyer_ttc = :contrat_renouv_loyer_ttc,
                contrat_renouv_total_ht = :contrat_renouv_total_ht,
                contrat_statut = :contrat_statut, contrat_notes = :contrat_notes
            WHERE id = :id
        ');
        $stmt->execute([
            'contrat_type' => $typeContratVal,
            'contrat_date' => field_value($_POST, 'contrat_date'),
            'contrat_duree_mois' => int_value($_POST, 'contrat_duree_mois'),
            'contrat_type_domiciliation' => field_value($_POST, 'contrat_type_domiciliation'),
            'contrat_type_domiciliation_autre' => field_value($_POST, 'contrat_type_domiciliation_autre'),
            'contrat_date_debut' => field_value($_POST, 'contrat_date_debut'),
            'contrat_date_fin' => field_value($_POST, 'contrat_date_fin'),
            'contrat_tva_pourcent' => money_value($_POST, 'contrat_tva_pourcent'),
            'contrat_loyer_ht' => money_value($_POST, 'contrat_loyer_ht'),
            'contrat_loyer_ttc' => money_value($_POST, 'contrat_loyer_ttc'),
            'contrat_total_ht' => money_value($_POST, 'contrat_total_ht'),
            'contrat_type_renouvellement' => field_value($_POST, 'contrat_type_renouvellement'),
            'contrat_renouv_tva_pourcent' => money_value($_POST, 'contrat_renouv_tva_pourcent'),
            'contrat_renouv_loyer_ht' => money_value($_POST, 'contrat_renouv_loyer_ht'),
            'contrat_renouv_loyer_ttc' => money_value($_POST, 'contrat_renouv_loyer_ttc'),
            'contrat_renouv_total_ht' => money_value($_POST, 'contrat_renouv_total_ht'),
            'contrat_statut' => field_value($_POST, 'contrat_statut', 'actif'),
            'contrat_notes' => field_value($_POST, 'contrat_notes'),
            'id' => $editId,
        ]);
        set_flash('success', 'Contrat mis a jour.');
        redirect_to('contrats');
    }
}

$query = search_term();

if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $stmt = $pdo->prepare('
            SELECT contrats.*, societes.societe_raison_sociale
            FROM contrats
            INNER JOIN societes ON societes.id = contrats.societe_id
            WHERE societes.societe_raison_sociale LIKE :term
               OR contrats.contrat_type LIKE :term
               OR contrats.contrat_statut LIKE :term
            ORDER BY contrats.id DESC
        ');
        $stmt->execute(['term' => like_term($query)]);
        $contrats = $stmt->fetchAll();
    } else {
        $contrats = $pdo->query('
            SELECT contrats.*, societes.societe_raison_sociale
            FROM contrats
            INNER JOIN societes ON societes.id = contrats.societe_id
            ORDER BY contrats.id DESC
        ')->fetchAll();
    }

    if (($_GET['export'] ?? '') === 'csv') {
        $rows = array_map(static function (array $c): array {
            return [
                $c['id'],
                $c['societe_raison_sociale'],
                $c['contrat_type'],
                $c['contrat_date'],
                $c['contrat_duree_mois'],
                $c['contrat_type_domiciliation'],
                $c['contrat_date_debut'],
                $c['contrat_date_fin'],
                $c['contrat_loyer_ttc'],
                $c['contrat_tva_pourcent'],
                $c['contrat_loyer_ht'],
                $c['contrat_total_ht'],
                $c['contrat_type_renouvellement'],
                $c['contrat_statut'],
            ];
        }, $contrats);

        export_csv('contrats.csv', [
            'ID', 'Societe', 'Type contrat', 'Date contrat', 'Duree (mois)',
            'Type domiciliation', 'Date debut', 'Date fin', 'Loyer TTC/mois',
            'TVA %', 'Loyer HT/mois', 'Total HT', 'Renouvellement', 'Statut',
        ], $rows);
    }
} else {
    $contrats = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Contrats</h2>
                <p class="help-text"><?= count($contrats) ?> enregistrement(s)</p>
            </div>
            <div class="table-actions">
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="mdi mdi-table-column"></span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('contrats', ['export' => 'csv', 'q' => $query])) ?>"><span class="mdi mdi-download"></span> Exporter CSV</a>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="contrats">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par societe, type ou statut" value="<?= e($query) ?>">
                <button type="submit"><span class="mdi mdi-magnify"></span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('contrats')) ?>"><span class="mdi mdi-close"></span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($editRecord): ?>
            <form method="post" class="stack" style="margin-bottom:1rem">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <h3>Modifier le contrat</h3>
                <div class="form-grid">
                    <label class="field">
                        <span>Type de contrat</span>
                        <select name="contrat_type" style="flex:1">
                            <option value="">Selectionner</option>
                            <option value="Domiciliation commerciale" <?= (string) $editRecord['contrat_type'] === 'Domiciliation commerciale' ? 'selected' : '' ?>>Domiciliation commerciale</option>
                            <option value="Domiciliation professionnelle" <?= (string) $editRecord['contrat_type'] === 'Domiciliation professionnelle' ? 'selected' : '' ?>>Domiciliation professionnelle</option>
                            <option value="Domiciliation simple" <?= (string) $editRecord['contrat_type'] === 'Domiciliation simple' ? 'selected' : '' ?>>Domiciliation simple</option>
                            <option value="autre" <?= (string) $editRecord['contrat_type'] === 'autre' ? 'selected' : '' ?>>Autre (specifier)</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Type contrat domiciliation</span>
                        <select name="contrat_type_domiciliation">
                            <option value="">Selectionner</option>
                            <?php foreach (['Personne Morale', 'Personne Physique', 'Association', 'Fondation', 'Autres'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $editRecord['contrat_type_domiciliation'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Date du contrat</span>
                        <input type="date" name="contrat_date" value="<?= e((string) $editRecord['contrat_date']) ?>">
                    </label>
                    <label class="field">
                        <span>Duree (mois)</span>
                        <input type="number" name="contrat_duree_mois" value="<?= e((string) $editRecord['contrat_duree_mois']) ?>">
                    </label>
                    <label class="field">
                        <span>Date debut</span>
                        <input type="date" name="contrat_date_debut" value="<?= e((string) $editRecord['contrat_date_debut']) ?>">
                    </label>
                    <label class="field">
                        <span>Date fin</span>
                        <input type="date" name="contrat_date_fin" value="<?= e((string) $editRecord['contrat_date_fin']) ?>">
                    </label>
                    <label class="field">
                        <span>Statut</span>
                        <select name="contrat_statut">
                            <?php foreach (['actif', 'expire', 'brouillon'] as $statut): ?>
                                <option value="<?= e($statut) ?>" <?= (string) $editRecord['contrat_statut'] === $statut ? 'selected' : '' ?>><?= e(ucfirst($statut)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer HT (Mois)</span>
                        <input type="number" step="0.01" name="contrat_loyer_ht" value="<?= e((string) $editRecord['contrat_loyer_ht']) ?>">
                    </label>
                    <label class="field">
                        <span>TVA %</span>
                        <select name="contrat_tva_pourcent">
                            <option value="">Selectionner</option>
                            <option value="7" <?= (string) $editRecord['contrat_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                            <option value="10" <?= (string) $editRecord['contrat_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                            <option value="14" <?= (string) $editRecord['contrat_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                            <option value="20" <?= (string) $editRecord['contrat_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer TTC (Mois)</span>
                        <input type="number" step="0.01" name="contrat_loyer_ttc" value="<?= e((string) $editRecord['contrat_loyer_ttc']) ?>">
                    </label>
                    <label class="field">
                        <span>Montant Total HT</span>
                        <input type="number" step="0.01" name="contrat_total_ht" value="<?= e((string) $editRecord['contrat_total_ht']) ?>">
                    </label>
                    <label class="field">
                        <span>Type renouvellement</span>
                        <select name="contrat_type_renouvellement">
                            <option value="">Selectionner</option>
                            <?php foreach (['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $editRecord['contrat_type_renouvellement'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer HT Renouvellement</span>
                        <input type="number" step="0.01" name="contrat_renouv_loyer_ht" value="<?= e((string) $editRecord['contrat_renouv_loyer_ht']) ?>">
                    </label>
                    <label class="field">
                        <span>TVA Renouvellement %</span>
                        <select name="contrat_renouv_tva_pourcent">
                            <option value="">Selectionner</option>
                            <option value="7" <?= (string) $editRecord['contrat_renouv_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                            <option value="10" <?= (string) $editRecord['contrat_renouv_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                            <option value="14" <?= (string) $editRecord['contrat_renouv_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                            <option value="20" <?= (string) $editRecord['contrat_renouv_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer TTC Renouvellement</span>
                        <input type="number" step="0.01" name="contrat_renouv_loyer_ttc" value="<?= e((string) $editRecord['contrat_renouv_loyer_ttc']) ?>">
                    </label>
                    <label class="field">
                        <span>Montant Total HT Renouvellement</span>
                        <input type="number" step="0.01" name="contrat_renouv_total_ht" value="<?= e((string) $editRecord['contrat_renouv_total_ht']) ?>">
                    </label>
                    <label class="field full">
                        <span>Notes</span>
                        <textarea name="contrat_notes"><?= e((string) $editRecord['contrat_notes']) ?></textarea>
                    </label>
                </div>
                <div class="table-actions">
                    <a class="btn btn-secondary" href="<?= e(app_url('contrats')) ?>">Annuler</a>
                    <button class="btn btn-next" type="submit">Enregistrer</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if (!$contrats): ?>
            <p class="table-empty">Aucun contrat pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle>
                <thead>
                <tr>
                    <th data-col="societe">Societe</th>
                    <th data-col="type-contrat">Type contrat</th>
                    <th data-col="date-contrat">Date contrat</th>
                    <th data-col="duree">Duree (mois)</th>
                    <th data-col="type-domiciliation">Type domiciliation</th>
                    <th data-col="date-debut">Date debut</th>
                    <th data-col="date-fin">Date fin</th>
                    <th data-col="loyer-ttc">Loyer mensuel TTC</th>
                    <th data-col="caution">Caution</th>
                    <th data-col="tva">TVA %</th>
                    <th data-col="loyer-ht">Loyer mensuel HT</th>
                    <th data-col="total-ht">Total HT</th>
                    <th data-col="pack-demarrage">Pack demarrage TTC</th>
                    <th data-col="renouvellement">Renouvellement</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['societe_raison_sociale']) ?></td>
                        <td><?= e($contrat['contrat_type']) ?></td>
<td><?= e(format_date($contrat['contrat_date'] ?? null)) ?></td>
                        <td><?= e($contrat['type_domiciliation'] ?? '-') ?></td>
                        <td><?= e((string) ($contrat['contrat_duree_mois'] ?? '-')) ?></td>
                        <td><?= e(format_date($contrat['contrat_date_debut'] ?? null)) ?></td>
                        <td><?= e(format_date($contrat['contrat_date_fin'] ?? null)) ?></td>
                        <td><?= $contrat['contrat_loyer_ttc'] !== null ? e(number_format((float) $contrat['contrat_loyer_ttc'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['contrat_caution'] !== null ? e(number_format((float) $contrat['contrat_caution'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['contrat_tva_pourcent'] !== null ? e(number_format((float) $contrat['contrat_tva_pourcent'], 2, ',', ' ') . ' %') : '-' ?></td>
                        <td><?= $contrat['contrat_loyer_ht'] !== null ? e(number_format((float) $contrat['contrat_loyer_ht'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['contrat_total_ht'] !== null ? e(number_format((float) $contrat['contrat_total_ht'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['contrat_pack_montant_ttc'] !== null ? e(number_format((float) $contrat['contrat_pack_montant_ttc'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= e($contrat['contrat_type_renouvellement'] ?? '-') ?></td>
                        <td><?= e($contrat['contrat_statut']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $contrat['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $contrat['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('contrats', ['edit' => (int) $contrat['id']])) ?>" title="Modifier"><span class="mdi mdi-pencil"></span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $contrat['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce contrat ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>

