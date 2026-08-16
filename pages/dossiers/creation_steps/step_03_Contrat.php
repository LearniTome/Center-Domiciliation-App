<?php

declare(strict_types=1);

if (is_post() && $step === 3) {
    $navAction = $_POST['nav_action'] ?? 'next';

    $typeContratVal = field_value($_POST, 'contrat_type');
    $typeContratAutre = field_value($_POST, 'contrat_type_autre');
    if ($typeContratVal === 'autre' && $typeContratAutre !== '') {
        $typeContratVal = $typeContratAutre;
    }
    $contrat = [
        'contrat_type' => $typeContratVal,
        'contrat_date' => field_value($_POST, 'contrat_date'),
        'contrat_duree_mois' => field_value($_POST, 'contrat_duree_mois'),
        'contrat_type_domiciliation' => field_value($_POST, 'contrat_type_domiciliation'),
        'contrat_type_domiciliation_autre' => field_value($_POST, 'contrat_type_domiciliation_autre'),
        'contrat_date_debut' => date_value($_POST, 'contrat_date_debut'),
        'contrat_date_fin' => date_value($_POST, 'contrat_date_fin'),
        'contrat_tva_pourcent' => field_value($_POST, 'contrat_tva_pourcent'),
        'contrat_loyer_ht' => field_value($_POST, 'contrat_loyer_ht'),
        'contrat_loyer_ttc' => field_value($_POST, 'contrat_loyer_ttc'),
        'contrat_total_ht' => field_value($_POST, 'contrat_total_ht'),
        'contrat_type_renouvellement' => field_value($_POST, 'contrat_type_renouvellement'),
        'contrat_renouv_tva_pourcent' => field_value($_POST, 'contrat_renouv_tva_pourcent'),
        'contrat_renouv_loyer_ht' => field_value($_POST, 'contrat_renouv_loyer_ht'),
        'contrat_renouv_loyer_ttc' => field_value($_POST, 'contrat_renouv_loyer_ttc'),
        'contrat_renouv_total_ht' => field_value($_POST, 'contrat_renouv_total_ht'),
        'contrat_statut' => field_value($_POST, 'contrat_statut', 'actif'),
        'contrat_notes' => field_value($_POST, 'contrat_notes'),
    ];

    $wizard['contrat'] = $contrat;

    if ($navAction === 'ai_fill') {
        if (ClaudeService::isAvailable()) {
            $suggestions = ClaudeService::autoFill($contrat);
            $_SESSION['creation_wizard']['ai_suggestions'] = ['step3' => $suggestions];
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible. Configurez la cle API dans le fichier .env.");
        }
        redirect_to('creation', ['step' => 3]);
    }

    if ($navAction === 'back') {
        redirect_to('creation', ['step' => 2]);
    }

    if ($contrat['contrat_type'] === '') {
        set_flash('error', 'Le type de contrat est obligatoire.');
        redirect_to('creation', ['step' => 3]);
    }

    redirect_to('creation', ['step' => 4]);
}

