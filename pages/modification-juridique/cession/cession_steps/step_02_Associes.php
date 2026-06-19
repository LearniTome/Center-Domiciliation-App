<?php
declare(strict_types=1);

// POST handler
if (is_post() && $step === 2) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 1]);
    }

    $wizard['associes'] = [];
    $noms = $_POST['associe_nom_complet'] ?? [];
    foreach ($noms as $i => $nom) {
        $nom = trim((string) $nom);
        if ($nom === '') continue;
        $wizard['associes'][] = [
            'associe_civilite' => trim((string) ($_POST['associe_civilite'][$i] ?? 'M.')),
            'associe_nom_complet' => $nom,
            'associe_cin' => trim((string) ($_POST['associe_cin'][$i] ?? '')),
            'associe_date_naissance' => trim((string) ($_POST['associe_date_naissance'][$i] ?? '')),
            'associe_lieu_naissance' => trim((string) ($_POST['associe_lieu_naissance'][$i] ?? '')),
            'associe_nationalite' => trim((string) ($_POST['associe_nationalite'][$i] ?? '')),
            'associe_adresse' => trim((string) ($_POST['associe_adresse'][$i] ?? '')),
            'associe_telephone' => trim((string) ($_POST['associe_telephone'][$i] ?? '')),
            'associe_email' => trim((string) ($_POST['associe_email'][$i] ?? '')),
            'associe_qualite' => trim((string) ($_POST['associe_qualite'][$i] ?? 'Gerant')),
            'associe_parts' => (string) ($_POST['associe_parts'][$i] ?? ''),
            'associe_capital_detenu' => (string) ($_POST['associe_capital_detenu'][$i] ?? ''),
            'associe_est_gerant' => ($_POST['associe_est_gerant'][$i] ?? '0') === '1' ? '1' : '0',
        ];
    }

    if (empty($wizard['associes'])) {
        set_flash('error', 'Ajoutez au moins un associe.');
        redirect_to('cession', ['step' => 2]);
    }

    $totalParts = 0;
    $totalCapital = 0.0;
    foreach ($wizard['associes'] as $a) {
        $totalParts += (int) ($a['associe_parts'] ?? 0);
        $totalCapital += (float) str_replace(',', '.', (string) ($a['associe_capital_detenu'] ?? '0'));
    }
    $partSocial = (int) ($wizard['societe']['societe_part_social'] ?? 0);
    $societeCapital = (float) str_replace(',', '.', (string) ($wizard['societe']['societe_capital'] ?? '0'));

    $hasError = false;
    if ($partSocial > 0 && $totalParts !== $partSocial) {
        $hasError = true;
        $_SESSION['_parts_error'] = true;
    }
    if ($societeCapital > 0 && abs($totalCapital - $societeCapital) > 0.01) {
        $hasError = true;
        $_SESSION['_capital_error'] = true;
    }
    if ($hasError) {
        $parts = $partSocial > 0 ? " parts ($totalParts/$partSocial)" : '';
        $cap = $societeCapital > 0 ? ' capital ('.number_format($totalCapital, 2, ',', ' ').'/' . number_format($societeCapital, 2, ',', ' ') . ' DH)' : '';
        set_flash('error', "Verifiez les associés : le total des$parts$cap ne correspond pas a la societe.");
        redirect_to('cession', ['step' => 2]);
    }

    redirect_to('cession', ['step' => 3]);
}

