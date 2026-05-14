<?php

declare(strict_types=1);

$societeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$societe = $societeId > 0 ? fetch_record($pdo ?? null, 'societes', $societeId) : null;

if (!$societe) {
    http_response_code(404);
    ?>
    <section class="card stack">
        <h2>Societe introuvable</h2>
        <p>La fiche demandee n'existe pas ou n'est plus disponible.</p>
        <a class="btn" href="<?= e(app_url('societes')) ?>">Retour aux societes</a>
    </section>
    <?php
    return;
}

$editing = isset($_GET['edit']) && $_GET['edit'] === '1';

if (is_post() && isset($_POST['validate_submit']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) === 0) {
        set_flash('error', 'Selectionnez au moins un document.');
        redirect_to('societe', ['id' => $societeId]);
    }
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE valide = 0 AND id IN ($placeholders)");
    $stmt->execute(array_map('intval', $selected));
    $docs = $stmt->fetchAll();
    $updateStmt = $pdo->prepare("UPDATE documents_generes SET valide = 1, fichier_docx = :fichier_docx, fichier_pdf = :fichier_pdf WHERE id = :id");
    foreach ($docs as $doc) {
        $oldDocx = $doc['fichier_docx'];
        $newDocx = str_replace('_Brouillon.docx', '.docx', $oldDocx);
        if ($oldDocx !== $newDocx && file_exists($oldDocx)) {
            rename($oldDocx, $newDocx);
        }
        $newPdf = $doc['fichier_pdf'];
        if ($newPdf !== null) {
            $renamedPdf = str_replace('_Brouillon.pdf', '.pdf', $newPdf);
            if ($newPdf !== $renamedPdf && file_exists($newPdf)) {
                rename($newPdf, $renamedPdf);
                $newPdf = $renamedPdf;
            }
        }
        $updateStmt->execute([
            'fichier_docx' => $newDocx,
            'fichier_pdf' => $newPdf,
            'id' => $doc['id'],
        ]);
    }
    set_flash('success', count($selected) . ' document(s) valide(s).');
    redirect_to('societe', ['id' => $societeId]);
}

if (is_post() && isset($_POST['delete_submit']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) > 0) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $docs = $stmt->fetchAll();
        foreach ($docs as $doc) {
            if (file_exists($doc['fichier_docx'])) unlink($doc['fichier_docx']);
            if ($doc['fichier_pdf'] && file_exists($doc['fichier_pdf'])) unlink($doc['fichier_pdf']);
        }
        $stmt = $pdo->prepare("DELETE FROM documents_generes WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        set_flash('error', count($selected) . ' document(s) supprime(s).');
        redirect_to('societe', ['id' => $societeId]);
    }
}