if ($step === 3):
?>
<form method="post" class="stack">
    <?= csrf_input() ?>
    <input type="hidden" name="step" value="3">
    <?php if ($aiSuggestions && isset($aiSuggestions['step3'])): ?>
    <div class="flash flash-info" style="margin-bottom:8px">
        <span class="material-symbols-outlined">smart_toy</span>
        Suggestions IA disponibles. <button type="button" class="btn btn-info" style="padding:2px 10px;font-size:0.8rem" data-apply-ai-fill="<?= e(json_encode($aiSuggestions['step3'], JSON_UNESCAPED_UNICODE)) ?>"><span class="material-symbols-outlined">auto_fix</span> Appliquer les suggestions</button>
    </div>
    <?php endif; ?>
    <div class="form-grid">
        <h3 class="section-title">Type de contrat</h3>
        <label class="field">
            <span>Type de contrat</span>
            <div style="display:flex;gap:8px;align-items:center">
                <select name="contrat_type" style="flex:1" data-calc-trigger>
                    <option value="">Selectionner</option>
                    <option value="Domiciliation commerciale" <?= (string) $contratData['contrat_type'] === 'Domiciliation commerciale' ? 'selected' : '' ?>>Domiciliation commerciale</option>
                    <option value="Domiciliation professionnelle" <?= (string) $contratData['contrat_type'] === 'Domiciliation professionnelle' ? 'selected' : '' ?>>Domiciliation professionnelle</option>
                    <option value="Domiciliation simple" <?= (string) $contratData['contrat_type'] === 'Domiciliation simple' ? 'selected' : '' ?>>Domiciliation simple</option>
                    <option value="autre" <?= (string) $contratData['contrat_type'] === 'autre' ? 'selected' : '' ?>>Autre (specifier)</option>
                </select>
            </div>
        </label>
        <label class="field" data-show-if="contrat_type" data-show-value="autre">
            <span>Autre type</span>
            <input name="contrat_type_autre" value="<?= e((string) ($contratData['contrat_type_autre'] ?? '')) ?>">
        </label>
        <label class="field">
            <span>Date du contrat</span>
            <input type="date" name="contrat_date" placeholder="18/05/2026" value="<?= e((string) ($contratData['contrat_date'] ?: '2026-05-18')) ?>">
        </label>
        <label class="field">
            <span>Type contrat domiciliation</span>
            <select name="contrat_type_domiciliation">
                <option value="">Selectionner</option>
                <?php foreach (['Personne Morale', 'Personne Physique', 'Association', 'Fondation', 'Autres'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= (string) $contratData['contrat_type_domiciliation'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <h3 class="section-title">Periode</h3>
        <label class="field">
            <span>Date de debut</span>
            <input type="date" name="contrat_date_debut" data-date-debut placeholder="18/05/2026" value="<?= e((string) ($contratData['contrat_date_debut'] ?: '2026-05-18')) ?>">
        </label>
        <label class="field">
            <span>Duree (mois)</span>
            <input type="number" name="contrat_duree_mois" data-duree-mois value="<?= e((string) $contratData['contrat_duree_mois']) ?>">
        </label>
        <label class="field">
            <span>Date de fin</span>
            <input type="date" name="contrat_date_fin" data-date-fin placeholder="18/05/2026" value="<?= e((string) $contratData['contrat_date_fin']) ?>" readonly>
        </label>
        <label class="field">
            <span>Statut</span>
            <select name="contrat_statut">
                <?php foreach (['actif', 'expire', 'brouillon'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= (string) $contratData['contrat_statut'] === $st ? 'selected' : '' ?>>
                        <?= e(ucfirst($st)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <h3 class="section-title">Loyer (Initial)</h3>
        <label class="field">
            <span>Loyer HT (Mois)</span>
            <input type="number" step="0.01" name="contrat_loyer_ht" data-loyer-ht value="<?= e((string) $contratData['contrat_loyer_ht']) ?>">
        </label>
        <label class="field">
            <span>TVA %</span>
            <select name="contrat_tva_pourcent" data-tva-pourcent>
                <option value="">Selectionner</option>
                <option value="7" <?= (string) $contratData['contrat_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                <option value="10" <?= (string) $contratData['contrat_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                <option value="14" <?= (string) $contratData['contrat_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                <option value="20" <?= (string) $contratData['contrat_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
            </select>
        </label>
        <label class="field">
            <span>Loyer TTC (Mois)</span>
            <input type="text" name="contrat_loyer_ttc" data-loyer-ttc-mois value="<?= e((string) ($contratData['contrat_loyer_ttc'] ?? '')) ?>" readonly>
        </label>
        <label class="field">
            <span>Montant Total du Loyer</span>
            <input type="text" name="contrat_total_ht" data-montant-total-loyer value="<?= e((string) ($contratData['contrat_total_ht'] ?? '')) ?>" readonly>
        </label>

        <h3 class="section-title">Renouvellement</h3>
        <label class="field">
            <span>Type renouvellement</span>
            <select name="contrat_type_renouvellement">
                <option value="">Selectionner</option>
                <?php foreach (['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= (string) $contratData['contrat_type_renouvellement'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Loyer HT (Mois)</span>
            <input type="number" step="0.01" name="contrat_renouv_loyer_ht" data-loyer-ht-renouvellement value="<?= e((string) ($contratData['contrat_renouv_loyer_ht'] ?: '166.67')) ?>">
        </label>
        <label class="field">
            <span>TVA %</span>
            <select name="contrat_renouv_tva_pourcent" data-tva-renouvellement-pourcent>
                <option value="">Selectionner</option>
                <option value="7" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                <option value="10" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                <option value="14" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                <option value="20" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
            </select>
        </label>
        <label class="field">
            <span>Loyer TTC (Mois)</span>
            <input type="text" name="contrat_renouv_loyer_ttc" data-loyer-ttc-renouvellement-mois value="<?= e((string) ($contratData['contrat_renouv_loyer_ttc'] ?? '')) ?>" readonly>
        </label>
        <label class="field">
            <span>Montant Total du Loyer</span>
            <input type="text" name="contrat_renouv_total_ht" data-montant-total-renouvellement value="<?= e((string) ($contratData['contrat_renouv_total_ht'] ?? '')) ?>" readonly>
        </label>

        <label class="field full">
            <span>Notes</span>
            <textarea name="contrat_notes"><?= e((string) $contratData['contrat_notes']) ?></textarea>
        </label>
    </div>
    <div class="table-actions">
        <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
        <button class="btn btn-info" type="button" data-fill-test><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        <button class="btn btn-info" type="submit" name="nav_action" value="ai_fill"><span class="material-symbols-outlined">smart_toy</span> Remplir avec IA</button>
        <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>
<?php endif; ?>
