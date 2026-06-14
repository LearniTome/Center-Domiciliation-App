<?php

declare(strict_types=1);

// AJAX handler for adding activite ref (statuts or cert_neg)
if (!empty($_POST['add_activite_ref']) && ($pdo ?? null) instanceof PDO) {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $name = trim((string) ($_POST['new_activite'] ?? ''));
        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Nom vide']);
            exit;
        }
        $type = field_value($_POST, 'type', 'statuts');
        if ($type === 'cert_neg') {
            $ompicCode = field_value($_POST, 'ompic_code');
            if ($ompicCode === '') {
                echo json_encode(['success' => false, 'error' => 'Code OMPIC requis']);
                exit;
            }
            $nmaLibelle = field_value($_POST, 'nma_libelle', $name);
            $stmt = $pdo->prepare("INSERT IGNORE INTO ref_activites_ompic (code, libelle, sort_order) VALUES (:code, :libelle, :so)");
            $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ref_activites_ompic")->fetchColumn();
            $stmt->execute(['code' => $ompicCode, 'libelle' => $nmaLibelle, 'so' => $max]);
            echo json_encode(['success' => true, 'code' => $ompicCode, 'libelle' => $nmaLibelle]);
        } else {
            $check = $pdo->prepare('SELECT COUNT(*) FROM ref_activites WHERE activite = :name');
            $check->execute(['name' => $name]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => "L'activite \"$name\" existe deja"]);
                exit;
            }
            $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ref_activites")->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO ref_activites (activite, sort_order) VALUES (:name, :so)');
            $stmt->execute(['name' => $name, 'so' => $max]);
            echo json_encode(['success' => true, 'value' => $name]);
        }
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

        $activitesStatuts = $_POST['societe_activites_statuts'] ?? [];
        $allStatuts = is_array($activitesStatuts) ? array_map('trim', $activitesStatuts) : [];
        $allStatuts = array_unique(array_filter($allStatuts));

        $wizard['societe'] = [
            'societe_raison_sociale' => $raison,
            'societe_forme_juridique' => trim((string) ($_POST['societe_forme_juridique'] ?? '')),
            'societe_ice' => trim((string) ($_POST['societe_ice'] ?? '')),
            'societe_date_ice' => trim((string) ($_POST['societe_date_ice'] ?? '')),
            'societe_date_exp_cert_neg' => trim((string) ($_POST['societe_date_exp_cert_neg'] ?? '')),
            'societe_rc' => trim((string) ($_POST['societe_rc'] ?? '')),
            'societe_if' => trim((string) ($_POST['societe_if'] ?? '')),
            'societe_tp' => trim((string) ($_POST['societe_tp'] ?? '')),
            'societe_cnss' => trim((string) ($_POST['societe_cnss'] ?? '')),
            'societe_capital' => (string) ($_POST['societe_capital'] ?? ''),
            'societe_part_social' => (string) ($_POST['societe_part_social'] ?? ''),
            'societe_valeur_nominale' => (string) ($_POST['societe_valeur_nominale'] ?? ''),
            'societe_adresse_siege' => trim((string) ($_POST['societe_adresse_siege'] ?? '')),
            'societe_ville' => trim((string) ($_POST['societe_ville'] ?? '')),
            'societe_tribunal' => trim((string) ($_POST['societe_tribunal'] ?? '')),
            'societe_tribunal_type' => trim((string) ($_POST['societe_tribunal_type'] ?? '')),
            'societe_email' => trim((string) ($_POST['societe_email'] ?? '')),
            'societe_telephone' => trim((string) ($_POST['societe_telephone'] ?? '')),
            'societe_dossier' => trim((string) ($_POST['societe_dossier'] ?? '')),
            'societe_type_generation' => trim((string) ($_POST['societe_type_generation'] ?? 'cession')),
            'societe_procedure_creation' => trim((string) ($_POST['societe_procedure_creation'] ?? '')),
            'societe_mode_depot' => trim((string) ($_POST['societe_mode_depot'] ?? '')),
            'societe_activites_statuts' => implode(', ', $allStatuts),
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
        <div style="display:flex;gap:8px;margin-right:auto">
            <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
        <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
        <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
    </div>
</form>

<?php elseif ($wizard['mode'] === 'nouvelle'): ?>
<form method="post" class="stack" id="wizard-step1">
    <?= csrf_input() ?>
    <input type="hidden" name="step" value="1">
    <article class="card">
        <div class="section-header">
            <h2>Informations sur la societe</h2>
            <button class="btn btn-info" type="button" data-fill-cession="1" style="margin-left:auto"><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        </div>
        <div class="form-grid">
            <input type="hidden" name="societe_type_generation" value="cession">

            <h3 class="section-title">Identifiants</h3>
            <label class="field">
                <span>Dossier cession</span>
                <input name="societe_dossier" value="<?= e((string) $societeData['societe_dossier']) ?>">
            </label>
            <label class="field">
                <span>Raison sociale</span>
                <input name="societe_raison_sociale" required value="<?= e((string) $societeData['societe_raison_sociale']) ?>">
            </label>
            <label class="field">
                <span>Forme juridique</span>
                <div style="display:flex;gap:8px;align-items:center">
                    <select name="societe_forme_juridique" style="flex:1">
                        <option value="">Selectionner</option>
                        <?php foreach ($formesJuridiques as $fj): ?>
                            <option value="<?= e($fj['forme_juridique']) ?>" <?= (string) $societeData['societe_forme_juridique'] === $fj['forme_juridique'] ? 'selected' : '' ?>><?= e($fj['forme_juridique']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="<?= e(app_url('configuration', ['tab' => 'formes-juridiques'])) ?>" target="_blank" title="Gerer les formes juridiques" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                </div>
            </label>
            <label class="field">
                <span>ICE</span>
                <input name="societe_ice" value="<?= e((string) $societeData['societe_ice']) ?>">
            </label>
            <label class="field">
                <span>RC</span>
                <input name="societe_rc" value="<?= e((string) $societeData['societe_rc']) ?>">
            </label>
            <label class="field">
                <span>IF</span>
                <input name="societe_if" value="<?= e((string) $societeData['societe_if']) ?>">
            </label>
            <label class="field">
                <span>TP</span>
                <input name="societe_tp" value="<?= e((string) ($societeData['societe_tp'] ?? '')) ?>">
            </label>
            <label class="field">
                <span>CNSS</span>
                <input name="societe_cnss" value="<?= e((string) ($societeData['societe_cnss'] ?? '')) ?>">
            </label>

            <div data-statuts-section style="grid-column:1/-1">
            <h3 class="section-title">Activites (Statuts)</h3>
            <label class="field full">
                <span>Activites pour les statuts</span>
                <div data-activites-group="statuts">
                    <div data-activites-container>
                        <?php
                        $wizStatuts = !empty($societeData['societe_activites_statuts']) ? array_map('trim', explode(',', (string) $societeData['societe_activites_statuts'])) : [];
                        if ($wizStatuts):
                            foreach ($wizStatuts as $act):
                        ?>
                            <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                                <select name="societe_activites_statuts[]" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($activitesOptions as $opt): ?>
                                        <option value="<?= e($opt) ?>" <?= $act === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!in_array($act, $activitesOptions)): ?>
                                        <option value="<?= e($act) ?>" selected><?= e($act) ?></option>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button>
                            </div>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                                <select name="societe_activites_statuts[]" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($activitesOptions as $opt): ?>
                                        <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                        <button type="button" class="btn" data-add-activite><span class="material-symbols-outlined">add</span> Ajouter une activite</button>
                        <button type="button" class="btn btn-info" data-add-activite-ref><span class="material-symbols-outlined">add_circle</span> Nouvelle activite</button>
                    </div>
                    <template data-activite-template>
                        <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                            <select name="societe_activites_statuts[]" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($activitesOptions as $opt): ?>
                                    <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button>
                        </div>
                    </template>
                </div>
            </label>
            </div>

            <h3 class="section-title">Capital</h3>
            <label class="field">
                <span>Capital</span>
                <input type="number" step="0.01" name="societe_capital" value="<?= e((string) $societeData['societe_capital']) ?>">
            </label>
            <label class="field">
                <span>Part social</span>
                <input type="number" name="societe_part_social" value="<?= e((string) $societeData['societe_part_social']) ?>">
            </label>
            <label class="field">
                <span>Valeur nominale</span>
                <input type="number" step="0.01" name="societe_valeur_nominale" value="<?= e((string) $societeData['societe_valeur_nominale']) ?>">
            </label>

            <h3 class="section-title">Adresse</h3>
            <label class="field full">
                <span>Adresse de reference</span>
                <div style="display:flex;gap:8px;align-items:center">
                    <select name="societe_adresse_siege" style="flex:1">
                        <option value="">Selectionner</option>
                        <?php foreach ($adressesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societeData['societe_adresse_siege'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="<?= e(app_url('configuration', ['tab' => 'adresses'])) ?>" target="_blank" title="Gerer les adresses" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                </div>
            </label>
            <label class="field">
                <span>Ville</span>
                <div style="display:flex;gap:8px;align-items:center">
                    <select name="societe_ville" style="flex:1">
                        <option value="">Selectionner</option>
                        <?php foreach ($villes as $option): ?>
                            <option value="<?= e($option) ?>" <?= $defaultVille === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="<?= e(app_url('configuration', ['tab' => 'villes'])) ?>" target="_blank" title="Gerer les villes" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                </div>
            </label>
            <label class="field">
                <span>Type de tribunal</span>
                <select name="societe_tribunal_type" data-tribunal-type>
                    <option value="">Selectionner</option>
                    <?php foreach ($tribunalTypes as $type): ?>
                        <option value="<?= e($type) ?>" <?= $currentTribunalType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Tribunal</span>
                <select name="societe_tribunal">
                    <option value="">Selectionner</option>
                    <?php foreach ($allTribunaux as $t): ?>
                        <option value="<?= e($t['tribunal']) ?>" data-type="<?= e($t['tribunal_type'] ?? '') ?>" <?= $defaultTribunal === $t['tribunal'] && $currentTribunalType === ($t['tribunal_type'] ?? '') ? 'selected' : '' ?>><?= e($t['tribunal']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <h3 class="section-title">Contact</h3>
            <label class="field">
                <span>Email</span>
                <input type="email" name="societe_email" value="<?= e((string) $societeData['societe_email']) ?>">
            </label>
            <label class="field">
                <span>Telephone</span>
                <input name="societe_telephone" value="<?= e((string) $societeData['societe_telephone']) ?>">
            </label>
        </div>
    </article>

    <div class="footer-actions">
        <div style="display:flex;gap:8px;margin-right:auto">
            <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
        <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
        <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
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
            form.querySelector('[name="societe_dossier"]') && (form.querySelector('[name="societe_dossier"]').value = 'CESS-' + randInt(100, 999));
            var fj = form.querySelector('[name="societe_forme_juridique"]');
            if (fj) {
                var opts = Array.from(fj.options).filter(function(o) { return o.value; });
                if (opts.length) fj.value = randFrom(opts).value;
            }
            var pref = randFrom(icePrefixes);
            form.querySelector('[name="societe_ice"]') && (form.querySelector('[name="societe_ice"]').value = pref + randInt(100000, 999999));
            form.querySelector('[name="societe_capital"]') && (form.querySelector('[name="societe_capital"]').value = String(randInt(50000, 500000)));
            form.querySelector('[name="societe_part_social"]') && (form.querySelector('[name="societe_part_social"]').value = String(randInt(100, 5000)));
            form.querySelector('[name="societe_valeur_nominale"]') && (form.querySelector('[name="societe_valeur_nominale"]').value = String(randInt(50, 1000)));
            var ville = form.querySelector('[name="societe_ville"]');
            if (ville) {
                var vOpts = Array.from(ville.options).filter(function(o) { return o.value; });
                if (vOpts.length) ville.value = randFrom(vOpts).value;
            }
            var trib = form.querySelector('[name="societe_tribunal"]');
            if (trib) {
                var tOpts = Array.from(trib.options).filter(function(o) { return o.value; });
                if (tOpts.length) trib.value = randFrom(tOpts).value;
            }
            var tribType = form.querySelector('[name="societe_tribunal_type"]');
            if (tribType) {
                var ttOpts = Array.from(tribType.options).filter(function(o) { return o.value; });
                if (ttOpts.length) tribType.value = randFrom(ttOpts).value;
                tribType.dispatchEvent(new Event('change'));
            }
            var addr = form.querySelector('[name="societe_adresse_siege"]');
            if (addr) {
                var aOpts = Array.from(addr.options).filter(function(o) { return o.value; });
                if (aOpts.length) addr.value = randFrom(aOpts).value;
            }
            form.querySelector('[name="societe_email"]') && (form.querySelector('[name="societe_email"]').value = 'contact@' + rs.toLowerCase().replace(/[^a-z0-9]/g, '') + '.ma');
            form.querySelector('[name="societe_telephone"]') && (form.querySelector('[name="societe_telephone"]').value = '0' + randInt(5,7) + String(randInt(10000000, 99999999)));

            // Activites (statuts) - fill selects
            var activiteItems = document.querySelectorAll('[data-activites-container] [data-activite-item] select');
            if (activiteItems.length > 0 && allActivitesOptions.length > 0) {
                var used = [];
                activiteItems.forEach(function(sel) {
                    var avail = allActivitesOptions.filter(function(a) { return used.indexOf(a) === -1; });
                    if (avail.length === 0) return;
                    var picked = randFrom(avail);
                    sel.value = picked;
                    used.push(picked);
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