if (is_post() && !isset($_POST['validate_submit']) && !isset($_POST['delete_submit']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $stmt = $pdo->prepare('
        UPDATE societes SET
            dossier_domiciliation = :dossier_domiciliation,
            raison_sociale = :raison_sociale,
            forme_juridique = :forme_juridique,
            ice = :ice,
            date_ice = :date_ice,
            rc = :rc,
            if_number = :if_number,
            capital = :capital,
            part_social = :part_social,
            valeur_nominale = :valeur_nominale,
            date_exp_cert_neg = :date_exp_cert_neg,
            ste_adress = :ste_adress,
            ville = :ville,
            tribunal = :tribunal,
            email = :email,
            telephone = :telephone,
            type_generation = :type_generation,
            procedure_creation = :procedure_creation,
            mode_depot_creation = :mode_depot_creation
        WHERE id = :id
    ');
    $stmt->execute([
        'dossier_domiciliation' => field_value($_POST, 'dossier_domiciliation'),
        'raison_sociale' => field_value($_POST, 'raison_sociale'),
        'forme_juridique' => field_value($_POST, 'forme_juridique'),
        'ice' => field_value($_POST, 'ice'),
        'date_ice' => field_value($_POST, 'date_ice'),
        'rc' => field_value($_POST, 'rc'),
        'if_number' => field_value($_POST, 'if_number'),
        'capital' => money_value($_POST, 'capital'),
        'part_social' => int_value($_POST, 'part_social'),
        'valeur_nominale' => money_value($_POST, 'valeur_nominale'),
        'date_exp_cert_neg' => field_value($_POST, 'date_exp_cert_neg'),
        'ste_adress' => field_value($_POST, 'ste_adress'),
        'ville' => field_value($_POST, 'ville'),
        'tribunal' => field_value($_POST, 'tribunal'),
        'email' => field_value($_POST, 'email'),
        'telephone' => field_value($_POST, 'telephone'),
        'type_generation' => field_value($_POST, 'type_generation'),
        'procedure_creation' => field_value($_POST, 'procedure_creation'),
        'mode_depot_creation' => field_value($_POST, 'mode_depot_creation'),
        'id' => $societeId,
    ]);
    set_flash('success', 'Societe mise a jour.');
    redirect_to('societe', ['id' => $societeId]);
}

if ($editing) {
    $villesOptions = fetch_reference_options($pdo ?? null, 'ref_villes', 'ville');
    $tribunauxOptions = fetch_reference_options($pdo ?? null, 'ref_tribunaux', 'tribunal');
    $adressesOptions = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');
    $formesJuridiquesOptions = fetch_reference_options($pdo ?? null, 'ref_formes_juridiques', 'forme_juridique');
}

$associes = ($pdo ?? null) instanceof PDO
    ? (function (PDO $pdo, int $societeId): array {
        $stmt = $pdo->prepare('SELECT nom_complet, cin, nationalite, qualite_associe, parts, is_gerant FROM associes WHERE societe_id = :societe_id ORDER BY id DESC');
        $stmt->execute(['societe_id' => $societeId]);
        return $stmt->fetchAll();
    })($pdo, $societeId)
    : [];

$contrats = ($pdo ?? null) instanceof PDO
    ? (function (PDO $pdo, int $societeId): array {
        $stmt = $pdo->prepare('SELECT id, type_contrat, date_debut, date_fin, statut, montant_total_ht_contrat AS montant_total_loyer FROM contrats WHERE societe_id = :societe_id ORDER BY id DESC');
        $stmt->execute(['societe_id' => $societeId]);
        return $stmt->fetchAll();
    })($pdo, $societeId)
    : [];

$collabCount = ($pdo ?? null) instanceof PDO
    ? (int) $pdo->prepare("SELECT COUNT(*) FROM collaborateurs")->fetchColumn()
    : 0;

$documents = fetch_all_documents($pdo ?? null, $societeId);
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem">
    <h2 style="margin:0"><?= e($societe['raison_sociale']) ?></h2>
    <div class="table-actions">
        <?php if ($editing): ?>
            <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => $societeId])) ?>"><span class="mdi mdi-close"></span> Annuler</a>
        <?php else: ?>
            <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => $societeId, 'edit' => '1'])) ?>"><span class="mdi mdi-pencil"></span> Modifier</a>
            <a class="btn btn-info" href="<?= e(app_url('generation', ['societe_id' => $societeId])) ?>"><span class="mdi mdi-file-sync"></span> Generer documents</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>"><span class="mdi mdi-arrow-left"></span> Retour</a>
    </div>
</div>

<section class="stats small" style="margin-bottom:1rem">
    <article class="stat">
        <span>Societe</span>
        <strong><?= e($societe['forme_juridique'] ?: '-') ?></strong>
    </article>
    <article class="stat">
        <span>Associes</span>
        <strong><?= count($associes) ?></strong>
    </article>
    <article class="stat">
        <span>Contrats</span>
        <strong><?= count($contrats) ?></strong>
    </article>
    <article class="stat">
        <span>Collaborateurs</span>
        <strong><?= $collabCount ?></strong>
    </article>
    <article class="stat">
        <span>Documents</span>
        <strong><?= count($documents) ?></strong>
    </article>
    <article class="stat">
        <span>Dossier</span>
        <strong><?= e($societe['dossier_domiciliation'] ?: '-') ?></strong>
    </article>
    <article class="stat">
        <span>Ville</span>
        <strong><?= e($societe['ville'] ?: '-') ?></strong>
    </article>
