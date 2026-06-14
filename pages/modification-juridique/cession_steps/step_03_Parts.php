<?php
declare(strict_types=1);

// PHP precompute
$socForPrix = $selectedSociete ?: ($wizard['societe'] ?? []);
$valeurNominaleCession = (float) ($socForPrix['societe_valeur_nominale'] ?? 0);

// POST handler
if (is_post() && $step === 3) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 2]);
    }

    $wizard['cession_date'] = field_value($_POST, 'cession_date');
    $wizard['cession_motif'] = field_value($_POST, 'cession_motif');

    $cedantTypes = $_POST['cedant_type'] ?? [];
    $cedantAssocieIds = $_POST['cedant_associe_id'] ?? [];
    $cedantNoms = $_POST['cedant_nom_complet'] ?? [];
    $cedantCins = $_POST['cedant_cin'] ?? [];
    $cessionnaireTypes = $_POST['cessionnaire_type'] ?? [];
    $cessionnaireAssocieIds = $_POST['cessionnaire_associe_id'] ?? [];
    $cessionnaireNoms = $_POST['cessionnaire_nom_complet'] ?? [];
    $cessionnaireCins = $_POST['cessionnaire_cin'] ?? [];
    $cessionnaireCivilites = $_POST['cessionnaire_civilite'] ?? [];
    $cessionnaireDates = $_POST['cessionnaire_date_naissance'] ?? [];
    $cessionnaireLieux = $_POST['cessionnaire_lieu_naissance'] ?? [];
    $cessionnaireNationalites = $_POST['cessionnaire_nationalite'] ?? [];
    $cessionnaireAdresses = $_POST['cessionnaire_adresse'] ?? [];
    $cessionnaireTelephones = $_POST['cessionnaire_telephone'] ?? [];
    $cessionnaireEmails = $_POST['cessionnaire_email'] ?? [];
    $cessionnaireQualites = $_POST['cessionnaire_qualite'] ?? [];
    $cessionnaireParts = $_POST['cessionnaire_parts'] ?? [];
    $cessionnaireCapitals = $_POST['cessionnaire_capital_detenu'] ?? [];
    $cessionnaireGerants = $_POST['cessionnaire_est_gerant'] ?? [];
    $pourcentages = $_POST['pourcentage'] ?? [];
    $partsCedees = $_POST['parts_cedees'] ?? [];
    $prixUnitaires = $_POST['prix_unitaire'] ?? [];
    $prixTotaux = $_POST['prix_total'] ?? [];
    $nommerGerant = $_POST['nommer_gerant'] ?? [];

    $totalParts = 0;
    if ($selectedSociete) {
        $totalParts = (int) ($selectedSociete['societe_part_social'] ?? 0);
    }

    $wizard['parts'] = [];
    $count = max(count($cedantNoms), count($cessionnaireNoms), count($partsCedees));
    for ($i = 0; $i < $count; $i++) {
        $cedType = $cedantTypes[$i] ?? 'existant';
        $cedAssocieId = (int) ($cedantAssocieIds[$i] ?? 0);
        $cedNom = trim((string) ($cedantNoms[$i] ?? ''));
        $cedCin = trim((string) ($cedantCins[$i] ?? ''));
        if ($cedNom === '' && $cedAssocieId > 0) {
            if (($pdo ?? null) instanceof PDO) {
                $a = fetch_record($pdo, 'associes', $cedAssocieId);
                if ($a) { $cedNom = $a['associe_nom_complet'] ?? ''; $cedCin = $a['associe_cin'] ?? ''; }
            }
            if ($cedNom === '' && !empty($wizard['associes'])) {
                foreach ($wizard['associes'] as $wa) {
                    if (((int) ($wa['id'] ?? 0)) === $cedAssocieId) {
                        $cedNom = $wa['associe_nom_complet'] ?? '';
                        $cedCin = $wa['associe_cin'] ?? '';
                        break;
                    }
                }
            }
        }

        $cessType = $cessionnaireTypes[$i] ?? 'existant';
        $cessAssocieId = (int) ($cessionnaireAssocieIds[$i] ?? 0);
        $cessNom = trim((string) ($cessionnaireNoms[$i] ?? ''));
        $cessCin = trim((string) ($cessionnaireCins[$i] ?? ''));
        if ($cessType === 'existant' && $cessAssocieId > 0 && $cessNom === '' && ($pdo ?? null) instanceof PDO) {
            $a = fetch_record($pdo, 'associes', $cessAssocieId);
            if ($a) { $cessNom = $a['associe_nom_complet'] ?? ''; $cessCin = $a['associe_cin'] ?? ''; }
        }
        if ($cessNom === '' && !empty($wizard['associes'])) {
            foreach ($wizard['associes'] as $wa) {
                if (((int) ($wa['id'] ?? 0)) === $cessAssocieId) {
                    $cessNom = $wa['associe_nom_complet'] ?? '';
                    $cessCin = $wa['associe_cin'] ?? '';
                    break;
                }
            }
        }

        $pct = money_value(['v' => $pourcentages[$i] ?? '0'], 'v');
        $parts = (int) ($partsCedees[$i] ?? 0);
        if ($pct > 0 && $totalParts > 0) {
            $parts = (int) round(($pct / 100) * $totalParts);
        }

        if ($cedNom === '' || $cessNom === '' || $parts <= 0) continue;

        $pu = money_value(['v' => $prixUnitaires[$i] ?? '0'], 'v');
        $pt = money_value(['v' => $prixTotaux[$i] ?? '0'], 'v');
        if ($pt === null || $pt <= 0) $pt = $pu * $parts;

        $wizard['parts'][] = [
            'cedant_type' => $cedType,
            'cedant_associe_id' => $cedAssocieId,
            'cedant_nom_complet' => $cedNom,
            'cedant_cin' => $cedCin,
            'cessionnaire_type' => $cessType,
            'cessionnaire_associe_id' => $cessAssocieId,
            'cessionnaire_nom_complet' => $cessNom,
            'cessionnaire_cin' => $cessCin,
            'cessionnaire_civilite' => trim((string) ($cessionnaireCivilites[$i] ?? 'M.')),
            'cessionnaire_date_naissance' => trim((string) ($cessionnaireDates[$i] ?? '')),
            'cessionnaire_lieu_naissance' => trim((string) ($cessionnaireLieux[$i] ?? '')),
            'cessionnaire_nationalite' => trim((string) ($cessionnaireNationalites[$i] ?? '')),
            'cessionnaire_adresse' => trim((string) ($cessionnaireAdresses[$i] ?? '')),
            'cessionnaire_telephone' => trim((string) ($cessionnaireTelephones[$i] ?? '')),
            'cessionnaire_email' => trim((string) ($cessionnaireEmails[$i] ?? '')),
            'cessionnaire_qualite' => trim((string) ($cessionnaireQualites[$i] ?? '')),
            'cessionnaire_parts' => (int) ($cessionnaireParts[$i] ?? 0),
            'cessionnaire_capital_detenu' => trim((string) ($cessionnaireCapitals[$i] ?? '')),
            'cessionnaire_est_gerant' => !empty($cessionnaireGerants[$i]) ? 1 : 0,
            'pourcentage' => $pct > 0 ? $pct : null,
            'parts_cedees' => $parts,
            'prix_unitaire' => $pu,
            'prix_total' => $pt,
            'nommer_gerant' => !empty($nommerGerant[$i]) ? 1 : 0,
        ];
    }

    if (empty($wizard['parts'])) {
        set_flash('error', 'Ajoutez au moins une ligne de cession valide.');
        redirect_to('cession', ['step' => 3]);
    }
    redirect_to('cession', ['step' => 4]);
}

