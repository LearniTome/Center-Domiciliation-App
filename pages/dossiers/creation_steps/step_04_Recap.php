<?php

declare(strict_types=1);

if (is_post() && $step === 4) {
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('creation', ['step' => 3]);
    }

    redirect_to('creation', ['step' => 5]);
}

if ($step === 4):
?>
<div class="stack">
    <div class="section-header">
        <div>
            <h2>Recapitulatif du dossier</h2>
            <p class="help-text">Verifiez les informations avant de generer les documents.</p>
        </div>
    </div>

    <div class="step-4-controls table-actions" style="margin-bottom:0.75rem">
        <button class="btn btn-info" onclick="window.print()"><span class="material-symbols-outlined">print</span> Imprimer</button>
        <button class="btn btn-info" id="btn-pdf-recap" data-forme="<?= e($societeData['societe_forme_juridique'] ?? '') ?>" data-raison="<?= e($societeData['societe_raison_sociale'] ?? '') ?>"><span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF</button>
        <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => 1])) ?>"><span class="material-symbols-outlined">edit</span> Modifier societe</a>
        <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => 2])) ?>"><span class="material-symbols-outlined">edit</span> Modifier associes</a>
        <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => 3])) ?>"><span class="material-symbols-outlined">edit</span> Modifier contrat</a>
    </div>

    <div class="recap-a4">
        <div class="recap-header">
            <h2>Recapitulatif du dossier de domiciliation</h2>
            <p>Dossier n° <?= e($societeData['societe_dossier_domiciliation_number'] ?: '-') ?> — Genere le 18/05/2026</p>
            <?php if (($societeData['societe_type_generation'] ?? '') === 'creation'): ?>
                <p>Dossier creation n° <?= e($societeData['societe_dossier_creation_number'] ?: '-') ?></p>
            <?php endif; ?>
        </div>

        <div class="recap-section">
            <h3>Societe</h3>
            <table class="recap-grid">
                <tr>
                    <td class="item"><span class="label">Raison sociale</span><span class="value"><?= e($societeData['societe_raison_sociale'] ?: '-') ?></span></td>
                    <td class="item"><span class="label">Forme juridique</span><span class="value"><?= e($societeData['societe_forme_juridique'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">ICE</span><span class="value"><?= e($societeData['societe_ice'] ?: '-') ?></span></td>
                    <td class="item"><span class="label">Capital</span><span class="value"><?= e($societeData['societe_capital'] ?: '-') ?> DH</span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Part social</span><span class="value"><?= e($societeData['societe_part_social'] ?: '-') ?></span></td>
                    <td class="item"><span class="label">Valeur nominale</span><span class="value"><?= e($societeData['societe_valeur_nominale'] ?: '-') ?> DH</span></td>
                </tr>
                <tr>
                    <td class="item full" colspan="2"><span class="label">Adresse</span><span class="value"><?= e($societeData['societe_adresse_siege'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Ville</span><span class="value"><?= e($societeData['societe_ville'] ?: '-') ?></span></td>
                    <td class="item"><span class="label">Tribunal</span><span class="value"><?= e($societeData['societe_tribunal'] ?: '-') ?><?= $currentTribunalType ? ' ('.e($currentTribunalType).')' : '' ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Email</span><span class="value"><?= e($societeData['societe_email'] ?: '-') ?></span></td>
                    <td class="item"><span class="label">Telephone</span><span class="value"><?= e($societeData['societe_telephone'] ?: '-') ?></span></td>
                </tr>
                <?php if (($societeData['societe_type_generation'] ?? '') === 'creation'): ?>
                <tr>
                    <td class="item full" colspan="2"><span class="label">Activites (Statuts)</span><span class="value"><?= e(!empty($societeData['societe_activites_statuts']) ? (string) $societeData['societe_activites_statuts'] : '-') ?></span></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="item full" colspan="2"><span class="label">Activites (OMPIC)</span><span class="value"><?= e(!empty($societeData['societe_activites_ompic']) ? fetch_activites_ompic_display($pdo ?? null, (string) $societeData['societe_activites_ompic']) : '-') ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Type generation</span><span class="value"><?= e($societeData['societe_type_generation'] ?: '-') ?></span></td>
                    <?php if (($societeData['societe_type_generation'] ?? '') === 'creation'): ?>
                    <td class="item"><span class="label">Procedure</span><span class="value"><?= e($societeData['societe_procedure_creation'] ?: '-') ?></span></td>
                    <?php endif; ?>
                </tr>
                <?php if (($societeData['societe_type_generation'] ?? '') === 'creation'): ?>
                <tr>
                    <td class="item"><span class="label">Mode depot</span><span class="value"><?= e($societeData['societe_mode_depot'] ?: '-') ?></span></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="recap-section">
            <h3>Associes (<?= count($associesData) ?>)</h3>
            <?php foreach ($associesData as $i => $associe): ?>
            <div class="recap-associe">
                <div class="associe-num">Associe n°<?= $i + 1 ?></div>
                <table class="recap-grid">
                    <tr>
                        <td class="item"><span class="label">Nom complet</span><span class="value"><?= e($associe['associe_nom_complet'] ?: '-') ?></span></td>
                        <td class="item"><span class="label">CIN</span><span class="value"><?= e($associe['associe_cin'] ?: '-') ?></span></td>
                    </tr>
                    <tr>
                        <td class="item"><span class="label">Nationalite</span><span class="value"><?= e((string) ($associe['associe_nationalite'] ?? '-')) ?></span></td>
                        <td class="item"><span class="label">Date naissance</span><span class="value"><?= format_date($associe['associe_date_naissance'] ?? null) ?></span></td>
                    </tr>
                    <tr>
                        <td class="item"><span class="label">Lieu naissance</span><span class="value"><?= e($associe['associe_lieu_naissance'] ?: '-') ?></span></td>
                        <td class="item"><span class="label">Qualite</span><span class="value"><?= e($associe['associe_qualite'] ?: '-') ?></span></td>
                    </tr>
                    <tr>
                        <td class="item"><span class="label">Gerant</span><span class="value"><?= ((string) ($associe['associe_est_gerant'] ?? '0') === '1') ? 'Oui' : 'Non' ?></span></td>
                        <?php if ((string) ($associe['associe_est_gerant'] ?? '0') === '1'): ?>
                        <td class="item"><span class="label">Duree de gerance</span><span class="value"><?= e($associe['associe_duree_gerance'] ?: '-') ?></span></td>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <td class="item"><span class="label">Parts</span><span class="value"><?= e((string) ($associe['associe_parts'] ?? '-')) ?></span></td>
                        <td class="item"><span class="label">Capital detenu</span><span class="value"><?= e((string) ($associe['associe_capital_detenu'] ?? '-')) ?> DH</span></td>
                    </tr>
                </table>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="recap-section">
            <h3>Contrat</h3>
            <table class="recap-grid">
                <tr>
                    <td class="item"><span class="label">Type contrat</span><span class="value"><?= e($contratData['contrat_type'] ?: '-') ?></span></td>
                    <td class="item"><span class="label">Type domiciliation</span><span class="value"><?= e($contratData['contrat_type_domiciliation'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Date contrat</span><span class="value"><?= format_date($contratData['contrat_date'] ?? null) ?></span></td>
                    <td class="item"><span class="label">Date debut</span><span class="value"><?= format_date($contratData['contrat_date_debut'] ?? null) ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Date fin</span><span class="value"><?= format_date($contratData['contrat_date_fin'] ?? null) ?></span></td>
                    <td class="item"><span class="label">Duree</span><span class="value"><?= e((string) ($contratData['contrat_duree_mois'] ?: '-')) ?> mois</span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Loyer HT</span><span class="value"><?= e($contratData['contrat_loyer_ht'] ?: '-') ?> DH</span></td>
                    <td class="item"><span class="label">Loyer TTC/mois</span><span class="value"><?= e($contratData['contrat_loyer_ttc'] ?: '-') ?> DH</span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Total loyer</span><span class="value"><?= e($contratData['contrat_total_ht'] ?: '-') ?> DH</span></td>
                    <td class="item"><span class="label">Renouvellement</span><span class="value"><?= e($contratData['contrat_type_renouvellement'] ?: '-') ?></span></td>
                </tr>
            </table>
        </div>
    </div>

    <form method="post" class="step-4-controls table-actions" style="margin-top:0.75rem">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="4">
        <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
        <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </form>
</div>
<?php endif; ?>