</section>

<?php if ($editing): ?>
    <section class="card stack">
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <div class="form-grid">
                <h3 class="section-title">Procedure</h3>
                <label class="field">
                    <span>Type generation</span>
                    <select name="type_generation">
                        <option value="">Selectionner</option>
                        <option value="creation" <?= (string) $societe['type_generation'] === 'creation' ? 'selected' : '' ?>>Creation</option>
                        <option value="domiciliation" <?= (string) $societe['type_generation'] === 'domiciliation' ? 'selected' : '' ?>>Domiciliation</option>
                    </select>
                </label>
                <label class="field">
                    <span>Procedure creation</span>
                    <select name="procedure_creation">
                        <option value="">Selectionner</option>
                        <option value="normal" <?= (string) $societe['procedure_creation'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="acceleree" <?= (string) $societe['procedure_creation'] === 'acceleree' ? 'selected' : '' ?>>Acceleree</option>
                    </select>
                </label>
                <label class="field">
                    <span>Mode depot creation</span>
                    <select name="mode_depot_creation">
                        <option value="">Selectionner</option>
                        <option value="depot_physique" <?= (string) $societe['mode_depot_creation'] === 'depot_physique' ? 'selected' : '' ?>>Depot Physique</option>
                        <option value="depot_en_ligne" <?= (string) $societe['mode_depot_creation'] === 'depot_en_ligne' ? 'selected' : '' ?>>Depot En Ligne</option>
                    </select>
                </label>
                <h3 class="section-title">Identifiants</h3>
                <label class="field">
                    <span>Dossier domiciliation</span>
                    <input name="dossier_domiciliation" value="<?= e((string) $societe['dossier_domiciliation']) ?>">
                </label>
                <label class="field">
                    <span>Raison sociale</span>
                    <input name="raison_sociale" required value="<?= e((string) $societe['raison_sociale']) ?>">
                </label>
                <label class="field">
                    <span>Forme juridique</span>
                    <select name="forme_juridique" style="flex:1">
                        <option value="">Selectionner</option>
                        <?php foreach ($formesJuridiquesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societe['forme_juridique'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>ICE</span>
                    <input name="ice" value="<?= e((string) $societe['ice']) ?>">
                </label>
                <label class="field">
                    <span>Date de cert. negatif</span>
                    <input type="date" name="date_ice" value="<?= e((string) $societe['date_ice']) ?>">
                </label>
                <label class="field">
                    <span>Date exp. cert. negatif</span>
                    <input type="date" name="date_exp_cert_neg" value="<?= e((string) $societe['date_exp_cert_neg']) ?>">
                </label>
                <label class="field">
                    <span>RC</span>
                    <input name="rc" value="<?= e((string) $societe['rc']) ?>">
                </label>
                <label class="field">
                    <span>IF</span>
                    <input name="if_number" value="<?= e((string) $societe['if_number']) ?>">
                </label>
                <h3 class="section-title">Capital</h3>
                <label class="field">
                    <span>Capital</span>
                    <input type="number" step="0.01" name="capital" value="<?= e((string) ($societe['capital'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Part social</span>
                    <input type="number" name="part_social" value="<?= e((string) ($societe['part_social'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Valeur nominale</span>
                    <input type="number" step="0.01" name="valeur_nominale" value="<?= e((string) ($societe['valeur_nominale'] ?? '')) ?>">
                </label>
                <h3 class="section-title">Adresse</h3>
                <label class="field full">
                    <span>Adresse de reference</span>
                    <select name="ste_adress" style="flex:1">
                        <option value="">Selectionner</option>
                        <?php foreach ($adressesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societe['ste_adress'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Ville</span>
                    <select name="ville" style="flex:1">
                        <option value="">Selectionner</option>
                        <?php foreach ($villesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societe['ville'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Tribunal</span>
                    <select name="tribunal">
                        <option value="">Selectionner</option>
                        <?php foreach ($tribunauxOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societe['tribunal'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <h3 class="section-title">Contact</h3>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e((string) $societe['email']) ?>">
                </label>
                <label class="field">
                    <span>Telephone</span>
                    <input name="telephone" value="<?= e((string) $societe['telephone']) ?>">
                </label>
            </div>
            <div>
                <button class="btn btn-next" type="submit"><span class="mdi mdi-check"></span> Enregistrer</button>
            </div>
        </form>
    </section>
<?php else: ?>
    <article class="card stack">
        <div class="form-grid">
            <h3 class="section-title">Procedure</h3>
            <div class="info-grid">
                <div><span>Type generation</span><strong><?= e($societe['type_generation'] ?: '-') ?></strong></div>
                <div><span>Procedure creation</span><strong><?= e($societe['procedure_creation'] ?: '-') ?></strong></div>
                <div class="full"><span>Mode depot creation</span><strong><?= e($societe['mode_depot_creation'] ?: '-') ?></strong></div>
            </div>

            <h3 class="section-title">Identifiants</h3>
            <div class="info-grid">
                <div><span>Forme juridique</span><strong><?= e($societe['forme_juridique'] ?: '-') ?></strong></div>
                <div><span>ICE</span><strong><?= e($societe['ice'] ?: '-') ?></strong></div>
                <div><span>Date cert. negatif</span><strong><?= e($societe['date_ice'] ?: '-') ?></strong></div>
                <div><span>Date exp. cert. neg.</span><strong><?= e($societe['date_exp_cert_neg'] ?: '-') ?></strong></div>
                <div><span>RC</span><strong><?= e($societe['rc'] ?: '-') ?></strong></div>
                <div><span>IF</span><strong><?= e($societe['if_number'] ?: '-') ?></strong></div>
            </div>

            <h3 class="section-title">Capital</h3>
            <div class="info-grid">
                <div><span>Capital</span><strong><?= format_money($societe['capital'] !== null ? (float) $societe['capital'] : null) ?></strong></div>
                <div><span>Part social</span><strong><?= format_number($societe['part_social'] !== null ? (float) $societe['part_social'] : null) ?></strong></div>
                <div><span>Valeur nominale</span><strong><?= format_money($societe['valeur_nominale'] !== null ? (float) $societe['valeur_nominale'] : null) ?></strong></div>
            </div>

            <h3 class="section-title">Adresse</h3>
            <div class="info-grid">
                <div class="full"><span>Adresse reference</span><strong><?= e($societe['ste_adress'] ?: '-') ?></strong></div>
                <div><span>Ville</span><strong><?= e($societe['ville'] ?: '-') ?></strong></div>
                <div><span>Tribunal</span><strong><?= e($societe['tribunal'] ?: '-') ?></strong></div>
            </div>

            <h3 class="section-title">Contact</h3>
            <div class="info-grid">
                <div><span>Email</span><strong><?= e($societe['email'] ?: '-') ?></strong></div>
                <div><span>Telephone</span><strong><?= e($societe['telephone'] ?: '-') ?></strong></div>
            </div>
        </div>
    </article>
<?php endif; ?>

<article class="card">
    <div class="section-header">
        <h3>Associes lies (<?= count($associes) ?>)</h3>
        <a class="btn btn-info" href="<?= e(app_url('associes')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
    </div>
    <?php if (!$associes): ?>
        <p class="table-empty">Aucun associe lie a cette societe.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>CIN</th>
                    <th>Nationalite</th>
                    <th>Qualite</th>
                    <th>Gerant</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['cin']) ?></td>
                        <td><?= e($associe['nationalite']) ?></td>
                        <td><?= e($associe['qualite_associe'] ?: '-') ?></td>
                        <td><?= (int) $associe['is_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</article>

<article class="card">
    <div class="section-header">
        <h3>Contrats lies (<?= count($contrats) ?>)</h3>
        <a class="btn btn-info" href="<?= e(app_url('contrats')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
    </div>
    <?php if (!$contrats): ?>
        <p class="table-empty">Aucun contrat lie a cette societe.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Periode</th>
                    <th>Statut</th>
                    <th>Montant Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e(($contrat['date_debut'] ?: '-') . ' -> ' . ($contrat['date_fin'] ?: '-')) ?></td>
                        <td><span class="statut-badge <?= strtolower($contrat['statut']) === 'actif' ? 'actif' : 'resilie' ?>"><?= e($contrat['statut']) ?></span></td>
                        <td><?= format_money($contrat['montant_total_loyer'] !== null ? (float) $contrat['montant_total_loyer'] : null) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</article>

<article class="card">
    <div class="section-header">
        <h3>Documents generes (<?= count($documents) ?>)</h3>
        <div class="table-actions">
            <a class="btn btn-info" href="<?= e(app_url('generation', ['societe_id' => $societeId])) ?>"><span class="mdi mdi-file-sync"></span> Generer documents</a>
            <a class="btn btn-info" href="<?= e(app_url('documents', ['societe_id' => $societeId])) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
    </div>
    <?php if (!$documents): ?>
        <div class="empty-state">
            <span class="mdi mdi-file-document-outline" style="font-size:2rem;color:var(--text-secondary)"></span>
            <p class="table-empty">Aucun document genere.</p>
        </div>
    <?php else: ?>
        <form method="post" id="docs-form">
            <?= csrf_input() ?>
            <div class="table-scroll">
                <table>
                    <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" id="select-all-docs"></th>
                        <th>Type</th>
                        <th>Document</th>
                        <th>Taille</th>
                        <th>Statut</th>
                        <th>Date creation</th>
                        <th>Modification</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>"></td>
                            <td><?= e($doc['doc_type'] ?? '-') ?></td>
                            <td><?= e(basename($doc['fichier_docx'])) ?></td>
                            <td><?= $doc['taille_ko'] ? number_format((float) $doc['taille_ko'], 1) . ' Ko' : '-' ?></td>
                            <td>
                                <span class="statut-badge <?= $doc['valide'] ? 'valide' : 'brouillon' ?>">
                                    <?= $doc['valide'] ? 'Valide' : 'Brouillon' ?>
                                </span>
                            </td>
                            <td><?= e(date('d/m/Y H:i', strtotime((string) $doc['created_at']))) ?></td>
                            <td><span class="help-text"><?php $modifTime = file_exists($doc['fichier_docx']) ? filemtime($doc['fichier_docx']) : null; echo $modifTime ? date('d/m/Y H:i', $modifTime) : '-'; ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn-icon" href="<?= e(word_url($doc['fichier_docx'])) ?>" title="Ouvrir dans Word">
                                            <span class="mdi mdi-file-word"></span>
                                        </a>
                                        <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="mdi mdi-download"></span>
                                        </a>
                                        <?php if ($doc['fichier_pdf']): ?>
                                            <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                <span class="mdi mdi-file-pdf"></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$doc['valide']): ?>
                                            <a class="btn-icon" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('docs-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='validate_submit'; h.value='1'; f.appendChild(h); f.submit();} })();" title="Valider">
                                                <span class="mdi mdi-file-check"></span>
                                            </a>
                                        <?php endif; ?>
                                        <a class="btn-icon danger" href="#" onclick="event.preventDefault(); if(!confirm('Supprimer ce document ?')) return; (function(){ var f=document.getElementById('docs-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='delete_submit'; h.value='1'; f.appendChild(h); f.submit();} })();" title="Supprimer">
                                            <span class="mdi mdi-delete"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-actions" style="margin-top:12px">
                <button class="btn btn-next" type="submit" name="validate_submit" value="1">
                    <span class="mdi mdi-file-check"></span> Valider la selection
                </button>
                <button class="btn btn-back" type="submit" name="delete_submit" value="1">
                    <span class="mdi mdi-delete"></span> Supprimer la selection
                </button>
            </div>
        </form>
        <script>
        document.getElementById('select-all-docs')?.addEventListener('click', function() {
            var checkboxes = document.querySelectorAll('#docs-form input[name="selected_files[]"]');
            checkboxes.forEach(function(c) { c.checked = this.checked; }.bind(this));
        });
        </script>
    <?php endif; ?>
</article>
