<div class="cession-part-row card" style="margin-top:12px" data-part="<?= $partIndex ?? 0 ?>">
    <div class="section-header" style="margin-bottom:8px">
        <strong>Ligne de cession #<span class="part-number"><?= ($partIndex ?? 0) + 1 ?></span></strong>
        <button type="button" class="btn-icon danger remove-part" title="Supprimer cette ligne">
            <span class="material-symbols-outlined">delete</span>
        </button>
    </div>

    <!-- Cedant (full width) -->
    <div class="field" style="margin-bottom:12px">
        <label>Cédant</label>
        <select name="cedant_type[<?= $partIndex ?? 0 ?>]" class="cedant-type" style="margin-bottom:8px;font-size:0.85rem">
            <option value="existant" <?= ($part['cedant_type'] ?? '') === 'existant' ? 'selected' : '' ?>>Associé existant</option>
            <option value="nouveau" <?= ($part['cedant_type'] ?? '') === 'nouveau' ? 'selected' : '' ?>>Nouveau cédant</option>
        </select>
        <div class="cedant-existing-fields" <?= ($part['cedant_type'] ?? '') === 'nouveau' ? 'style="display:none"' : '' ?>>
            <select name="cedant_associe_id[<?= $partIndex ?? 0 ?>]">
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($selectedAssocies as $assoc): ?>
                    <option value="<?= (int) $assoc['id'] ?>" <?= ($part['cedant_associe_id'] ?? 0) === (int) $assoc['id'] ? 'selected' : '' ?>>
                        <?= e($assoc['associe_nom_complet']) ?> (<?= (int) ($assoc['associe_parts'] ?? 0) ?> parts)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cedant-new-fields form-grid" <?= ($part['cedant_type'] ?? '') === 'nouveau' ? '' : 'style="display:none"' ?>>
            <input type="text" name="cedant_nom_complet[<?= $partIndex ?? 0 ?>]" placeholder="Nom complet" value="<?= e($part['cedant_nom_complet'] ?? '') ?>">
            <input type="text" name="cedant_cin[<?= $partIndex ?? 0 ?>]" placeholder="CIN" value="<?= e($part['cedant_cin'] ?? '') ?>">
        </div>
    </div>

    <!-- Cessionnaire (full width) -->
    <div class="field">
        <label>Cessionnaire</label>
        <select name="cessionnaire_type[<?= $partIndex ?? 0 ?>]" class="cessionnaire-type" style="margin-bottom:8px;font-size:0.85rem">
            <option value="existant" <?= ($part['cessionnaire_type'] ?? '') === 'existant' ? 'selected' : '' ?>>Associé existant</option>
            <option value="nouveau" <?= ($part['cessionnaire_type'] ?? '') === 'nouveau' ? 'selected' : '' ?>>Nouveau cessionnaire</option>
        </select>
        <div class="cessionnaire-existing-fields" <?= ($part['cessionnaire_type'] ?? '') === 'nouveau' ? 'style="display:none"' : '' ?>>
            <select name="cessionnaire_associe_id[<?= $partIndex ?? 0 ?>]">
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($selectedAssocies as $assoc): ?>
                    <option value="<?= (int) $assoc['id'] ?>" <?= ($part['cessionnaire_associe_id'] ?? 0) === (int) $assoc['id'] ? 'selected' : '' ?>>
                        <?= e($assoc['associe_nom_complet']) ?> (<?= (int) ($assoc['associe_parts'] ?? 0) ?> parts)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cessionnaire-new-fields" <?= ($part['cessionnaire_type'] ?? '') === 'nouveau' ? '' : 'style="display:none"' ?>>
            <div class="form-grid" style="margin-bottom:4px">
                <div style="display:flex;gap:4px">
                    <select name="cessionnaire_civilite[<?= $partIndex ?? 0 ?>]" style="flex:0 0 70px">
                        <option value="M." <?= ($part['cessionnaire_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option>
                        <option value="Mme" <?= ($part['cessionnaire_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                        <option value="Mlle" <?= ($part['cessionnaire_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                    </select>
                    <input type="text" name="cessionnaire_nom_complet[<?= $partIndex ?? 0 ?>]" placeholder="Nom complet" style="flex:1" value="<?= e($part['cessionnaire_nom_complet'] ?? '') ?>">
                </div>
                <input type="text" name="cessionnaire_cin[<?= $partIndex ?? 0 ?>]" placeholder="CIN" value="<?= e($part['cessionnaire_cin'] ?? '') ?>">
                <input type="date" name="cessionnaire_date_naissance[<?= $partIndex ?? 0 ?>]" value="<?= e($part['cessionnaire_date_naissance'] ?? '') ?>">
                <select name="cessionnaire_lieu_naissance[<?= $partIndex ?? 0 ?>]">
                    <option value="">-- Lieu naissance --</option>
                    <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                        <option value="<?= e($ln) ?>" <?= ($part['cessionnaire_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="cessionnaire_nationalite[<?= $partIndex ?? 0 ?>]">
                    <option value="">-- Nationalité --</option>
                    <?php foreach ($nationalitesOptions as $nat): ?>
                        <option value="<?= e($nat) ?>" <?= ($part['cessionnaire_nationalite'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <textarea name="cessionnaire_adresse[<?= $partIndex ?? 0 ?>]" placeholder="Adresse" rows="2"><?= e($part['cessionnaire_adresse'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Parts & Prix -->
    <div class="info-grid" style="grid-template-columns:80px 1fr 1fr 1fr auto;gap:12px;margin-top:12px;align-items:end">
        <div class="field">
            <label>%</label>
            <input type="text" name="pourcentage[<?= $partIndex ?? 0 ?>]" class="pourcentage-input" placeholder="0,00" value="<?= e(isset($part['pourcentage']) ? number_format((float) $part['pourcentage'], 2, ',', '') : '') ?>">
        </div>
        <div class="field">
            <label>Parts cédées</label>
            <input type="number" name="parts_cedees[<?= $partIndex ?? 0 ?>]" class="parts-cedees-input" value="<?= (int) ($part['parts_cedees'] ?? 0) ?>">
        </div>
        <div class="field">
            <label>Prix unitaire (DH)</label>
            <input type="text" name="prix_unitaire[<?= $partIndex ?? 0 ?>]" class="prix-unitaire-input" placeholder="0,00" value="<?= e(isset($part['prix_unitaire']) ? number_format((float) $part['prix_unitaire'], 2, ',', '') : '') ?>">
        </div>
        <div class="field">
            <label>Prix total (DH)</label>
            <input type="text" name="prix_total[<?= $partIndex ?? 0 ?>]" class="prix-total-input" placeholder="0,00" value="<?= e(isset($part['prix_total']) ? number_format((float) $part['prix_total'], 2, ',', '') : '') ?>">
        </div>
        <div class="field">
            <label>Gérant</label>
            <label style="display:flex;align-items:center;gap:4px;padding:6px 0">
                <input type="checkbox" name="nommer_gerant[<?= $partIndex ?? 0 ?>]" value="1" <?= !empty($part['nommer_gerant']) ? 'checked' : '' ?>>
                Nommer
            </label>
        </div>
    </div>
</div>
