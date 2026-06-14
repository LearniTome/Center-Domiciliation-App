<?php

declare(strict_types=1);

// AJAX handler for adding activite ref
if (!empty($_POST['add_activite_ref']) && ($pdo ?? null) instanceof PDO) {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $name = trim((string) ($_POST['new_activite'] ?? ''));
        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Nom vide']);
            exit;
        }
        $check = $pdo->prepare('SELECT COUNT(*) FROM ref_activites WHERE activite = :name');
        $check->execute(['name' => $name]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => "L'activite \"$name\" existe deja"]);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO ref_activites (activite) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        echo json_encode(['success' => true, 'value' => $name]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// POST handler
if (is_post() && $step === 1) {
    verify_csrf();

    if ($wizard['mode'] === 'existante') {
        $wizard['societe_id'] = (int) ($_POST['societe_id'] ?? 0);
        if ($wizard['societe_id'] <= 0) {
            set_flash('error', 'Veuillez selectionner une societe.');
            redirect_to('cession', ['step' => 1]);
        }
        $wizard['parts'] = [];
        redirect_to('cession', ['step' => 2]);
    }

    if ($wizard['mode'] === 'nouvelle') {
        $raison = trim((string) ($_POST['societe_raison_sociale'] ?? ''));
        if ($raison === '') {
            set_flash('error', 'Veuillez saisir la raison sociale.');
            redirect_to('cession', ['step' => 1]);
        }

        $wizard['societe'] = [
            'societe_raison_sociale' => $raison,
            'societe_forme_juridique' => trim((string) ($_POST['societe_forme_juridique'] ?? '')),
            'societe_ice' => trim((string) ($_POST['societe_ice'] ?? '')),
            'societe_rc' => trim((string) ($_POST['societe_rc'] ?? '')),
            'societe_if' => trim((string) ($_POST['societe_if'] ?? '')),
            'societe_tp' => trim((string) ($_POST['societe_tp'] ?? '')),
            'societe_capital' => (string) ($_POST['societe_capital'] ?? ''),
            'societe_part_social' => (string) ($_POST['societe_part_social'] ?? ''),
            'societe_valeur_nominale' => (string) ($_POST['societe_valeur_nominale'] ?? ''),
            'societe_adresse_siege' => trim((string) ($_POST['societe_adresse_siege'] ?? '')),
            'societe_ville' => trim((string) ($_POST['societe_ville'] ?? '')),
            'societe_tribunal' => trim((string) ($_POST['societe_tribunal'] ?? '')),
            'societe_tribunal_type' => trim((string) ($_POST['societe_tribunal_type'] ?? '')),
            'societe_email' => trim((string) ($_POST['societe_email'] ?? '')),
            'societe_telephone' => trim((string) ($_POST['societe_telephone'] ?? '')),
            'societe_activites_statuts' => !empty($_POST['societe_activites_statuts']) && is_array($_POST['societe_activites_statuts']) ? implode(', ', array_unique(array_filter(array_map('trim', $_POST['societe_activites_statuts'])))) : '',
            'societe_activites_ompic' => trim((string) ($_POST['societe_activites_ompic'] ?? '')),
        ];

        $wizard['parts'] = [];
        redirect_to('cession', ['step' => 2]);
    }

    set_flash('error', 'Mode de cession non defini.');
    redirect_to('cession', ['step' => 0]);
}

// HTML view
if ($step === 1):
?>
<?php if ($wizard['mode'] === 'existante'): ?>
<!-- Mode existante: dropdown -->
<form method="post" class="stack">
    <?= csrf_input() ?>
    <div class="field">
        <label for="societe_id">Societe concernee</label>
        <select name="societe_id" id="societe_id">
            <option value="">-- Selectionnez une societe --</option>
            <?php foreach ($societesList as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $wizard['societe_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['societe_raison_sociale']) ?> (<?= e($s['societe_dossier'] ?? '') ?>) - <?= e($s['societe_forme_juridique'] ?? '') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($selectedSociete): ?>
    <div class="info-grid">
        <div><strong>Forme juridique</strong><br><?= e($selectedSociete['societe_forme_juridique'] ?? '-') ?></div>
        <div><strong>Capital</strong><br><?= e(number_format((float) ($selectedSociete['societe_capital'] ?? 0), 2, ',', ' ') . ' DH') ?></div>
        <div><strong>Nombre de parts</strong><br><?= (int) ($selectedSociete['societe_part_social'] ?? 0) ?></div>
        <div><strong>Ville</strong><br><?= e($selectedSociete['societe_ville'] ?? '-') ?></div>
    </div>
    <?php if (!empty($selectedAssocies)): ?>
    <div>
        <strong>Associes actuels :</strong>
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
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <div class="footer-actions">
        <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>

<?php elseif ($wizard['mode'] === 'nouvelle'): ?>
<form method="post" class="stack">
    <?= csrf_input() ?>
    <article class="card">
        <div class="section-header">
            <div style="display:flex;align-items:center;gap:8px"><h2>Informations sur la societe</h2><p class="help-text" style="margin:0">Saisissez les details de la nouvelle societe</p></div>
            <button class="btn btn-info" type="button" data-fill-cession="1"><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        </div>
        <div class="form-grid">
            <h3 class="section-title">Identifiants</h3>
            <label class="field">
                <span>Raison sociale *</span>
                <input type="text" name="societe_raison_sociale" required value="<?= e($wizard['societe']['societe_raison_sociale'] ?? '') ?>">
            </label>
            <label class="field">
                <span>Forme juridique</span>
                <select name="societe_forme_juridique">
                    <option value="">-- Selectionnez --</option>
                    <?php foreach ($formesJuridiques as $fj): ?>
                        <option value="<?= e($fj['forme_juridique']) ?>" <?= ($wizard['societe']['societe_forme_juridique'] ?? '') === $fj['forme_juridique'] ? 'selected' : '' ?>><?= e($fj['forme_juridique']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>ICE</span>
                <input type="text" name="societe_ice" value="<?= e($wizard['societe']['societe_ice'] ?? '') ?>">
            </label>
            <label class="field">
                <span>RC</span>
                <input type="text" name="societe_rc" value="<?= e($wizard['societe']['societe_rc'] ?? '') ?>">
            </label>
            <label class="field">
                <span>IF</span>
                <input type="text" name="societe_if" value="<?= e($wizard['societe']['societe_if'] ?? '') ?>">
            </label>
            <label class="field">
                <span>TP</span>
                <input type="text" name="societe_tp" value="<?= e($wizard['societe']['societe_tp'] ?? '') ?>">
            </label>
            <h3 class="section-title">Capital</h3>
            <label class="field">
                <span>Capital (DH)</span>
                <input type="text" name="societe_capital" value="<?= e($wizard['societe']['societe_capital'] ?? '') ?>" placeholder="100000">
            </label>
            <label class="field">
                <span>Nombre de parts</span>
                <input type="number" name="societe_part_social" value="<?= e($wizard['societe']['societe_part_social'] ?? '') ?>" placeholder="100">
            </label>
            <label class="field">
                <span>Valeur nominale (DH)</span>
                <input type="text" name="societe_valeur_nominale" value="<?= e($wizard['societe']['societe_valeur_nominale'] ?? '') ?>" placeholder="1000">
            </label>
            <h3 class="section-title">Localisation</h3>
            <label class="field">
                <span>Ville</span>
                <select name="societe_ville">
                    <option value="">-- Selectionnez --</option>
                    <?php foreach ($villes as $v): ?>
                        <option value="<?= e($v) ?>" <?= ($wizard['societe']['societe_ville'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Tribunal</span>
                <select name="societe_tribunal">
                    <option value="">-- Selectionnez --</option>
                    <?php foreach ($tribunaux as $t): ?>
                        <option value="<?= e($t['tribunal']) ?>" <?= ($wizard['societe']['societe_tribunal'] ?? '') === $t['tribunal'] ? 'selected' : '' ?>><?= e($t['tribunal']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Type de tribunal</span>
                <select name="societe_tribunal_type">
                    <option value="">-- Selectionnez --</option>
                    <option value="Tribunal de commerce" <?= ($wizard['societe']['societe_tribunal_type'] ?? '') === 'Tribunal de commerce' ? 'selected' : '' ?>>Tribunal de commerce</option>
                    <option value="Tribunal de Première Instance" <?= ($wizard['societe']['societe_tribunal_type'] ?? '') === 'Tribunal de Première Instance' ? 'selected' : '' ?>>Tribunal de Première Instance</option>
                </select>
            </label>
            <label class="field full">
                <span>Adresse du siege</span>
                <textarea name="societe_adresse_siege" rows="2"><?= e($wizard['societe']['societe_adresse_siege'] ?? '') ?></textarea>
            </label>
            <h3 class="section-title">Contact</h3>
            <label class="field">
                <span>Email</span>
                <input type="email" name="societe_email" value="<?= e($wizard['societe']['societe_email'] ?? '') ?>" placeholder="contact@exemple.com">
            </label>
            <label class="field">
                <span>Telephone</span>
                <input type="text" name="societe_telephone" value="<?= e($wizard['societe']['societe_telephone'] ?? '') ?>" placeholder="05XX-XXXXXX">
            </label>
            <h3 class="section-title">Activite</h3>
            <div class="field full" style="flex-direction:column;align-items:stretch;gap:8px">
                <span>Activites (statuts)</span>
                <div style="overflow:visible">
                    <table id="activites-table">
                        <thead>
                            <tr>
                                <th>Activite</th>
                                <th style="width:50px">Action</th>
                            </tr>
                        </thead>
                        <tbody id="activites-container">
                            <?php
                            $wizStatuts = !empty($wizard['societe']['societe_activites_statuts']) ? array_map('trim', explode(',', (string) $wizard['societe']['societe_activites_statuts'])) : [];
                            if ($wizStatuts):
                                foreach ($wizStatuts as $act):
                            ?>
                                <tr data-activite-row>
                                    <td>
                                        <div class="autocomplete-wrap" style="position:relative">
                                            <input type="text" name="societe_activites_statuts[]" style="width:100%" value="<?= e($act) ?>" placeholder="Saisissez ou selectionnez une activite" autocomplete="off">
                                            <div class="autocomplete-dropdown" style="position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--panel);border:1px solid var(--line);border-radius:4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.5)"></div>
                                        </div>
                                    </td>
                                    <td><button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">delete</span></button></td>
                                </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <tr data-activite-row>
                                    <td>
                                        <div class="autocomplete-wrap" style="position:relative">
                                            <input type="text" name="societe_activites_statuts[]" style="width:100%" placeholder="Saisissez ou selectionnez une activite" autocomplete="off">
                                            <div class="autocomplete-dropdown" style="position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--panel);border:1px solid var(--line);border-radius:4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.5)"></div>
                                        </div>
                                    </td>
                                    <td><button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">delete</span></button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn" id="add-activite-row"><span class="material-symbols-outlined">playlist_add</span> Ajouter une activite</button>
                </div>
                <template id="activite-row-template">
                    <tr data-activite-row>
                        <td>
                            <div class="autocomplete-wrap" style="position:relative">
                                <input type="text" name="societe_activites_statuts[]" style="width:100%" placeholder="Saisissez ou selectionnez une activite" autocomplete="off">
                                <div class="autocomplete-dropdown" style="position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--panel);border:1px solid var(--line);border-radius:4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.5)"></div>
                            </div>
                        </td>
                        <td><button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">delete</span></button></td>
                    </tr>
                </template>
            </div>
        </div>
    </article>

    <div class="footer-actions">
        <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>
<?php endif; ?>

<script>
(function(){
    var allActivitesOptions = <?= json_encode(array_values($activitesOptions)) ?>;
    var razSociete = [
        'MALAOUI APP', 'FADAA DOI', 'ATLAS CONSULTING', 'TECHNOVA SARL',
        'GREEN ECO SERVICES', 'NORTH AFRICA LOGISTICS', 'ALPHA BUSINESS',
        'MEDITERRANEE INVEST', 'SAHARA ENERGIE', 'ATLANTIC TRADE'
    ];
    var icePrefixes = ['123456789', '987654321', '456789123', '789123456'];
    var hommes = ['ALAOUI', 'BENALI', 'CHERKAOUI', 'DAHMANI', 'EL FASSI', 'FAHIMI', 'GHAZI', 'HAMMADI'];
    var prenoms = ['Mohamed', 'Ahmed', 'Hassan', 'Omar', 'Youssef', 'Karim', 'Mehdi', 'Said'];

    function randFrom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
    function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

    document.querySelectorAll('[data-fill-cession="1"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var form = btn.closest('form');
            if (!form) return;

            var rs = randFrom(razSociete);
            form.querySelector('[name="societe_raison_sociale"]') && (form.querySelector('[name="societe_raison_sociale"]').value = rs);
            var fj = form.querySelector('[name="societe_forme_juridique"]');
            if (fj) {
                var opts = Array.from(fj.options).filter(function(o) { return o.value && o.value !== '-- Selectionnez --'; });
                if (opts.length) fj.value = randFrom(opts).value;
            }
            var pref = randFrom(icePrefixes);
            form.querySelector('[name="societe_ice"]') && (form.querySelector('[name="societe_ice"]').value = pref + randInt(100000, 999999));
            form.querySelector('[name="societe_rc"]') && (form.querySelector('[name="societe_rc"]').value = String(randInt(10000, 999999)));
            form.querySelector('[name="societe_if"]') && (form.querySelector('[name="societe_if"]').value = String(randInt(1000000, 99999999)));
            form.querySelector('[name="societe_tp"]') && (form.querySelector('[name="societe_tp"]').value = String(randInt(1000000, 99999999)));
            form.querySelector('[name="societe_capital"]') && (form.querySelector('[name="societe_capital"]').value = String(randInt(50000, 500000)));
            form.querySelector('[name="societe_part_social"]') && (form.querySelector('[name="societe_part_social"]').value = String(randInt(100, 5000)));
            form.querySelector('[name="societe_valeur_nominale"]') && (form.querySelector('[name="societe_valeur_nominale"]').value = String(randInt(50, 1000)));
            var ville = form.querySelector('[name="societe_ville"]');
            if (ville) {
                var vOpts = Array.from(ville.options).filter(function(o) { return o.value && o.value !== '-- Selectionnez --'; });
                if (vOpts.length) ville.value = randFrom(vOpts).value;
            }
            var trib = form.querySelector('[name="societe_tribunal"]');
            if (trib) {
                var tOpts = Array.from(trib.options).filter(function(o) { return o.value && o.value !== '-- Selectionnez --'; });
                if (tOpts.length) trib.value = randFrom(tOpts).value;
            }
            var tribType = form.querySelector('[name="societe_tribunal_type"]');
            if (tribType) {
                var ttOpts = Array.from(tribType.options).filter(function(o) { return o.value; });
                if (ttOpts.length) tribType.value = randFrom(ttOpts).value;
            }
            form.querySelector('[name="societe_adresse_siege"]') && (form.querySelector('[name="societe_adresse_siege"]').value = randInt(1, 500) + ', Rue ' + randFrom(['Mohammed V', 'Hassan II', 'Oqba', 'Far', 'Moulay Ismail']) + ', ' + (ville ? ville.value : 'Casablanca'));
            form.querySelector('[name="societe_email"]') && (form.querySelector('[name="societe_email"]').value = 'contact@' + rs.toLowerCase().replace(/[^a-z0-9]/g, '') + '.ma');
            form.querySelector('[name="societe_telephone"]') && (form.querySelector('[name="societe_telephone"]').value = '0' + randInt(5,7) + String(randInt(10000000, 99999999)));

            // Activites (statuts)
            var activiteRows = document.querySelectorAll('#activites-container [data-activite-row]');
            if (activiteRows.length > 0 && typeof allActivitesOptions !== 'undefined' && allActivitesOptions.length > 0) {
                var used = [];
                activiteRows.forEach(function(row, idx) {
                    var inp = row.querySelector('input');
                    if (inp) {
                        var avail = allActivitesOptions.filter(function(a) { return used.indexOf(a) === -1; });
                        if (avail.length === 0) return;
                        var picked = randFrom(avail);
                        inp.value = picked;
                        inp.dispatchEvent(new Event('input', { bubbles: true }));
                        used.push(picked);
                    }
                });
            }

            // Trigger input/change events
            form.querySelectorAll('input, select, textarea').forEach(function(f) {
                f.dispatchEvent(new Event('input', { bubbles: true }));
                f.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
})();
</script>
<?php endif; ?>
