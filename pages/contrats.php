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
        $typeContratVal = field_value($_POST, 'type_contrat');
        $typeContratAutre = field_value($_POST, 'type_contrat_autre');
        if ($typeContratVal === 'autre' && $typeContratAutre !== '') {
            $typeContratVal = $typeContratAutre;
        }
        $stmt = $pdo->prepare('
            UPDATE contrats SET
                type_contrat = :type_contrat,
                date_contrat = :date_contrat, duree_contrat_mois = :duree_contrat_mois,
                type_contrat_domiciliation = :type_contrat_domiciliation,
                type_contrat_domiciliation_autre = :type_contrat_domiciliation_autre,
                date_debut = :date_debut, date_fin = :date_fin,
                taux_tva_pourcent = :taux_tva_pourcent,
                loyer_mensuel_ht = :loyer_mensuel_ht,
                loyer_mensuel_ttc = :loyer_mensuel_ttc,
                montant_total_ht_contrat = :montant_total_ht_contrat,
                type_renouvellement = :type_renouvellement,
                taux_tva_renouvellement_pourcent = :taux_tva_renouvellement_pourcent,
                loyer_mensuel_ht_renouvellement = :loyer_mensuel_ht_renouvellement,
                loyer_mensuel_renouvellement_ttc = :loyer_mensuel_renouvellement_ttc,
                montant_total_ht_renouvellement = :montant_total_ht_renouvellement,
                statut = :statut, notes = :notes
            WHERE id = :id
        ');
        $stmt->execute([
            'type_contrat' => $typeContratVal,
            'date_contrat' => field_value($_POST, 'date_contrat'),
            'duree_contrat_mois' => int_value($_POST, 'duree_contrat_mois'),
            'type_contrat_domiciliation' => field_value($_POST, 'type_contrat_domiciliation'),
            'type_contrat_domiciliation_autre' => field_value($_POST, 'type_contrat_domiciliation_autre'),
            'date_debut' => field_value($_POST, 'date_debut'),
            'date_fin' => field_value($_POST, 'date_fin'),
            'taux_tva_pourcent' => money_value($_POST, 'taux_tva_pourcent'),
            'loyer_mensuel_ht' => money_value($_POST, 'loyer_mensuel_ht'),
            'loyer_mensuel_ttc' => money_value($_POST, 'loyer_mensuel_ttc'),
            'montant_total_ht_contrat' => money_value($_POST, 'montant_total_ht_contrat'),
            'type_renouvellement' => field_value($_POST, 'type_renouvellement'),
            'taux_tva_renouvellement_pourcent' => money_value($_POST, 'taux_tva_renouvellement_pourcent'),
            'loyer_mensuel_ht_renouvellement' => money_value($_POST, 'loyer_mensuel_ht_renouvellement'),
            'loyer_mensuel_renouvellement_ttc' => money_value($_POST, 'loyer_mensuel_renouvellement_ttc'),
            'montant_total_ht_renouvellement' => money_value($_POST, 'montant_total_ht_renouvellement'),
            'statut' => field_value($_POST, 'statut', 'actif'),
            'notes' => field_value($_POST, 'notes'),
            'id' => $editId,
        ]);
        set_flash('success', 'Contrat mis a jour.');
        redirect_to('contrats');
    }
}

