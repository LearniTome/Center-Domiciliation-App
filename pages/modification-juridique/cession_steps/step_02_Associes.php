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
            'associe_est_gerant' => !empty($_POST['associe_est_gerant'][$i]) ? '1' : '0',
        ];
    }

    if (empty($wizard['associes'])) {
        set_flash('error', 'Ajoutez au moins un associe.');
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

    <div class="section-header">
        <div style="display:flex;align-items:center;gap:8px"><h2>Associes</h2><p class="help-text" style="margin:0">Ajoutez les associes de la societe</p></div>
        <div style="display:flex;align-items:center;gap:8px">
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
                    <label class="field">
                        <span>Nombre de parts</span>
                        <input type="number" name="associe_parts[<?= $ai ?>]" value="<?= e($assoc['associe_parts'] ?? '') ?>" placeholder="100">
                    </label>
                    <label class="field" style="justify-content:center">
                        <label style="display:flex;align-items:center;gap:6px;padding:6px 0">
                            <input type="checkbox" name="associe_est_gerant[<?= $ai ?>]" value="1" <?= ($assoc['associe_est_gerant'] ?? '0') === '1' ? 'checked' : '' ?>>
                            Gerant
                        </label>
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
                    <label class="field" style="justify-content:center">
                        <label style="display:flex;align-items:center;gap:6px;padding:6px 0">
                            <input type="checkbox" name="associe_est_gerant[0]" value="1">
                            Gerant
                        </label>
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
                <label class="field" style="justify-content:center">
                    <label style="display:flex;align-items:center;gap:6px;padding:6px 0">
                        <input data-field-name="associe_est_gerant" type="checkbox" value="1">
                        Gerant
                    </label>
                </label>
            </div>
        </div>
    </template>

    <div class="footer-actions" style="margin-top:12px">
        <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
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
    });

    associeContainer?.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-remove-associe]');
        if (btn) {
            var card = btn.closest('[data-associe-item]');
            if (card && confirm('Retirer cet associe ?')) {
                card.remove();
                reindexAssocies();
            }
        }
    });

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
            associeCards.forEach(function(card) {
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
                card.querySelector('[name^="associe_parts"]') && (card.querySelector('[name^="associe_parts"]').value = String(randInt(100, 5000)));
                card.querySelector('[name^="associe_capital_detenu"]') && (card.querySelector('[name^="associe_capital_detenu"]').value = String(randInt(10000, 500000)));
                card.querySelector('[name^="associe_est_gerant"]') && (card.querySelector('[name^="associe_est_gerant"]').checked = Math.random() > 0.5);
            });

            form.querySelectorAll('input, select, textarea').forEach(function(f) {
                f.dispatchEvent(new Event('input', { bubbles: true }));
                f.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
})();
</script>
<?php endif; ?>
