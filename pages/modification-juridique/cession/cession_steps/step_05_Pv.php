<?php
declare(strict_types=1);

// Step 5: PV Cession preview
if (is_post() && $step === 5) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 4]);
    }
    redirect_to('cession', ['step' => 6]);
}

if ($step === 5):
$socData = $wizard['mode'] === 'existante' ? $selectedSociete : ($wizard['societe'] ?? []);
$totalParts = (int) ($socData['societe_part_social'] ?? 0);
$totalCapital = (float) ($socData['societe_capital'] ?? 0);
$valeurNominale = (float) ($socData['societe_valeur_nominale'] ?? 0);
$totalPrix = 0;
foreach ($wizard['parts'] as $p) {
    $totalPrix += (float) ($p['prix_total'] ?? 0);
}
$totalPartsCedees = 0;
foreach ($wizard['parts'] as $p) {
    $totalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
}
$firstPart = $wizard['parts'][0] ?? [];
$cedantNom = $firstPart['cedant_nom_complet'] ?? '';
$cessionnaireNom = $firstPart['cessionnaire_nom_complet'] ?? '';
$cessionnaireCivilite = $firstPart['cessionnaire_civilite'] ?? 'M.';
$cessionnaireNationalite = $firstPart['cessionnaire_nationalite'] ?? '';
$cessionnaireAdresse = $firstPart['cessionnaire_adresse'] ?? '';
?>
<div class="stack">
    <div class="section-header">
        <h2>Procès-Verbal de Cession</h2>
    </div>

    <div class="recap-a4" id="pv-content">
        <div class="recap-header">
            <h2>Procès-Verbal de l'Assemblée Générale Ordinaire</h2>
            <p>Société : <?= e($socData['societe_raison_sociale'] ?? '-') ?></p>
        </div>

        <div class="recap-section">
            <p><strong><?= e($socData['societe_forme_juridique'] ?? '') ?></strong></p>
            <div class="recap-grid">
                <div class="item"><span class="label">Dénomination sociale</span><span class="value"><?= e($socData['societe_raison_sociale'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Forme juridique</span><span class="value"><?= e($socData['societe_forme_juridique'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Capital social</span><span class="value"><?= e(number_format($totalCapital, 2, ',', ' ')) ?> DH</span></div>
                <div class="item"><span class="label">Siège social</span><span class="value"><?= e($socData['societe_adresse_siege'] ?: '-') ?></span></div>
                <div class="item"><span class="label">RC</span><span class="value"><?= e($socData['societe_rc'] ?: '-') ?> — Tribunal de <?= e($socData['societe_ville'] ?: '-') ?></span></div>
            </div>
        </div>

        <div class="recap-section">
            <h3>PROCÈS-VERBAL DE L'ASSEMBLÉE GÉNÉRALE ORDINAIRE DE L'ASSOCIÉ UNIQUE</h3>
            <div class="recap-grid">
                <div class="item"><span class="label">Date</span><span class="value"><?= e(format_date($wizard['cession_date'] ?? '')) ?></span></div>
                <div class="item"><span class="label">Lieu</span><span class="value"><?= e($socData['societe_adresse_siege'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Président de séance</span><span class="value"><?= e($cedantNom ?: '-') ?></span></div>
            </div>
        </div>

        <div class="recap-section">
            <p>L'an deux mille <?= date('Y') ?>, le <?= e(format_date($wizard['cession_date'] ?? '')) ?>, l'Associé Unique de la société <?= e($socData['societe_raison_sociale'] ?: '-') ?>, s'est réuni en Assemblée Générale Ordinaire au siège social.</p>
            <p>Après avoir constaté que toutes les dispositions légales et statutaires ont été respectées, l'Associé Unique examine l'ordre du jour suivant :</p>
        </div>

        <div class="recap-section">
            <h3>ORDRE DU JOUR</h3>
            <ol>
                <li>Cession de parts sociales par l'associé unique</li>
                <li>Agrément du ou des nouveaux associés</li>
                <li>Modification des statuts (article relatif aux associés et au capital social)</li>
                <li>Pouvoirs pour formalités</li>
            </ol>
        </div>

        <div class="recap-section">
            <h3>1. Cession de parts sociales</h3>
            <p>L'Associé Unique <strong><?= e($cedantNom) ?></strong>, titulaire de la totalité des parts sociales de la société, déclare céder à <strong><?= e($cessionnaireCivilite) ?> <?= e($cessionnaireNom) ?></strong>, de nationalité <?= e($cessionnaireNationalite ?: '-') ?>, demeurant à <?= e($cessionnaireAdresse ?: '-') ?>, <strong><?= $totalPartsCedees ?> parts sociales</strong> de <?= e(number_format($valeurNominale, 2, ',', ' ')) ?> DH chacune, pour un montant total de <strong><?= e(number_format($totalPrix, 2, ',', ' ')) ?> DH</strong>.</p>
            <p>L'Associé Unique accepte expressément cette cession et reconnaît que le prix de cession a été réglé entre les parties.</p>
        </div>

        <div class="recap-section">
            <h3>2. Agrément</h3>
            <p>L'Associé Unique agrée la cession susmentionnée et accepte l'entrée du nouvel associé dans le capital social de la société.</p>
        </div>

        <div class="recap-section">
            <h3>3. Modification des statuts</h3>
            <p>En conséquence de la cession, l'Associé Unique décide de modifier l'article 7 des statuts relatif à la répartition du capital social, lequel sera désormais rédigé comme suit :</p>
            <p><strong>Article 7 — Capital Social</strong></p>
            <p>Le capital social est fixé à la somme de <?= e(number_format($totalCapital, 2, ',', ' ')) ?> DH, divisé en <?= $totalParts ?> parts sociales de <?= e(number_format($valeurNominale, 2, ',', ' ')) ?> DH chacune, réparties comme suit :</p>
            <ul>
                <li><?= e($cessionnaireNom) ?> : <?= $totalPartsCedees ?> parts</li>
                <?php
                $partsRestantes = $totalParts - $totalPartsCedees;
                if ($partsRestantes > 0):
                ?>
                <li><?= e($cedantNom) ?> : <?= $partsRestantes ?> parts</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="recap-section">
            <h3>4. Pouvoirs</h3>
            <p>Tous pouvoirs sont donnés à <?= e($cedantNom) ?>, pour effectuer toutes formalités de dépôt et d'inscription modificative auprès du greffe du tribunal de commerce, ainsi que toutes autres démarches requises par la loi.</p>
        </div>

        <div class="recap-section">
            <h3>Clôture de la séance</h3>
            <p>Plus rien n'étant à l'ordre du jour, la séance est levée.</p>
            <div class="recap-grid" style="margin-top:1rem">
                <div class="item"><span class="label">Fait à</span><span class="value"><?= e($socData['societe_ville'] ?: '-') ?>, le <?= e(format_date($wizard['cession_date'] ?? '')) ?></span></div>
            </div>
            <p style="margin-top:1.5rem"><strong>L'Associé Unique</strong></p>
            <p><?= e($cedantNom) ?></p>
        </div>
    </div>

    <form method="post" class="table-actions" style="margin-top:0.75rem">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="5">
        <div style="display:flex;gap:8px;margin-right:auto">
            <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
        <button class="btn btn-info" type="button" onclick="window.print()"><span class="material-symbols-outlined">print</span> Imprimer</button>
        <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
        <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Réinitialiser l'assistant ?"><span class="material-symbols-outlined">restart_alt</span> Réinitialiser</a>
    </form>
</div>
<?php endif; ?>