// HTML view
if ($step === 2):
?>
<form method="post" class="stack" id="associe-step-form">
    <?= csrf_input() ?>
    <input type="hidden" name="nav_action" value="next">

    <?php
    $hasPartsError = !empty($_SESSION['_parts_error']);
    $hasCapitalError = !empty($_SESSION['_capital_error']);
    if ($hasPartsError || $hasCapitalError):
        $msgs = [];
        if ($hasPartsError) $msgs[] = 'Le total des parts doit etre ' . ((int) ($wizard['societe']['societe_part_social'] ?? 0));
        if ($hasCapitalError) $msgs[] = 'Le total du capital doit etre ' . number_format((float) ($wizard['societe']['societe_capital'] ?? 0), 2, ',', ' ') . ' DH';
    ?>
    <article class="card" style="border-color:var(--danger);margin-bottom:12px">
        <p style="color:var(--danger);margin:0"><?= e(implode(' — ', $msgs)) ?>.</p>
    </article>
    <?php unset($_SESSION['_parts_error'], $_SESSION['_capital_error']); endif; ?>

    <div class="section-header">
        <h2>Associes</h2>
        <div style="display:flex;align-items:center;gap:8px;margin-left:auto">
            <button class="btn btn-info" type="button" data-fill-cession="2"><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
            <button class="btn btn-info" type="button" id="add-associe-step2"><span class="material-symbols-outlined">playlist_add</span> Ajouter un associe</button>
        </div>
    </div>

    <div class="stack" id="cession-associes-container">
        <?php if (!empty($selectedAssocies) && $wizard['mode'] === 'existante'): ?>
        <article class="card">
            <div class="section-header">
                <div><h3>Associes existants</h3></div>
            </div>
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="associe">Associe</th>
                        <th data-col="cin">CIN</th>
                        <th data-col="parts">Parts</th>
                        <th data-col="capital">Capital</th>
                        <th data-col="qualite">Qualite</th>
                        <th>Gerant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedAssocies as $a): ?>
                    <tr>
                        <td><?= e($a['associe_nom_complet']) ?></td>
                        <td><?= e($a['associe_cin'] ?? '-') ?></td>
                        <td><?= (int) ($a['associe_parts'] ?? 0) ?></td>
                        <td><?= e(number_format((float) ($a['associe_capital_detenu'] ?? 0), 2, ',', ' ') . ' DH') ?></td>
                        <td><?= e($a['associe_qualite'] ?? '-') ?></td>
                        <td><?= ((string) ($a['associe_est_gerant'] ?? '0') === '1') ? '<span class="material-symbols-outlined" style="color:var(--success)">verified</span>' : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </article>
        <?php endif; ?>

        <?php
        $savedAssocies = $wizard['associes'] ?? [];
        if (!empty($savedAssocies)): ?>
            <?php foreach ($savedAssocies as $ai => $assoc): ?>
            <div class="associe-card" data-associe-item>
                <div class="associe-card-header">
                    <strong data-associe-title>Associe <?= $ai + 1 ?></strong>
                    <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                </div>
                <div class="form-grid">
                    <h3 class="section-title">Identite</h3>
                    <label class="field">
                        <span>Civilite</span>
                        <select name="associe_civilite[<?= $ai ?>]">
                            <option value="M." <?= ($assoc['associe_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option>
                            <option value="Mme" <?= ($assoc['associe_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                            <option value="Mlle" <?= ($assoc['associe_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nom complet *</span>
                        <input type="text" name="associe_nom_complet[<?= $ai ?>]" required value="<?= e($assoc['associe_nom_complet'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>CIN</span>
                        <input type="text" name="associe_cin[<?= $ai ?>]" value="<?= e($assoc['associe_cin'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Date de naissance</span>
                        <input type="date" name="associe_date_naissance[<?= $ai ?>]" value="<?= e($assoc['associe_date_naissance'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Lieu de naissance</span>
                        <select name="associe_lieu_naissance[<?= $ai ?>]">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                <option value="<?= e($ln) ?>" <?= ($assoc['associe_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nationalite</span>
                        <select name="associe_nationalite[<?= $ai ?>]">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($nationalitesOptions as $nat): ?>
                                <option value="<?= e($nat) ?>" <?= ($assoc['associe_nationalite'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Telephone</span>
                        <input type="text" name="associe_telephone[<?= $ai ?>]" value="<?= e($assoc['associe_telephone'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="associe_email[<?= $ai ?>]" value="<?= e($assoc['associe_email'] ?? '') ?>">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea name="associe_adresse[<?= $ai ?>]" rows="2"><?= e($assoc['associe_adresse'] ?? '') ?></textarea>
                    </label>
                    <h3 class="section-title">Participation</h3>
                    <label class="field">
                        <span>Qualite</span>
                        <select name="associe_qualite[<?= $ai ?>]">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                <option value="<?= e($qa) ?>" <?= ($assoc['associe_qualite'] ?? '') === $qa ? 'selected' : '' ?>><?= e($qa) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php $hasCapitalError = !empty($_SESSION['_capital_error']); ?>
                    <label class="field <?= ($hasPartsError || $hasCapitalError) ? 'field-error' : '' ?>">
                        <span>Nombre de parts</span>
                        <input type="number" name="associe_parts[<?= $ai ?>]" value="<?= e($assoc['associe_parts'] ?? '') ?>" placeholder="100" class="<?= ($hasPartsError || $hasCapitalError) ? 'input-error' : '' ?>">
                    </label>
                    <label class="field <?= $hasCapitalError ? 'field-error' : '' ?>">
                        <span>Capital detenu (DH)</span>
                        <input type="number" step="0.01" name="associe_capital_detenu[<?= $ai ?>]" value="<?= e($assoc['associe_capital_detenu'] ?? '') ?>" placeholder="50000" class="<?= $hasCapitalError ? 'input-error' : '' ?>">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select name="associe_est_gerant[<?= $ai ?>]">
                            <option value="0" <?= ($assoc['associe_est_gerant'] ?? '0') === '0' ? 'selected' : '' ?>>Non</option>
                            <option value="1" <?= ($assoc['associe_est_gerant'] ?? '0') === '1' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="associe-card" data-associe-item>
                <div class="associe-card-header">
                    <strong data-associe-title>Associe 1</strong>
                    <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                </div>
                <div class="form-grid">
                    <h3 class="section-title">Identite</h3>
                    <label class="field">
                        <span>Civilite</span>
                        <select name="associe_civilite[0]">
                            <option value="M." selected>M.</option>
                            <option value="Mme">Mme</option>
                            <option value="Mlle">Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nom complet *</span>
                        <input type="text" name="associe_nom_complet[0]" required>
                    </label>
                    <label class="field">
                        <span>CIN</span>
                        <input type="text" name="associe_cin[0]">
                    </label>
                    <label class="field">
                        <span>Date de naissance</span>
                        <input type="date" name="associe_date_naissance[0]">
                    </label>
                    <label class="field">
                        <span>Lieu de naissance</span>
                        <select name="associe_lieu_naissance[0]">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                <option value="<?= e($ln) ?>"><?= e($ln) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nationalite</span>
                        <select name="associe_nationalite[0]">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($nationalitesOptions as $nat): ?>
                                <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Telephone</span>
                        <input type="text" name="associe_telephone[0]">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="associe_email[0]">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea name="associe_adresse[0]" rows="2"></textarea>
                    </label>
                    <h3 class="section-title">Participation</h3>
                    <label class="field">
                        <span>Qualite</span>
                        <select name="associe_qualite[0]">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                <option value="<?= e($qa) ?>"><?= e($qa) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nombre de parts</span>
                        <input type="number" name="associe_parts[0]" placeholder="100">
                    </label>
                    <label class="field">
                        <span>Capital detenu (DH)</span>
                        <input type="number" step="0.01" name="associe_capital_detenu[0]" placeholder="50000">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select name="associe_est_gerant[0]">
                            <option value="0" selected>Non</option>
                            <option value="1">Oui</option>
                        </select>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <template id="associe-step2-template">
        <div class="associe-card" data-associe-item>
            <div class="associe-card-header">
                <strong data-associe-title>Associe</strong>
                <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
            </div>
            <div class="form-grid">
                <h3 class="section-title">Identite</h3>
                <label class="field">
                    <span>Civilite</span>
                    <select data-field-name="associe_civilite">
                        <option value="M.">M.</option>
                        <option value="Mme">Mme</option>
                        <option value="Mlle">Mlle</option>
                    </select>
                </label>
                <label class="field">
                    <span>Nom complet</span>
                    <input data-field-name="associe_nom_complet" required>
                </label>
                <label class="field">
                    <span>CIN</span>
                    <input data-field-name="associe_cin">
                </label>
                <label class="field">
                    <span>Date de naissance</span>
                    <input data-field-name="associe_date_naissance" type="date">
                </label>
                <label class="field">
                    <span>Lieu de naissance</span>
                    <select data-field-name="associe_lieu_naissance">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                        <option value="<?= e($ln) ?>"><?= e($ln) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Nationalite</span>
                    <select data-field-name="associe_nationalite">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($nationalitesOptions as $nat): ?>
                        <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <h3 class="section-title">Contact</h3>
                <label class="field">
                    <span>Telephone</span>
                    <input data-field-name="associe_telephone">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input data-field-name="associe_email" type="email">
                </label>
                <label class="field full">
                    <span>Adresse</span>
                    <textarea data-field-name="associe_adresse" rows="2"></textarea>
                </label>
                <h3 class="section-title">Participation</h3>
                <label class="field">
                    <span>Qualite</span>
                    <select data-field-name="associe_qualite">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($qualitesAssocieOptions as $qa): ?>
                        <option value="<?= e($qa) ?>"><?= e($qa) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Nombre de parts</span>
                    <input data-field-name="associe_parts" type="number" placeholder="100">
                </label>
                <label class="field">
                    <span>Capital detenu (DH)</span>
                    <input data-field-name="associe_capital_detenu" type="number" step="0.01" placeholder="50000">
                </label>
                <label class="field">
                    <span>Gerant</span>
                    <select data-field-name="associe_est_gerant">
                        <option value="0" selected>Non</option>
                        <option value="1">Oui</option>
                    </select>
                </label>
            </div>
        </div>
    </template>

    <div class="dash-metrics" style="margin-top:12px">
        <div class="dash-metric">
            <div class="dm-icon dm-icon-soc"><span class="material-symbols-outlined">token</span></div>
            <div class="dm-body">
                <span class="dm-label">Parts societe</span>
                <strong class="dm-value"><?= (int) ($wizard['societe']['societe_part_social'] ?? 0) ?></strong>
            </div>
        </div>
        <div class="dash-metric">
            <div class="dm-icon dm-icon-ctr"><span class="material-symbols-outlined">account_balance</span></div>
            <div class="dm-body">
                <span class="dm-label">Capital societe</span>
                <strong class="dm-value"><?= e(number_format((float) ($wizard['societe']['societe_capital'] ?? 0), 2, ',', ' ')) ?> DH</strong>
            </div>
        </div>
        <div class="dash-metric" id="total-assoc-metric" style="position:relative">
            <div class="dm-icon dm-icon-doc"><span class="material-symbols-outlined">group</span></div>
            <div class="dm-body">
                <span class="dm-label">Total associes</span>
                <strong class="dm-value">Parts: <span id="total-parts-display">0</span> / Capital: <span id="total-capital-display">0,00</span> DH</strong>
                <span id="parts-status" style="font-size:0.72rem;font-weight:500">&nbsp;</span>
            </div>
        </div>
    </div>

    <div class="footer-actions" style="margin-top:12px">
        <div style="display:flex;gap:8px;margin-right:auto">
            <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
        <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
        <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
    </div>
</form>

<script>
(function(){
    'use strict';

    // Helper functions for auto-fill
    function randFrom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
    function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function randDate(start, end) {
        var d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    var hommes = ['ALAOUI', 'BENALI', 'CHERKAOUI', 'DAHMANI', 'EL FASSI', 'FAHIMI', 'GHAZI', 'HAMMADI'];
    var prenoms = ['Mohamed', 'Ahmed', 'Hassan', 'Omar', 'Youssef', 'Karim', 'Mehdi', 'Said'];

    // Parts + Capital total tracking
    var totalPartsExpected = <?= (int) ($wizard['societe']['societe_part_social'] ?? 0) ?>;
    var totalCapitalExpected = <?= (float) ($wizard['societe']['societe_capital'] ?? 0) ?>;
    var totalPartsDisplay = document.getElementById('total-parts-display');
    var totalCapitalDisplay = document.getElementById('total-capital-display');
    var partsStatus = document.getElementById('parts-status');

    function updateTotals() {
        var partInputs = document.querySelectorAll('[name^="associe_parts"]');
        var capInputs = document.querySelectorAll('[name^="associe_capital_detenu"]');
        var totalP = 0, totalC = 0;
        partInputs.forEach(function(inp) { totalP += parseInt(inp.value) || 0; });
        capInputs.forEach(function(inp) { totalC += parseFloat((inp.value || '0').replace(',', '.')) || 0; });
        if (totalPartsDisplay) totalPartsDisplay.textContent = totalP;
        if (totalCapitalDisplay) totalCapitalDisplay.textContent = totalC.toFixed(2).replace('.', ',');
        if (partsStatus) {
            var ok = true;
            if (totalPartsExpected > 0 && totalP !== totalPartsExpected) ok = false;
            if (totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01) ok = false;
            if (ok) {
                partsStatus.innerHTML = '✓ Le total correspond';
                partsStatus.style.color = 'var(--success)';
            } else {
                var msgs = [];
                if (totalPartsExpected > 0 && totalP !== totalPartsExpected) msgs.push('parts: ' + totalP + '/' + totalPartsExpected);
                if (totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01) msgs.push('capital: ' + totalC.toFixed(0) + '/' + totalCapitalExpected.toFixed(0));
                partsStatus.innerHTML = '✗ ' + msgs.join(', ');
                partsStatus.style.color = 'var(--danger)';
            }
        }
        // Highlight error fields
        partInputs.forEach(function(f) {
            var hasErr = totalPartsExpected > 0 && totalP !== totalPartsExpected;
            f.classList.toggle('input-error', hasErr);
            var label = f.closest('.field');
            if (label) label.classList.toggle('field-error', hasErr);
        });
        capInputs.forEach(function(f) {
            var hasErr = totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01;
            f.classList.toggle('input-error', hasErr);
            var label = f.closest('.field');
            if (label) label.classList.toggle('field-error', hasErr);
        });
    }

    // Auto-calc capital from parts: capital = parts × (capital_societe / parts_societe)
    function recalcCapitalFromParts(partsInput) {
        if (!totalPartsExpected || !totalCapitalExpected) return;
        var card = partsInput.closest('[data-associe-item]');
        if (!card) return;
        var capInput = card.querySelector('[name^="associe_capital_detenu"]');
        if (!capInput) return;
        var parts = parseFloat(partsInput.value.replace(',', '.')) || 0;
        if (parts > 0) {
            capInput.value = ((parts / totalPartsExpected) * totalCapitalExpected).toFixed(2);
        }
    }

    // Listen for changes on parts/capital inputs (delegated)
    document.getElementById('cession-associes-container')?.addEventListener('input', function(e) {
        if (e.target && e.target.name) {
            if (e.target.name.indexOf('associe_parts') === 0) {
                recalcCapitalFromParts(e.target);
                updateTotals();
            } else if (e.target.name.indexOf('associe_capital_detenu') === 0) {
                updateTotals();
            }
        }
    });

    // Initial total on page load
    updateTotals();

    // Step 2: Associés dynamic add/remove
    var associeContainer = document.getElementById('cession-associes-container');
    var associeTemplate = document.getElementById('associe-step2-template');

    function reindexAssocies() {
        var cards = associeContainer.querySelectorAll('[data-associe-item]');
        cards.forEach(function(card, idx) {
            var title = card.querySelector('[data-associe-title]');
            if (title) title.textContent = 'Associe ' + (idx + 1);
            card.querySelectorAll('[name]').forEach(function(el) {
                var name = el.getAttribute('name') || '';
                el.name = name.replace(/\[\d+\]/g, '[' + idx + ']');
            });
        });
    }

    document.getElementById('add-associe-step2')?.addEventListener('click', function() {
        if (!associeTemplate) return;
        var clone = associeTemplate.content.cloneNode(true);
        // Convert data-field-name to name attributes
        clone.querySelectorAll('[data-field-name]').forEach(function(el) {
            var field = el.getAttribute('data-field-name');
            el.name = field + '[0]';
            el.removeAttribute('data-field-name');
        });
        associeContainer.appendChild(clone);
        reindexAssocies();
        updateTotals();
    });

    associeContainer?.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-remove-associe]');
        if (btn) {
            var card = btn.closest('[data-associe-item]');
            if (card && confirm('Retirer cet associe ?')) {
                card.remove();
                reindexAssocies();
                updateTotals();
            }
        }
    });

    // Form submit validation
    var associeForm = document.getElementById('associe-step-form');
    if (associeForm) {
        associeForm.addEventListener('submit', function(e) {
            var partInputs = document.querySelectorAll('[name^="associe_parts"]');
            var capInputs = document.querySelectorAll('[name^="associe_capital_detenu"]');
            var totalP = 0, totalC = 0;
            partInputs.forEach(function(inp) { totalP += parseInt(inp.value) || 0; });
            capInputs.forEach(function(inp) { totalC += parseFloat((inp.value || '0').replace(',', '.')) || 0; });
            var errors = [];
            if (totalPartsExpected > 0 && totalP !== totalPartsExpected) {
                errors.push('parts (' + totalP + '/' + totalPartsExpected + ')');
            }
            if (totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01) {
                errors.push('capital (' + totalC.toFixed(0) + '/' + totalCapitalExpected.toFixed(0) + ' DH)');
            }
            if (errors.length > 0) {
                e.preventDefault();
                alert('Le total des ' + errors.join(' et ') + ' des associes doit correspondre a la societe.');
            }
        });
    }

    // Auto-fill for step 2
    document.querySelectorAll('[data-fill-cession="2"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var form = btn.closest('form');
            if (!form) return;

            var associeCards = form.querySelectorAll('[data-associe-item]');
            if (associeCards.length === 0) {
                var addBtn = document.getElementById('add-associe-step2');
                if (addBtn) { addBtn.click(); }
                associeCards = form.querySelectorAll('[data-associe-item]');
            }
            // Distribute parts and capital evenly among associates
            var partBase = totalPartsExpected > 0 ? Math.floor(totalPartsExpected / associeCards.length) : randInt(100, 1000);
            var partRem = totalPartsExpected > 0 ? totalPartsExpected - (partBase * associeCards.length) : 0;
            var capBase = totalCapitalExpected > 0 ? Math.floor((totalCapitalExpected * 100 / associeCards.length)) / 100 : randInt(10000, 500000);
            var capRem = totalCapitalExpected > 0 ? Math.round((totalCapitalExpected - (capBase * associeCards.length)) * 100) / 100 : 0;

            associeCards.forEach(function(card, idx) {
                var civ = card.querySelector('select[name^="associe_civilite"]');
                if (civ) {
                    var civOpts = Array.from(civ.options).filter(function(o) { return o.value; });
                    if (civOpts.length) civ.value = randFrom(civOpts).value;
                }
                var nom = randFrom(hommes);
                var prenom = randFrom(prenoms);
                card.querySelector('[name^="associe_nom_complet"]') && (card.querySelector('[name^="associe_nom_complet"]').value = prenom + ' ' + nom);
                card.querySelector('[name^="associe_cin"]') && (card.querySelector('[name^="associe_cin"]').value = 'AB' + randInt(100000, 999999));
                card.querySelector('[name^="associe_date_naissance"]') && (card.querySelector('[name^="associe_date_naissance"]').value = randDate(new Date(1960, 0, 1), new Date(1995, 11, 31)));
                var ln = card.querySelector('[name^="associe_lieu_naissance"]');
                if (ln) {
                    var lnOpts = Array.from(ln.options).filter(function(o) { return o.value; });
                    if (lnOpts.length) ln.value = randFrom(lnOpts).value;
                }
                var nat = card.querySelector('[name^="associe_nationalite"]');
                if (nat) {
                    var natOpts = Array.from(nat.options).filter(function(o) { return o.value; });
                    if (natOpts.length) nat.value = randFrom(natOpts).value;
                }
                card.querySelector('[name^="associe_telephone"]') && (card.querySelector('[name^="associe_telephone"]').value = '06' + randInt(10000000, 99999999));
                card.querySelector('[name^="associe_email"]') && (card.querySelector('[name^="associe_email"]').value = prenom.toLowerCase() + '.' + nom.toLowerCase() + '@email.ma');
                card.querySelector('[name^="associe_adresse"]') && (card.querySelector('[name^="associe_adresse"]').value = randInt(1, 200) + ' ' + randFrom(['Avenue', 'Rue', 'Boulevard']) + ' ' + randFrom(['Liberte', 'FAR', 'Hassan II', 'Mohammed VI', 'Resistance']));
                var ql = card.querySelector('[name^="associe_qualite"]');
                if (ql) {
                    var qOpts = Array.from(ql.options).filter(function(o) { return o.value; });
                    if (qOpts.length) ql.value = randFrom(qOpts).value;
                }
                var parts = partBase + (idx === associeCards.length - 1 ? partRem : 0);
                card.querySelector('[name^="associe_parts"]') && (card.querySelector('[name^="associe_parts"]').value = String(parts));
                card.querySelector('[name^="associe_est_gerant"]') && (card.querySelector('[name^="associe_est_gerant"]').value = Math.random() > 0.5 ? '1' : '0');
            });

            updateTotals();
            form.querySelectorAll('input, select, textarea').forEach(function(f) {
                f.dispatchEvent(new Event('input', { bubbles: true }));
                f.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
})();
</script>
<?php endif; ?>
