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
            <p>Dossier n° <?= e($societeData['societe_dossier'] ?: '-') ?> — Genere le 18/05/2026</p>
        </div>

        <div class="recap-section">
            <h3>Societe</h3>
            <div class="recap-grid">
                <div class="item"><span class="label">Raison sociale</span><span class="value"><?= e($societeData['societe_raison_sociale'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Forme juridique</span><span class="value"><?= e($societeData['societe_forme_juridique'] ?: '-') ?></span></div>
                <div class="item"><span class="label">ICE</span><span class="value"><?= e($societeData['societe_ice'] ?: '-') ?></span></div>

                <div class="item"><span class="label">Capital</span><span class="value"><?= e($societeData['societe_capital'] ?: '-') ?> DH</span></div>
                <div class="item"><span class="label">Part social</span><span class="value"><?= e($societeData['societe_part_social'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Valeur nominale</span><span class="value"><?= e($societeData['societe_valeur_nominale'] ?: '-') ?> DH</span></div>
                <div class="item full"><span class="label">Adresse</span><span class="value"><?= e($societeData['societe_adresse_siege'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Ville</span><span class="value"><?= e($societeData['societe_ville'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Tribunal</span><span class="value"><?= e($societeData['societe_tribunal'] ?: '-') ?><?= $currentTribunalType ? ' ('.e($currentTribunalType).')' : '' ?></span></div>
                <div class="item"><span class="label">Email</span><span class="value"><?= e($societeData['societe_email'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Telephone</span><span class="value"><?= e($societeData['societe_telephone'] ?: '-') ?></span></div>
                <?php if (($societeData['societe_type_generation'] ?? '') === 'creation'): ?>
                    <div class="item full"><span class="label">Activites (Statuts)</span><span class="value"><?= e(!empty($societeData['societe_activites_statuts']) ? (string) $societeData['societe_activites_statuts'] : '-') ?></span></div>
                <?php endif; ?>
                <div class="item full"><span class="label">Activites (OMPIC)</span><span class="value"><?= e(!empty($societeData['societe_activites_ompic']) ? fetch_activites_ompic_display($pdo ?? null, (string) $societeData['societe_activites_ompic']) : '-') ?></span></div>
                <div class="item"><span class="label">Type generation</span><span class="value"><?= e($societeData['societe_type_generation'] ?: '-') ?></span></div>
                <?php if (($societeData['societe_type_generation'] ?? '') === 'creation'): ?>
                    <div class="item"><span class="label">Procedure</span><span class="value"><?= e($societeData['societe_procedure_creation'] ?: '-') ?></span></div>
                    <div class="item"><span class="label">Mode depot</span><span class="value"><?= e($societeData['societe_mode_depot'] ?: '-') ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="recap-section">
            <h3>Associes (<?= count($associesData) ?>)</h3>
            <?php foreach ($associesData as $i => $associe): ?>
            <div class="recap-associe">
                <div class="associe-num">Associe n°<?= $i + 1 ?></div>
                <div class="recap-grid">
                    <div class="item"><span class="label">Nom complet</span><span class="value"><?= e($associe['associe_nom_complet'] ?: '-') ?></span></div>
                    <div class="item"><span class="label">CIN</span><span class="value"><?= e($associe['associe_cin'] ?: '-') ?></span></div>
                    <div class="item"><span class="label">Nationalite</span><span class="value"><?= e((string) ($associe['associe_nationalite'] ?? '-')) ?></span></div>
                    <div class="item"><span class="label">Date naissance</span><span class="value"><?= format_date($associe['associe_date_naissance'] ?? null) ?></span></div>
                    <div class="item"><span class="label">Lieu naissance</span><span class="value"><?= e($associe['associe_lieu_naissance'] ?: '-') ?></span></div>
                    <div class="item"><span class="label">Qualite</span><span class="value"><?= e($associe['associe_qualite'] ?: '-') ?></span></div>
                    <div class="item"><span class="label">Gerant</span><span class="value"><?= ((string) ($associe['associe_est_gerant'] ?? '0') === '1') ? 'Oui' : 'Non' ?></span></div>
                    <?php if ((string) ($associe['associe_est_gerant'] ?? '0') === '1'): ?>
                    <div class="item"><span class="label">Duree de gerance</span><span class="value"><?= e($associe['associe_duree_gerance'] ?: '-') ?></span></div>
                    <?php endif; ?>
                    <div class="item"><span class="label">Parts</span><span class="value"><?= e((string) ($associe['associe_parts'] ?? '-')) ?></span></div>
                    <div class="item"><span class="label">Capital detenu</span><span class="value"><?= e((string) ($associe['associe_capital_detenu'] ?? '-')) ?> DH</span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="recap-section">
            <h3>Contrat</h3>
            <div class="recap-grid">
                <div class="item"><span class="label">Type contrat</span><span class="value"><?= e($contratData['contrat_type'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Type domiciliation</span><span class="value"><?= e($contratData['contrat_type_domiciliation'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Date contrat</span><span class="value"><?= format_date($contratData['contrat_date'] ?? null) ?></span></div>
                <div class="item"><span class="label">Date debut</span><span class="value"><?= format_date($contratData['contrat_date_debut'] ?? null) ?></span></div>
                <div class="item"><span class="label">Date fin</span><span class="value"><?= format_date($contratData['contrat_date_fin'] ?? null) ?></span></div>
                <div class="item"><span class="label">Duree</span><span class="value"><?= e((string) ($contratData['contrat_duree_mois'] ?: '-')) ?> mois</span></div>
                <div class="item"><span class="label">Loyer HT</span><span class="value"><?= e($contratData['contrat_loyer_ht'] ?: '-') ?> DH</span></div>
                <div class="item"><span class="label">Loyer TTC/mois</span><span class="value"><?= e($contratData['contrat_loyer_ttc'] ?: '-') ?> DH</span></div>
                <div class="item"><span class="label">Total loyer</span><span class="value"><?= e($contratData['contrat_total_ht'] ?: '-') ?> DH</span></div>
                <div class="item"><span class="label">Renouvellement</span><span class="value"><?= e($contratData['contrat_type_renouvellement'] ?: '-') ?></span></div>
            </div>
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
