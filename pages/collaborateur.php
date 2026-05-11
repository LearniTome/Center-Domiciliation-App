<?php

declare(strict_types=1);

$editingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingRecord = $editingId > 0 ? fetch_record($pdo ?? null, 'collaborateurs', $editingId) : null;
$societesOptions = fetch_societes_options($pdo ?? null);

$types = [
    'Expert-comptable',
    'Comptable agree',
    'Commissaire aux comptes',
    'Coursier',
    'Avocat',
    'Notaire',
    'Conseil juridique',
    'Banque',
    'Assurance',
    'Autre',
];

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();

    $nomComplet = field_value($_POST, 'nom_complet');
    if ($nomComplet === '') {
        set_flash('error', 'Le nom complet est obligatoire.');
        redirect_to('collaborateur', $editingId ? ['id' => $editingId] : []);
    }

    $payload = [
        'societe_id' => int_value($_POST, 'societe_id'),
        'den_ste' => field_value($_POST, 'den_ste'),
        'nom_complet' => $nomComplet,
        'fonction' => field_value($_POST, 'fonction'),
        'collaborateur_type' => field_value($_POST, 'collaborateur_type'),
        'collaborateur_code' => field_value($_POST, 'collaborateur_code'),
        'collaborateur_nom' => field_value($_POST, 'collaborateur_nom'),
        'collaborateur_ice' => field_value($_POST, 'collaborateur_ice'),
        'collaborateur_tp' => field_value($_POST, 'collaborateur_tp'),
        'collaborateur_rc' => field_value($_POST, 'collaborateur_rc'),
        'collaborateur_if' => field_value($_POST, 'collaborateur_if'),
        'collaborateur_tel_fixe' => field_value($_POST, 'collaborateur_tel_fixe'),
        'collaborateur_tel_mobile' => field_value($_POST, 'collaborateur_tel_mobile'),
        'collaborateur_adresse' => field_value($_POST, 'collaborateur_adresse'),
        'collaborateur_email' => field_value($_POST, 'collaborateur_email'),
        'email' => field_value($_POST, 'email'),
        'telephone' => field_value($_POST, 'telephone'),
        'date_debut' => field_value($_POST, 'date_debut'),
        'statut' => field_value($_POST, 'statut', 'actif'),
        'notes' => field_value($_POST, 'notes'),
    ];

    if ($editingId > 0 && $editingRecord) {
        $payload['id'] = $editingId;
        $stmt = $pdo->prepare('
            UPDATE collaborateurs
            SET societe_id = :societe_id,
                den_ste = :den_ste,
                nom_complet = :nom_complet,
                fonction = :fonction,
                collaborateur_type = :collaborateur_type,
                collaborateur_code = :collaborateur_code,
                collaborateur_nom = :collaborateur_nom,
                collaborateur_ice = :collaborateur_ice,
                collaborateur_tp = :collaborateur_tp,
                collaborateur_rc = :collaborateur_rc,
                collaborateur_if = :collaborateur_if,
                collaborateur_tel_fixe = :collaborateur_tel_fixe,
                collaborateur_tel_mobile = :collaborateur_tel_mobile,
                collaborateur_adresse = :collaborateur_adresse,
                collaborateur_email = :collaborateur_email,
                email = :email,
                telephone = :telephone,
                date_debut = :date_debut,
                statut = :statut,
                notes = :notes
            WHERE id = :id
        ');
        $stmt->execute($payload);
        set_flash('success', 'Collaborateur mis a jour.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO collaborateurs
                (societe_id, den_ste, nom_complet, fonction, collaborateur_type, collaborateur_code,
                 collaborateur_nom, collaborateur_ice, collaborateur_tp, collaborateur_rc, collaborateur_if,
                 collaborateur_tel_fixe, collaborateur_tel_mobile, collaborateur_adresse, collaborateur_email,
                 email, telephone, date_debut, statut, notes)
            VALUES
                (:societe_id, :den_ste, :nom_complet, :fonction, :collaborateur_type, :collaborateur_code,
                 :collaborateur_nom, :collaborateur_ice, :collaborateur_tp, :collaborateur_rc, :collaborateur_if,
                 :collaborateur_tel_fixe, :collaborateur_tel_mobile, :collaborateur_adresse, :collaborateur_email,
                 :email, :telephone, :date_debut, :statut, :notes)
        ');
        $stmt->execute($payload);
        set_flash('success', 'Collaborateur ajoute.');
    }

    redirect_to('collaborateurs');
}

$formData = $editingRecord ?? [
    'id' => '',
    'societe_id' => '',
    'den_ste' => '',
    'nom_complet' => '',
    'fonction' => '',
    'collaborateur_type' => '',
    'collaborateur_code' => '',
    'collaborateur_nom' => '',
    'collaborateur_ice' => '',
    'collaborateur_tp' => '',
    'collaborateur_rc' => '',
    'collaborateur_if' => '',
    'collaborateur_tel_fixe' => '',
    'collaborateur_tel_mobile' => '',
    'collaborateur_adresse' => '',
    'collaborateur_email' => '',
    'email' => '',
    'telephone' => '',
    'date_debut' => '',
    'statut' => 'actif',
    'notes' => '',
];
?>
<section class="grid two">
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2><?= $editingRecord ? 'Modifier un collaborateur' : 'Nouveau collaborateur' ?></h2>
                <p class="help-text">Expert-comptable, comptable, coursier, avocat, etc.</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>">Retour</a>
            </div>
        </div>

        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">

            <div class="form-grid">
                <h3 class="section-title">Type & Identite</h3>

                <label class="field">
                    <span>Type</span>
                    <select name="collaborateur_type">
                        <option value="">Selectionner...</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= e($t) ?>" <?= (string) $formData['collaborateur_type'] === $t ? 'selected' : '' ?>>
                                <?= e($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span>Cabinet / Societe</span>
                    <input name="den_ste" value="<?= e((string) $formData['den_ste']) ?>" placeholder="Raison sociale du cabinet">
                </label>

                <label class="field">
                    <span>Nom complet *</span>
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>" placeholder="Nom et prenom">
                </label>

                <label class="field">
                    <span>Fonction</span>
                    <input name="fonction" value="<?= e((string) $formData['fonction']) ?>" placeholder="ex: Gerant, Associe">
                </label>

                <h3 class="section-title">Identifiants legaux</h3>

                <label class="field">
                    <span>Code</span>
                    <input name="collaborateur_code" value="<?= e((string) $formData['collaborateur_code']) ?>">
                </label>

                <label class="field">
                    <span>ICE</span>
                    <input name="collaborateur_ice" value="<?= e((string) $formData['collaborateur_ice']) ?>">
                </label>

                <label class="field">
                    <span>TP</span>
                    <input name="collaborateur_tp" value="<?= e((string) $formData['collaborateur_tp']) ?>">
                </label>

                <label class="field">
                    <span>RC</span>
                    <input name="collaborateur_rc" value="<?= e((string) $formData['collaborateur_rc']) ?>">
                </label>

                <label class="field">
                    <span>IF</span>
                    <input name="collaborateur_if" value="<?= e((string) $formData['collaborateur_if']) ?>">
                </label>

                <h3 class="section-title">Contact</h3>

                <label class="field">
                    <span>Email professionnel</span>
                    <input type="email" name="collaborateur_email" value="<?= e((string) $formData['collaborateur_email']) ?>">
                </label>

                <label class="field">
                    <span>Email secondaire</span>
                    <input type="email" name="email" value="<?= e((string) $formData['email']) ?>">
                </label>

                <label class="field">
                    <span>Telephone fixe</span>
                    <input name="collaborateur_tel_fixe" value="<?= e((string) $formData['collaborateur_tel_fixe']) ?>">
                </label>

                <label class="field">
                    <span>Telephone mobile</span>
                    <input name="collaborateur_tel_mobile" value="<?= e((string) $formData['collaborateur_tel_mobile']) ?>">
                </label>

                <label class="field">
                    <span>Telephone (autre)</span>
                    <input name="telephone" value="<?= e((string) $formData['telephone']) ?>">
                </label>

                <label class="field full">
                    <span>Adresse</span>
                    <textarea name="collaborateur_adresse"><?= e((string) $formData['collaborateur_adresse']) ?></textarea>
                </label>

                <h3 class="section-title">Informations</h3>

                <label class="field">
                    <span>Date debut</span>
                    <input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>">
                </label>

                <label class="field">
                    <span>Statut</span>
                    <select name="statut">
                        <?php foreach (['actif', 'inactif', 'archive'] as $statut): ?>
                            <option value="<?= e($statut) ?>" <?= (string) $formData['statut'] === $statut ? 'selected' : '' ?>>
                                <?= e(ucfirst($statut)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span>Lie a la societe</span>
                    <select name="societe_id">
                        <option value="">Aucune</option>
                        <?php foreach ($societesOptions as $societe): ?>
                            <option value="<?= e((string) $societe['id']) ?>" <?= (string) $formData['societe_id'] === (string) $societe['id'] ? 'selected' : '' ?>>
                                <?= e($societe['raison_sociale']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field full">
                    <span>Notes</span>
                    <textarea name="notes"><?= e((string) $formData['notes']) ?></textarea>
                </label>
            </div>

            <button type="submit"><?= $editingRecord ? 'Mettre a jour' : 'Enregistrer' ?></button>
        </form>
    </article>

    <?php if ($editingRecord): ?>
        <article class="card stack">
            <div class="section-header">
                <div>
                    <h2>Apercu</h2>
                    <p class="help-text">Informations du collaborateur.</p>
                </div>
            </div>

            <div class="info-grid">
                <div><strong>Type</strong><span><?= e($formData['collaborateur_type'] ?: '-') ?></span></div>
                <div><strong>Cabinet</strong><span><?= e($formData['den_ste'] ?: '-') ?></span></div>
                <div><strong>Nom complet</strong><span><?= e($formData['nom_complet'] ?: '-') ?></span></div>
                <div><strong>Fonction</strong><span><?= e($formData['fonction'] ?: '-') ?></span></div>
                <div><strong>ICE</strong><span><?= e($formData['collaborateur_ice'] ?: '-') ?></span></div>
                <div><strong>RC</strong><span><?= e($formData['collaborateur_rc'] ?: '-') ?></span></div>
                <div><strong>IF</strong><span><?= e($formData['collaborateur_if'] ?: '-') ?></span></div>
                <div><strong>TP</strong><span><?= e($formData['collaborateur_tp'] ?: '-') ?></span></div>
                <div><strong>Email</strong><span><?= e($formData['collaborateur_email'] ?: $formData['email'] ?: '-') ?></span></div>
                <div><strong>Telephone</strong><span><?= e($formData['collaborateur_tel_mobile'] ?: $formData['collaborateur_tel_fixe'] ?: $formData['telephone'] ?: '-') ?></span></div>
                <div class="full"><strong>Adresse</strong><span><?= e($formData['collaborateur_adresse'] ?: '-') ?></span></div>
                <div><strong>Statut</strong><span><?= e($formData['statut'] ?: '-') ?></span></div>
                <div><strong>Date debut</strong><span><?= e($formData['date_debut'] ?: '-') ?></span></div>
            </div>

            <?php if ($formData['notes']): ?>
                <div class="field full">
                    <strong>Notes</strong>
                    <p><?= e($formData['notes']) ?></p>
                </div>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</section>