// HTML view
if ($step === 3):
?>
<form method="post" id="cession-form" data-valeur-nominale="<?= $valeurNominaleCession ?>">
    <?= csrf_input() ?>
    <input type="hidden" name="nav_action" value="next">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field">
            <label for="cession_date">Date de la cession</label>
            <input type="date" name="cession_date" id="cession_date" value="<?= e($wizard['cession_date'] ?? date('Y-m-d')) ?>" required>
        </div>
        <div class="field">
            <label for="cession_motif">Motif de la cession</label>
            <input type="text" name="cession_motif" id="cession_motif" value="<?= e($wizard['cession_motif'] ?? '') ?>" placeholder="Ex: Cession entre associes">
        </div>
    </div>

    <input type="hidden" id="total-societe-parts" value="<?= (int) ($selectedSociete['societe_part_social'] ?? 0) ?>">

    <div style="margin-top:20px">
        <div class="section-header" style="margin-bottom:12px">
            <strong>Lignes de cession</strong>
            <button class="btn btn-info" type="button" data-fill-cession="3"><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        </div>
        <div id="cession-parts-container">
            <?php $partIndex = 0; ?>
            <?php if (!empty($wizard['parts'])): ?>
                <?php foreach ($wizard['parts'] as $pi => $part): ?>
                    <?php $partIndex = $pi; include __DIR__ . '/_parts_row.php'; ?>
                    <?php $partIndex = $pi + 1; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                    $part = [
                        'cedant_type' => 'existant', 'cedant_associe_id' => 0, 'cedant_nom_complet' => '', 'cedant_cin' => '',
                        'cessionnaire_type' => 'existant', 'cessionnaire_associe_id' => 0, 'cessionnaire_nom_complet' => '', 'cessionnaire_cin' => '',
                        'cessionnaire_civilite' => 'M.', 'cessionnaire_date_naissance' => '', 'cessionnaire_lieu_naissance' => '',
                        'cessionnaire_nationalite' => '', 'cessionnaire_adresse' => '', 'cessionnaire_telephone' => '', 'cessionnaire_email' => '', 'cessionnaire_qualite' => '', 'cessionnaire_parts' => 0, 'cessionnaire_capital_detenu' => '', 'cessionnaire_est_gerant' => 0,
                        'parts_cedees' => '', 'prix_unitaire' => '', 'prix_total' => '',
                    ];
                    $partIndex = 0; include __DIR__ . '/_parts_row.php';
                    $partIndex = 1;
                ?>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-info" id="add-cession-part" style="margin-top:8px" data-part-index="<?= $partIndex ?>">
            <span class="material-symbols-outlined">add</span> Ajouter une ligne
        </button>
    </div>

    <div class="footer-actions">
        <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 2])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>