$contrats = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT contrats.*, societes.raison_sociale
        FROM contrats
        INNER JOIN societes ON societes.id = contrats.societe_id
        ORDER BY contrats.id DESC
    ')->fetchAll()
    : [];

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
            </div>
        </div>

        <?php if ($editRecord): ?>
            <form method="post" class="stack" style="margin-bottom:1rem">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <h3>Modifier le contrat</h3>
                <div class="form-grid">
                    <label class="field">
                        <span>Type de contrat</span>
                        <select name="type_contrat" style="flex:1">
                            <option value="">Selectionner</option>
                            <option value="Domiciliation commerciale" <?= (string) $editRecord['type_contrat'] === 'Domiciliation commerciale' ? 'selected' : '' ?>>Domiciliation commerciale</option>
                            <option value="Domiciliation professionnelle" <?= (string) $editRecord['type_contrat'] === 'Domiciliation professionnelle' ? 'selected' : '' ?>>Domiciliation professionnelle</option>
                            <option value="Domiciliation simple" <?= (string) $editRecord['type_contrat'] === 'Domiciliation simple' ? 'selected' : '' ?>>Domiciliation simple</option>
                            <option value="autre" <?= (string) $editRecord['type_contrat'] === 'autre' ? 'selected' : '' ?>>Autre (specifier)</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Type contrat domiciliation</span>
                        <select name="type_contrat_domiciliation">
                            <option value="">Selectionner</option>
                            <?php foreach (['Personne Morale', 'Personne Physique', 'Association', 'Fondation', 'Autres'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $editRecord['type_contrat_domiciliation'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Date du contrat</span>
                        <input type="date" name="date_contrat" value="<?= e((string) $editRecord['date_contrat']) ?>">
                    </label>
                    <label class="field">
                        <span>Duree (mois)</span>
                        <input type="number" name="duree_contrat_mois" value="<?= e((string) $editRecord['duree_contrat_mois']) ?>">
                    </label>
                    <label class="field">
                        <span>Date debut</span>
                        <input type="date" name="date_debut" value="<?= e((string) $editRecord['date_debut']) ?>">
                    </label>
                    <label class="field">
                        <span>Date fin</span>
                        <input type="date" name="date_fin" value="<?= e((string) $editRecord['date_fin']) ?>">
                    </label>
                    <label class="field">
                        <span>Statut</span>
                        <select name="statut">
                            <?php foreach (['actif', 'expire', 'brouillon'] as $statut): ?>
                                <option value="<?= e($statut) ?>" <?= (string) $editRecord['statut'] === $statut ? 'selected' : '' ?>><?= e(ucfirst($statut)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer HT (Mois)</span>
                        <input type="number" step="0.01" name="loyer_mensuel_ht" value="<?= e((string) $editRecord['loyer_mensuel_ht']) ?>">
                    </label>
                    <label class="field">
                        <span>TVA %</span>
                        <select name="taux_tva_pourcent">
                            <option value="">Selectionner</option>
                            <option value="7" <?= (string) $editRecord['taux_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                            <option value="10" <?= (string) $editRecord['taux_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                            <option value="14" <?= (string) $editRecord['taux_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                            <option value="20" <?= (string) $editRecord['taux_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer TTC (Mois)</span>
                        <input type="number" step="0.01" name="loyer_mensuel_ttc" value="<?= e((string) $editRecord['loyer_mensuel_ttc']) ?>">
                    </label>
                    <label class="field">
                        <span>Montant Total HT</span>
                        <input type="number" step="0.01" name="montant_total_ht_contrat" value="<?= e((string) $editRecord['montant_total_ht_contrat']) ?>">
                    </label>
                    <label class="field">
                        <span>Type renouvellement</span>
                        <select name="type_renouvellement">
                            <option value="">Selectionner</option>
                            <?php foreach (['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $editRecord['type_renouvellement'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer HT Renouvellement</span>
                        <input type="number" step="0.01" name="loyer_mensuel_ht_renouvellement" value="<?= e((string) $editRecord['loyer_mensuel_ht_renouvellement']) ?>">
                    </label>
                    <label class="field">
                        <span>TVA Renouvellement %</span>
                        <select name="taux_tva_renouvellement_pourcent">
                            <option value="">Selectionner</option>
                            <option value="7" <?= (string) $editRecord['taux_tva_renouvellement_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                            <option value="10" <?= (string) $editRecord['taux_tva_renouvellement_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                            <option value="14" <?= (string) $editRecord['taux_tva_renouvellement_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                            <option value="20" <?= (string) $editRecord['taux_tva_renouvellement_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Loyer TTC Renouvellement</span>
                        <input type="number" step="0.01" name="loyer_mensuel_renouvellement_ttc" value="<?= e((string) $editRecord['loyer_mensuel_renouvellement_ttc']) ?>">
                    </label>
                    <label class="field">
                        <span>Montant Total HT Renouvellement</span>
                        <input type="number" step="0.01" name="montant_total_ht_renouvellement" value="<?= e((string) $editRecord['montant_total_ht_renouvellement']) ?>">
                    </label>
                    <label class="field full">
                        <span>Notes</span>
                        <textarea name="notes"><?= e((string) $editRecord['notes']) ?></textarea>
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
                        <td><?= e($contrat['raison_sociale']) ?></td>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e($contrat['date_contrat'] ?? '-') ?></td>
                        <td><?= $contrat['duree_contrat_mois'] !== null ? e((string) $contrat['duree_contrat_mois']) : '-' ?></td>
                        <td><?= e($contrat['type_contrat_domiciliation'] ?? '-') ?></td>
                        <td><?= e($contrat['date_debut'] ?: '-') ?></td>
                        <td><?= e($contrat['date_fin'] ?: '-') ?></td>
                        <td><?= $contrat['loyer_mensuel_ttc'] !== null ? e(number_format((float) $contrat['loyer_mensuel_ttc'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['caution_montant'] !== null ? e(number_format((float) $contrat['caution_montant'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['taux_tva_pourcent'] !== null ? e(number_format((float) $contrat['taux_tva_pourcent'], 2, ',', ' ') . ' %') : '-' ?></td>
                        <td><?= $contrat['loyer_mensuel_ht'] !== null ? e(number_format((float) $contrat['loyer_mensuel_ht'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['montant_total_ht_contrat'] !== null ? e(number_format((float) $contrat['montant_total_ht_contrat'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= $contrat['montant_pack_demarrage_ttc'] !== null ? e(number_format((float) $contrat['montant_pack_demarrage_ttc'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= e($contrat['type_renouvellement'] ?? '-') ?></td>
                        <td><?= e($contrat['statut']) ?></td>
                        <td><?= e(substr($contrat['created_at'], 0, 10)) ?></td>
                        <td><?= e(substr($contrat['updated_at'], 0, 10)) ?></td>
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

