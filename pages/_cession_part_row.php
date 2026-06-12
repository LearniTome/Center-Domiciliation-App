<div class="cession-part-row" style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-top:8px;background:var(--bg-card)">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <!-- Cédant -->
        <div>
            <strong style="font-size:0.85rem">Cedant</strong>
            <div class="field" style="margin-top:4px">
                <label style="font-size:0.75rem">Type</label>
                <select name="cedant_type[<?= $partIndex ?>]" class="cedant-type-select">
                    <option value="existant" <?= ($part['cedant_type'] ?? 'existant') === 'existant' ? 'selected' : '' ?>>Associe existant</option>
                    <option value="nouveau" <?= ($part['cedant_type'] ?? '') === 'nouveau' ? 'selected' : '' ?>>Nouvel associe</option>
                </select>
            </div>
            <div class="cedant-existing-fields" <?= ($part['cedant_type'] ?? 'existant') === 'nouveau' ? 'style="display:none"' : '' ?>>
                <div class="field">
                    <label style="font-size:0.75rem">Associe</label>
                    <select name="cedant_associe_id[<?= $partIndex ?>]">
                        <option value="0">-- Selectionnez --</option>
                        <?php foreach ($selectedAssocies as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= ($part['cedant_associe_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
                                <?= e($a['associe_nom_complet']) ?> (<?= (int) ($a['associe_parts'] ?? 0) ?> parts)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="cedant-new-fields" <?= ($part['cedant_type'] ?? 'existant') === 'nouveau' ? '' : 'style="display:none"' ?>>
                <div class="field">
                    <label style="font-size:0.75rem">Nom complet</label>
                    <input type="text" name="cedant_nom_complet[<?= $partIndex ?>]" value="<?= e($part['cedant_nom_complet'] ?? '') ?>" placeholder="Prenom NOM">
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">CIN</label>
                    <input type="text" name="cedant_cin[<?= $partIndex ?>]" value="<?= e($part['cedant_cin'] ?? '') ?>" placeholder="Numero CIN">
                </div>
            </div>
        </div>

        <!-- Cessionnaire -->
        <div>
            <strong style="font-size:0.85rem">Cessionnaire</strong>
            <div class="field" style="margin-top:4px">
                <label style="font-size:0.75rem">Type</label>
                <select name="cessionnaire_type[<?= $partIndex ?>]" class="cessionnaire-type-select">
                    <option value="existant" <?= ($part['cessionnaire_type'] ?? 'existant') === 'existant' ? 'selected' : '' ?>>Associe existant</option>
                    <option value="nouveau" <?= ($part['cessionnaire_type'] ?? '') === 'nouveau' ? 'selected' : '' ?>>Nouvel associe</option>
                </select>
            </div>
            <div class="cessionnaire-existing-fields" <?= ($part['cessionnaire_type'] ?? 'existant') === 'nouveau' ? 'style="display:none"' : '' ?>>
                <div class="field">
                    <label style="font-size:0.75rem">Associe</label>
                    <select name="cessionnaire_associe_id[<?= $partIndex ?>]">
                        <option value="0">-- Selectionnez --</option>
                        <?php foreach ($selectedAssocies as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= ($part['cessionnaire_associe_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
                                <?= e($a['associe_nom_complet']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="cessionnaire-new-fields" <?= ($part['cessionnaire_type'] ?? 'existant') === 'nouveau' ? '' : 'style="display:none"' ?>>
                <div class="field">
                    <label style="font-size:0.75rem">Civilite</label>
                    <select name="cessionnaire_civilite[<?= $partIndex ?>]">
                        <option value="M." <?= ($part['cessionnaire_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option>
                        <option value="Mme" <?= ($part['cessionnaire_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                        <option value="Mlle" <?= ($part['cessionnaire_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                    </select>
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">Nom complet</label>
                    <input type="text" name="cessionnaire_nom_complet[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_nom_complet'] ?? '') ?>" placeholder="Prenom NOM">
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">CIN</label>
                    <input type="text" name="cessionnaire_cin[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_cin'] ?? '') ?>" placeholder="Numero CIN">
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">Date naissance</label>
                    <input type="date" name="cessionnaire_date_naissance[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_date_naissance'] ?? '') ?>">
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">Lieu naissance</label>
                    <select name="cessionnaire_lieu_naissance[<?= $partIndex ?>]">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                            <option value="<?= e($ln) ?>" <?= ($part['cessionnaire_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">Nationalite</label>
                    <select name="cessionnaire_nationalite[<?= $partIndex ?>]">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($nationalitesOptions as $n): ?>
                            <option value="<?= e($n) ?>" <?= ($part['cessionnaire_nationalite'] ?? '') === $n ? 'selected' : '' ?>><?= e($n) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label style="font-size:0.75rem">Adresse</label>
                    <input type="text" name="cessionnaire_adresse[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_adresse'] ?? '') ?>" placeholder="Adresse complete">
                </div>
            </div>
        </div>
    </div>

    <!-- Parts & Prix -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px">
        <div class="field">
            <label style="font-size:0.75rem">Parts cedees</label>
            <input type="number" name="parts_cedees[<?= $partIndex ?>]" value="<?= e((string) ($part['parts_cedees'] ?? '')) ?>" min="1" required>
        </div>
        <div class="field">
            <label style="font-size:0.75rem">Prix unitaire (DH)</label>
            <input type="text" name="prix_unitaire[<?= $partIndex ?>]" value="<?= e((string) ($part['prix_unitaire'] ?? '')) ?>" placeholder="0,00">
        </div>
        <div class="field">
            <label style="font-size:0.75rem">Prix total (DH)</label>
            <input type="text" name="prix_total[<?= $partIndex ?>]" value="<?= e((string) ($part['prix_total'] ?? '')) ?>" placeholder="Calcule automatiquement">
        </div>
    </div>
</div>