<script>
(function(){
    function randFrom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
    function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function randDate(start, end) {
        var d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
    }
    var hommes = ['ALAOUI', 'BENALI', 'CHERKAOUI', 'DAHMANI', 'EL FASSI', 'FAHIMI', 'GHAZI', 'HAMMADI'];
    var prenoms = ['Mohamed', 'Ahmed', 'Hassan', 'Omar', 'Youssef', 'Karim', 'Mehdi', 'Said'];

    // Sync cedant hidden fields when selection changes
    document.querySelectorAll('.cedant-select').forEach(function(sel) {
        function syncCedant() {
            var row = sel.closest('.cession-part-row');
            if (!row) return;
            var opt = sel.options[sel.selectedIndex];
            row.querySelector('.cedant-nom-hidden').value = opt ? (opt.getAttribute('data-nom') || '') : '';
            row.querySelector('.cedant-cin-hidden').value = opt ? (opt.getAttribute('data-cin') || '') : '';
        }
        sel.addEventListener('change', syncCedant);
        syncCedant();
    });

    // Toggle cessionnaire fields
    document.querySelectorAll('.cessionnaire-type').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var row = this.closest('.cession-part-row');
            if (!row) return;
            row.querySelector('.cessionnaire-existing-fields').style.display = this.value === 'nouveau' ? 'none' : '';
            row.querySelector('.cessionnaire-new-fields').style.display = this.value === 'nouveau' ? '' : 'none';
        });
    });

    // Calculate prix_total
    function calcPrixTotal() {
        var row = this.closest('.cession-part-row');
        if (!row) return;
        var parts = parseFloat(row.querySelector('.parts-cedees-input')?.value.replace(',', '.')) || 0;
        var pu = parseFloat(row.querySelector('.prix-unitaire-input')?.value.replace(',', '.')) || 0;
        var ptInput = row.querySelector('.prix-total-input');
        if (ptInput) ptInput.value = (parts * pu).toFixed(2).replace('.', ',');
    }

    document.querySelectorAll('.parts-cedees-input, .prix-unitaire-input').forEach(function(inp) {
        inp.addEventListener('input', calcPrixTotal);
    });

    // Calculate parts from pourcentage
    document.querySelectorAll('.pourcentage-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            var row = this.closest('.cession-part-row');
            if (!row) return;
            var pct = parseFloat(this.value.replace(',', '.')) || 0;
            var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
            var partsInput = row.querySelector('.parts-cedees-input');
            if (partsInput && pct > 0 && totalParts > 0) {
                partsInput.value = Math.round((pct / 100) * totalParts);
                calcPrixTotal.call(partsInput);
            }
        });
    });

    // Add new part row
    document.getElementById('add-cession-part')?.addEventListener('click', function() {
        var container = document.getElementById('cession-parts-container');
        var index = parseInt(this.dataset.partIndex) || 0;
        var template = container.querySelector('.cession-part-row');
        if (!template) return;
        var clone = template.cloneNode(true);
        var suffix = '[' + index + ']';
        clone.querySelectorAll('[name]').forEach(function(el) {
            var name = el.getAttribute('name') || '';
            el.name = name.replace(/\[\d+\]/g, suffix);
            if (el.type === 'checkbox') {
                el.checked = false;
            } else if (el.tagName !== 'SELECT') {
                el.value = '';
            } else {
                el.selectedIndex = 0;
            }
        });
        // Reset display
        var cessNew = clone.querySelector('.cessionnaire-new-fields');
        var cessExist = clone.querySelector('.cessionnaire-existing-fields');
        if (cessNew) cessNew.style.display = 'none';
        if (cessExist) cessExist.style.display = '';
        // Update part number
        var pn = clone.querySelector('.part-number');
        if (pn) pn.textContent = index + 1;
        container.appendChild(clone);

        // Bind events
        clone.querySelectorAll('.cedant-select').forEach(function(el) {
            function syncCedant() {
                var row = el.closest('.cession-part-row');
                if (!row) return;
                var opt = el.options[el.selectedIndex];
                row.querySelector('.cedant-nom-hidden').value = opt ? (opt.getAttribute('data-nom') || '') : '';
                row.querySelector('.cedant-cin-hidden').value = opt ? (opt.getAttribute('data-cin') || '') : '';
            }
            el.addEventListener('change', syncCedant);
            syncCedant();
        });
        clone.querySelectorAll('.cessionnaire-type').forEach(function(el) {
            el.addEventListener('change', function() {
                var r = this.closest('.cession-part-row');
                if (!r) return;
                r.querySelector('.cessionnaire-existing-fields').style.display = this.value === 'nouveau' ? 'none' : '';
                r.querySelector('.cessionnaire-new-fields').style.display = this.value === 'nouveau' ? '' : 'none';
            });
        });
        clone.querySelectorAll('.parts-cedees-input, .prix-unitaire-input').forEach(function(el) {
            el.addEventListener('input', calcPrixTotal);
        });
        clone.querySelectorAll('.pourcentage-input').forEach(function(el) {
            el.addEventListener('input', function() {
                var r = this.closest('.cession-part-row');
                if (!r) return;
                var pct = parseFloat(this.value.replace(',', '.')) || 0;
                var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
                var pi = r.querySelector('.parts-cedees-input');
                if (pi && pct > 0 && totalParts > 0) {
                    pi.value = Math.round((pct / 100) * totalParts);
                    calcPrixTotal.call(pi);
                }
            });
        });
        this.dataset.partIndex = index + 1;
    });

    // Remove part row
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-part');
        if (btn) {
            var row = btn.closest('.cession-part-row');
            if (row && confirm('Supprimer cette ligne de cession ?')) {
                row.remove();
            }
        }
    });

    // Auto-fill step 3
    document.querySelectorAll('[data-fill-cession]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var step = parseInt(btn.getAttribute('data-fill-cession') || '0', 10);
            var form = btn.closest('form');
            if (!form) return;

            if (step === 3) {
                form.querySelector('[name="cession_motif"]') && (form.querySelector('[name="cession_motif"]').value = randFrom(['Cession entre associes', 'Retrait d\'un associe', 'Entree d\'un nouvel associe', 'Reorganisation du capital', 'Donation de parts']));
                var rows = form.querySelectorAll('[data-part]');
                rows.forEach(function(row) {
                    var idx = row.getAttribute('data-part');
                    // Pick an existing associate as cedant
                    var cedantSelect = row.querySelector('select[name="cedant_associe_id[' + idx + ']"]');
                    if (cedantSelect) {
                        var optns = Array.from(cedantSelect.options).filter(function(o) { return o.value; });
                        if (optns.length) cedantSelect.value = randFrom(optns).value;
                    }

                    // Set cessionnaire type to "nouveau" and fill fields
                    var cessType = row.querySelector('.cessionnaire-type');
                    if (cessType) {
                        cessType.value = 'nouveau';
                        cessType.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    var cessCivilite = row.querySelector('select[name="cessionnaire_civilite[' + idx + ']"]');
                    if (cessCivilite) {
                        var civOpts = Array.from(cessCivilite.options).filter(function(o) { return o.value; });
                        if (civOpts.length) cessCivilite.value = randFrom(civOpts).value;
                    }
                    row.querySelector('input[name="cessionnaire_nom_complet[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_nom_complet[' + idx + ']"]').value = randFrom(hommes) + ' ' + randFrom(prenoms));
                    row.querySelector('input[name="cessionnaire_cin[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_cin[' + idx + ']"]').value = 'CD' + randInt(100000, 999999));
                    row.querySelector('input[name="cessionnaire_date_naissance[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_date_naissance[' + idx + ']"]').value = randDate(new Date(1960,0,1), new Date(1995,11,31)));
                    var cessLn = row.querySelector('select[name="cessionnaire_lieu_naissance[' + idx + ']"]');
                    if (cessLn) {
                        var lnOpts = Array.from(cessLn.options).filter(function(o) { return o.value; });
                        if (lnOpts.length) cessLn.value = randFrom(lnOpts).value;
                    }
                    var cessNat = row.querySelector('select[name="cessionnaire_nationalite[' + idx + ']"]');
                    if (cessNat) {
                        var natOpts = Array.from(cessNat.options).filter(function(o) { return o.value; });
                        if (natOpts.length) cessNat.value = randFrom(natOpts).value;
                    }
                    row.querySelector('textarea[name="cessionnaire_adresse[' + idx + ']"]') && (row.querySelector('textarea[name="cessionnaire_adresse[' + idx + ']"]').value = randInt(1, 200) + ' ' + randFrom(['Avenue', 'Rue', 'Boulevard']) + ' ' + randFrom(['Liberte', 'FAR', 'Hassan II', 'Mohammed VI', 'Resistance']));
                    row.querySelector('input[name="cessionnaire_telephone[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_telephone[' + idx + ']"]').value = '06' + randInt(10000000, 99999999));
                    row.querySelector('input[name="cessionnaire_email[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_email[' + idx + ']"]').value = 'cession.' + randInt(100, 999) + '@email.ma');
                    var cessQl = row.querySelector('select[name="cessionnaire_qualite[' + idx + ']"]');
                    if (cessQl) {
                        var qOpts = Array.from(cessQl.options).filter(function(o) { return o.value; });
                        if (qOpts.length) cessQl.value = randFrom(qOpts).value;
                    }
                    row.querySelector('input[name="cessionnaire_parts[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_parts[' + idx + ']"]').value = String(randInt(100, 5000)));
                    row.querySelector('input[name="cessionnaire_capital_detenu[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_capital_detenu[' + idx + ']"]').value = String(randInt(10000, 500000)));
                    row.querySelector('input[name="cessionnaire_est_gerant[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_est_gerant[' + idx + ']"]').checked = Math.random() > 0.5);

                    // Parts & price
                    row.querySelector('input[name="parts_cedees[' + idx + ']"]') && (row.querySelector('input[name="parts_cedees[' + idx + ']"]').value = String(randInt(50, 1000)));
                    var vnCession = parseFloat(form.getAttribute('data-valeur-nominale'));
                    row.querySelector('input[name="prix_unitaire[' + idx + ']"]') && (row.querySelector('input[name="prix_unitaire[' + idx + ']"]').value = vnCession > 0 ? String(vnCession) : String(randInt(100, 2000)));
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
