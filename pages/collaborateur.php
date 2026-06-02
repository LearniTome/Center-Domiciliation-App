<?php

declare(strict_types=1);

$editingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingRecord = $editingId > 0 ? fetch_record($pdo ?? null, 'collaborateurs', $editingId) : null;
$showEdit = $editingRecord && isset($_GET['edit']);

// Determine type
$collabType = '';
if ($editingRecord) {
    $collabType = $editingRecord['collaborateur_type'] ?? '';
    if (!in_array($collabType, ['interne', 'externe-pm', 'externe-pp'], true)) {
        $ds = $editingRecord['den_ste'] ?? '';
        if ((int) ($editingRecord['can_login'] ?? 0) === 1) {
            $collabType = 'interne';
        } else {
            $collabType = ($ds && $ds !== 'NULL') ? 'externe-pm' : 'externe-pp';
        }
    }
} else {
    $collabType = field_value($_GET, 'type', '');
}

// Fetch roles from DB
$roles = [];
$rolesInterne = [];
$rolesExterne = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, nom, is_internal FROM roles ORDER BY sort_order ASC, nom ASC');
    $roles = $stmt->fetchAll();
    foreach ($roles as $r) {
        if ((int) ($r['is_internal'] ?? 0)) {
            $rolesInterne[] = $r;
        } else {
            $rolesExterne[] = $r;
        }
    }
}

// Fetch fonctions from DB
$fonctions = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, fonction FROM ref_fonctions ORDER BY sort_order ASC, fonction ASC');
    $fonctions = $stmt->fetchAll();
}

$typeOptions = ['interne', 'externe-pm', 'externe-pp'];

// --- POST ---
if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();

    $savedType = field_value($_POST, 'collaborateur_type');
    $roleId = int_value($_POST, 'role_id');

    // Build payload based on type
    if ($savedType === 'interne') {
        $nomComplet = field_value($_POST, 'nom_complet');
        if ($nomComplet === '') {
            set_flash('error', 'Le nom complet est obligatoire.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => 'interne']);
        }
        $payload = [
            'den_ste' => '',
            'nom_complet' => $nomComplet,
            'fonction' => field_value($_POST, 'fonction'),
            'collaborateur_code' => '',
            'collaborateur_nom' => '',
            'collaborateur_ice' => '',
            'collaborateur_tp' => '',
            'collaborateur_rc' => '',
            'collaborateur_if' => '',
            'collaborateur_tel_fixe' => '',
            'collaborateur_tel_mobile' => field_value($_POST, 'collaborateur_tel_mobile'),
            'collaborateur_adresse' => '',
            'collaborateur_email' => field_value($_POST, 'collaborateur_email'),
            'email' => field_value($_POST, 'email'),
            'telephone' => '',
            'date_debut' => field_value($_POST, 'date_debut'),
            'statut' => field_value($_POST, 'statut', 'actif'),
            'notes' => field_value($_POST, 'notes'),
            'role_id' => $roleId,
            'can_login' => (int) ($_POST['can_login'] ?? 0),
            'collaborateur_type' => 'interne',
        ];
        $password = field_value($_POST, 'password');
        $passwordConfirm = field_value($_POST, 'password_confirm');
        if ($payload['can_login'] && $password !== '') {
            if (strlen($password) < 6) { set_flash('error', 'Le mot de passe doit contenir au moins 6 caracteres.'); redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => 'interne']); }
            if ($password !== $passwordConfirm) { set_flash('error', 'Les mots de passe ne correspondent pas.'); redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => 'interne']); }
        }
    } elseif ($savedType === 'externe-pm') {
        $denSte = field_value($_POST, 'den_ste');
        if ($denSte === '') {
            set_flash('error', 'La raison sociale est obligatoire.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => 'externe-pm']);
        }
        $payload = [
            'den_ste' => $denSte,
            'nom_complet' => $denSte,
            'fonction' => '',
            'collaborateur_code' => field_value($_POST, 'collaborateur_code'),
            'collaborateur_nom' => '',
            'collaborateur_ice' => field_value($_POST, 'collaborateur_ice'),
            'collaborateur_tp' => field_value($_POST, 'collaborateur_tp'),
            'collaborateur_rc' => field_value($_POST, 'collaborateur_rc'),
            'collaborateur_if' => field_value($_POST, 'collaborateur_if'),
            'collaborateur_tel_fixe' => field_value($_POST, 'collaborateur_tel_fixe'),
            'collaborateur_tel_mobile' => field_value($_POST, 'collaborateur_tel_mobile'),
            'collaborateur_adresse' => field_value($_POST, 'collaborateur_adresse'),
            'collaborateur_email' => field_value($_POST, 'collaborateur_email'),
            'email' => '',
            'telephone' => '',
            'date_debut' => '',
            'statut' => field_value($_POST, 'statut', 'actif'),
            'notes' => field_value($_POST, 'notes'),
            'role_id' => $roleId,
            'can_login' => 0,
            'collaborateur_type' => 'externe-pm',
        ];
    } else {
        // externe-pp
        $nomComplet = field_value($_POST, 'nom_complet');
        if ($nomComplet === '') {
            set_flash('error', 'Le nom complet est obligatoire.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => 'externe-pp']);
        }
        $payload = [
            'den_ste' => '',
            'nom_complet' => $nomComplet,
            'fonction' => field_value($_POST, 'fonction'),
            'collaborateur_code' => '',
            'collaborateur_nom' => '',
            'collaborateur_ice' => '',
            'collaborateur_tp' => '',
            'collaborateur_rc' => '',
            'collaborateur_if' => '',
            'collaborateur_tel_fixe' => '',
            'collaborateur_tel_mobile' => field_value($_POST, 'collaborateur_tel_mobile'),
            'collaborateur_adresse' => field_value($_POST, 'collaborateur_adresse'),
            'collaborateur_email' => field_value($_POST, 'collaborateur_email'),
            'email' => '',
            'telephone' => '',
            'date_debut' => '',
            'statut' => field_value($_POST, 'statut', 'actif'),
            'notes' => field_value($_POST, 'notes'),
            'role_id' => $roleId,
            'can_login' => 0,
            'collaborateur_type' => $savedType,
        ];
    }

    // Duplicate email check
    $checkEmail = $payload['collaborateur_email'] ?: $payload['email'];
    if ($checkEmail !== '') {
        $dupSql = 'SELECT id FROM collaborateurs WHERE (collaborateur_email = :email OR email = :email2) AND statut != \'archive\'';
        $dupParams = ['email' => $checkEmail, 'email2' => $checkEmail];
        if ($editingId > 0) { $dupSql .= ' AND id != :exclude_id'; $dupParams['exclude_id'] = $editingId; }
        $dupSql .= ' LIMIT 1';
        $dupStmt = $pdo->prepare($dupSql);
        $dupStmt->execute($dupParams);
        if ($dupStmt->fetch()) {
            set_flash('error', 'Un collaborateur avec cet email existe deja.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => $savedType]);
        }
    }

    try {
        if ($editingId > 0 && $editingRecord) {
            $sql = '
                UPDATE collaborateurs
                SET den_ste = :den_ste, nom_complet = :nom_complet, fonction = :fonction,
                    collaborateur_code = :collaborateur_code, collaborateur_nom = :collaborateur_nom,
                    collaborateur_ice = :collaborateur_ice, collaborateur_tp = :collaborateur_tp,
                    collaborateur_rc = :collaborateur_rc, collaborateur_if = :collaborateur_if,
                    collaborateur_tel_fixe = :collaborateur_tel_fixe, collaborateur_tel_mobile = :collaborateur_tel_mobile,
                    collaborateur_adresse = :collaborateur_adresse, collaborateur_email = :collaborateur_email,
                    email = :email, telephone = :telephone, date_debut = :date_debut,
                    statut = :statut, notes = :notes, role_id = :role_id, can_login = :can_login,
                    collaborateur_type = :collaborateur_type
            ';
            $params = $payload;
            $params['id'] = $editingId;
            $doPassword = ($payload['can_login'] && isset($password) && $password !== '');
            if ($doPassword) {
                $sql .= ', password_hash = :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            set_flash('success', 'Collaborateur mis a jour.');
        } else {
            $cols = 'den_ste, nom_complet, fonction, collaborateur_code, collaborateur_nom,
                     collaborateur_ice, collaborateur_tp, collaborateur_rc, collaborateur_if,
                     collaborateur_tel_fixe, collaborateur_tel_mobile, collaborateur_adresse,
                     collaborateur_email, email, telephone, date_debut, statut, notes, role_id,
                     can_login, collaborateur_type';
            $vals = ':den_ste, :nom_complet, :fonction, :collaborateur_code, :collaborateur_nom,
                     :collaborateur_ice, :collaborateur_tp, :collaborateur_rc, :collaborateur_if,
                     :collaborateur_tel_fixe, :collaborateur_tel_mobile, :collaborateur_adresse,
                     :collaborateur_email, :email, :telephone, :date_debut, :statut, :notes, :role_id,
                     :can_login, :collaborateur_type';
            $params = $payload;
            $doPassword = ($payload['can_login'] && isset($password) && $password !== '');
            if ($doPassword) {
                $cols .= ', password_hash';
                $vals .= ', :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql = "INSERT INTO collaborateurs ({$cols}) VALUES ({$vals})";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $newId = (int) $pdo->lastInsertId();
            $who = ''; $cu = current_user(); if ($cu) $who = $cu['nom_complet'] ?? '';
            $logStmt = $pdo->prepare('INSERT INTO collaborateur_log (action, collaborateur_nom, collaborateur_email, collaborateur_id, done_by) VALUES (\'ajout\', :nom, :email, :cid, :done_by)');
            $logStmt->execute(['nom' => $payload['nom_complet'], 'email' => $payload['collaborateur_email'] ?: $payload['email'], 'cid' => $newId, 'done_by' => $who]);
            set_flash('success', 'Collaborateur ajoute.');
        }
        clear_user_cache();
    } catch (PDOException $e) {
        set_flash('error', 'Erreur : ' . $e->getMessage());
    }

    redirect_to('collaborateurs');
}

// --- Display ---
if ($editingRecord) {
    $formData = $editingRecord;
} else {
    // Build empty form data based on type
    $formData = [
        'id' => '', 'den_ste' => '', 'nom_complet' => '', 'fonction' => '',
        'collaborateur_code' => '', 'collaborateur_nom' => '', 'collaborateur_ice' => '',
        'collaborateur_tp' => '', 'collaborateur_rc' => '', 'collaborateur_if' => '',
        'collaborateur_tel_fixe' => '', 'collaborateur_tel_mobile' => '', 'collaborateur_adresse' => '',
        'collaborateur_email' => '', 'email' => '', 'telephone' => '', 'date_debut' => '',
        'statut' => 'actif', 'notes' => '', 'role_id' => null, 'can_login' => 0,
        'password_hash' => '', 'collaborateur_type' => $collabType,
    ];
}

$isCurrentUser = is_logged_in() && $editingId > 0 && (int) ($_SESSION['user_id'] ?? 0) === $editingId;
$isNew = !$editingRecord;
?>

<style>
.collab-preview .section-header { margin-bottom: 8px; }
.collab-preview .section-title { padding: 10px 0 4px; }
.collab-preview .info-grid { gap: 10px; }
</style>
<section class="grid two">
<?php if ($isNew && !in_array($collabType, $typeOptions, true)): ?>
    <article class="card stack" style="grid-column:1/-1;max-width:560px;margin:0 auto;">
        <div class="section-header">
            <h2>Nouveau collaborateur</h2>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            </div>
        </div>
        <p class="help-text">Choisissez le type de collaborateur</p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
            <a href="<?= e(app_url('collaborateur', ['type' => 'interne'])) ?>" class="card" style="text-align:center;padding:16px 12px;cursor:pointer;text-decoration:none;color:var(--text);border:2px solid var(--line);">
                <span class="material-symbols-outlined" style="font-size:2rem;color:var(--primary);display:block;margin-bottom:6px;">badge</span>
                <strong style="font-size:0.9rem;">Interne</strong>
                <p class="help-text" style="margin-top:2px;font-size:0.75rem;">Employe, Admin<br>avec acces app</p>
            </a>
            <a href="<?= e(app_url('collaborateur', ['type' => 'externe-pm'])) ?>" class="card" style="text-align:center;padding:16px 12px;cursor:pointer;text-decoration:none;color:var(--text);border:2px solid var(--line);">
                <span class="material-symbols-outlined" style="font-size:2rem;color:var(--success);display:block;margin-bottom:6px;">business</span>
                <strong style="font-size:0.9rem;">Externe PM</strong>
                <p class="help-text" style="margin-top:2px;font-size:0.75rem;">Societe, Cabinet<br>avec ICE, RC, IF</p>
            </a>
            <a href="<?= e(app_url('collaborateur', ['type' => 'externe-pp'])) ?>" class="card" style="text-align:center;padding:16px 12px;cursor:pointer;text-decoration:none;color:var(--text);border:2px solid var(--line);">
                <span class="material-symbols-outlined" style="font-size:2rem;color:var(--info);display:block;margin-bottom:6px;">person</span>
                <strong style="font-size:0.9rem;">Externe PP</strong>
                <p class="help-text" style="margin-top:2px;font-size:0.75rem;">Expert, Avocat<br>personne physique</p>
            </a>
        </div>
    </article>
<?php elseif ($isNew): ?>
    <article class="card stack">
        <div class="section-header">
            <h2>Nouveau collaborateur</h2>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            </div>
        </div>

        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
            <input type="hidden" name="collaborateur_type" value="<?= e($collabType) ?>">

            <div class="form-grid">
                <h3 class="section-title">Identite & Role</h3>

                <label class="field">
                    <span>Role / Type</span>
                    <select name="role_id">
                        <option value="">Selectionner...</option>
                        <?php $optRoles = ($collabType === 'interne') ? $rolesInterne : $rolesExterne; ?>
                        <?php foreach ($optRoles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= (string) ($formData['role_id'] ?? '') === (string) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <?php if ($collabType !== 'externe-pm'): ?>
                <label class="field">
                    <span>Nom complet *</span>
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>" placeholder="Nom et prenom">
                </label>
                <label class="field">
                    <span>Fonction</span>
                    <select name="fonction">
                        <option value="">Selectionner...</option>
                        <?php foreach ($fonctions as $f): ?>
                            <option value="<?= e((string) $f['fonction']) ?>" <?= (string) $formData['fonction'] === (string) $f['fonction'] ? 'selected' : '' ?>>
                                <?= e((string) $f['fonction']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>

                <?php if ($collabType === 'externe-pm'): ?>
                <label class="field full">
                    <span>Raison sociale *</span>
                    <input name="den_ste" required value="<?= e((string) $formData['den_ste']) ?>" placeholder="Denomination sociale">
                </label>
                <?php endif; ?>

                <?php if ($collabType === 'interne'): ?>
                <h3 class="section-title">Acces au systeme</h3>

                <label class="field" style="grid-column:1/-1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="can_login" value="1" data-toggle-password <?= (int) ($formData['can_login'] ?? 0) ? 'checked' : '' ?>>
                        <span>Peut se connecter a l application</span>
                    </label>
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Mot de passe</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="6 caracteres min">
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Confirmer le mot de passe</span>
                    <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Retapez le mot de passe">
                </label>
                <?php endif; ?>

                <?php if ($collabType === 'externe-pm'): ?>
                <h3 class="section-title">Identifiants legaux</h3>
                <label class="field"><span>Code</span><input name="collaborateur_code" value="<?= e((string) $formData['collaborateur_code']) ?>"></label>
                <label class="field"><span>ICE *</span><input name="collaborateur_ice" value="<?= e((string) $formData['collaborateur_ice']) ?>" placeholder="Identifiant ICE"></label>
                <label class="field"><span>TP</span><input name="collaborateur_tp" value="<?= e((string) $formData['collaborateur_tp']) ?>"></label>
                <label class="field"><span>RC</span><input name="collaborateur_rc" value="<?= e((string) $formData['collaborateur_rc']) ?>"></label>
                <label class="field"><span>IF</span><input name="collaborateur_if" value="<?= e((string) $formData['collaborateur_if']) ?>"></label>
                <?php endif; ?>

                <h3 class="section-title">Contact</h3>
                <label class="field"><span>Email professionnel</span><input type="email" name="collaborateur_email" value="<?= e((string) $formData['collaborateur_email']) ?>"></label>
                <?php if ($collabType === 'interne'): ?>
                <label class="field"><span>Email secondaire</span><input type="email" name="email" value="<?= e((string) $formData['email']) ?>"></label>
                <?php endif; ?>
                <?php if ($collabType === 'externe-pm'): ?>
                <label class="field"><span>Telephone fixe</span><input name="collaborateur_tel_fixe" value="<?= e((string) $formData['collaborateur_tel_fixe']) ?>"></label>
                <?php endif; ?>
                <label class="field"><span>Telephone mobile</span><input name="collaborateur_tel_mobile" value="<?= e((string) $formData['collaborateur_tel_mobile']) ?>"></label>
                <?php if ($collabType === 'externe-pm' || $collabType === 'externe-pp'): ?>
                <label class="field full"><span>Adresse</span><textarea name="collaborateur_adresse"><?= e((string) $formData['collaborateur_adresse']) ?></textarea></label>
                <?php endif; ?>

                <h3 class="section-title">Informations</h3>
                <?php if ($collabType === 'interne'): ?>
                <label class="field"><span>Date debut</span><input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>"></label>
                <?php endif; ?>
                <label class="field"><span>Statut</span>
                    <select name="statut">
                        <?php foreach (['actif', 'inactif', 'archive'] as $statut): ?>
                            <option value="<?= e($statut) ?>" <?= (string) $formData['statut'] === $statut ? 'selected' : '' ?>><?= e(ucfirst($statut)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field full"><span>Notes</span><textarea name="notes"><?= e((string) $formData['notes']) ?></textarea></label>
            </div>

            <button type="submit">Enregistrer</button>
        </form>
    </article>

<?php elseif ($editingRecord && $showEdit): ?>
    <article class="card stack">
        <div class="section-header">
            <h2>Modifier un collaborateur</h2>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateur', ['id' => $editingId])) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
            </div>
        </div>

        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
            <input type="hidden" name="collaborateur_type" value="<?= e($collabType) ?>">

            <div class="form-grid">
                <h3 class="section-title">Identite & Role</h3>

                <label class="field">
                    <span>Role / Type</span>
                    <select name="role_id">
                        <option value="">Selectionner...</option>
                        <?php $optRoles = ($collabType === 'interne') ? $rolesInterne : $rolesExterne; ?>
                        <?php foreach ($optRoles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= (string) ($formData['role_id'] ?? '') === (string) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <?php if ($collabType !== 'externe-pm'): ?>
                <label class="field">
                    <span>Nom complet *</span>
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>" placeholder="Nom et prenom">
                </label>
                <label class="field">
                    <span>Fonction</span>
                    <select name="fonction">
                        <option value="">Selectionner...</option>
                        <?php foreach ($fonctions as $f): ?>
                            <option value="<?= e((string) $f['fonction']) ?>" <?= (string) $formData['fonction'] === (string) $f['fonction'] ? 'selected' : '' ?>>
                                <?= e((string) $f['fonction']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>

                <?php if ($collabType === 'externe-pm'): ?>
                <label class="field full">
                    <span>Raison sociale *</span>
                    <input name="den_ste" required value="<?= e((string) $formData['den_ste']) ?>" placeholder="Denomination sociale">
                </label>
                <?php endif; ?>

                <?php if ($collabType === 'interne'): ?>
                <h3 class="section-title">Acces au systeme</h3>

                <label class="field" style="grid-column:1/-1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="can_login" value="1" data-toggle-password <?= (int) ($formData['can_login'] ?? 0) ? 'checked' : '' ?>>
                        <span>Peut se connecter a l application</span>
                    </label>
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Mot de passe <?= $editingRecord ? '(laisser vide pour conserver)' : '' ?></span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="6 caracteres min">
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Confirmer le mot de passe</span>
                    <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Retapez le mot de passe">
                </label>
                <?php endif; ?>

                <?php if ($isCurrentUser): ?>
                    <p class="help-text" style="grid-column:1/-1;color:var(--info);font-size:0.8rem;">
                        <span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;">info</span>
                        Vous modifiez votre propre compte.
                    </p>
                <?php endif; ?>

                <?php if ($collabType === 'externe-pm'): ?>
                <h3 class="section-title">Identifiants legaux</h3>
                <label class="field"><span>Code</span><input name="collaborateur_code" value="<?= e((string) $formData['collaborateur_code']) ?>"></label>
                <label class="field"><span>ICE *</span><input name="collaborateur_ice" value="<?= e((string) $formData['collaborateur_ice']) ?>" placeholder="Identifiant ICE"></label>
                <label class="field"><span>TP</span><input name="collaborateur_tp" value="<?= e((string) $formData['collaborateur_tp']) ?>"></label>
                <label class="field"><span>RC</span><input name="collaborateur_rc" value="<?= e((string) $formData['collaborateur_rc']) ?>"></label>
                <label class="field"><span>IF</span><input name="collaborateur_if" value="<?= e((string) $formData['collaborateur_if']) ?>"></label>
                <?php endif; ?>

                <h3 class="section-title">Contact</h3>
                <label class="field"><span>Email professionnel</span><input type="email" name="collaborateur_email" value="<?= e((string) $formData['collaborateur_email']) ?>"></label>
                <?php if ($collabType === 'interne'): ?>
                <label class="field"><span>Email secondaire</span><input type="email" name="email" value="<?= e((string) $formData['email']) ?>"></label>
                <?php endif; ?>
                <?php if ($collabType === 'externe-pm'): ?>
                <label class="field"><span>Telephone fixe</span><input name="collaborateur_tel_fixe" value="<?= e((string) $formData['collaborateur_tel_fixe']) ?>"></label>
                <?php endif; ?>
                <label class="field"><span>Telephone mobile</span><input name="collaborateur_tel_mobile" value="<?= e((string) $formData['collaborateur_tel_mobile']) ?>"></label>
                <?php if ($collabType === 'externe-pm' || $collabType === 'externe-pp'): ?>
                <label class="field full"><span>Adresse</span><textarea name="collaborateur_adresse"><?= e((string) $formData['collaborateur_adresse']) ?></textarea></label>
                <?php endif; ?>

                <h3 class="section-title">Informations</h3>
                <?php if ($collabType === 'interne'): ?>
                <label class="field"><span>Date debut</span><input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>"></label>
                <?php endif; ?>
                <label class="field"><span>Statut</span>
                    <select name="statut">
                        <?php foreach (['actif', 'inactif', 'archive'] as $statut): ?>
                            <option value="<?= e($statut) ?>" <?= (string) $formData['statut'] === $statut ? 'selected' : '' ?>><?= e(ucfirst($statut)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field full"><span>Notes</span><textarea name="notes"><?= e((string) $formData['notes']) ?></textarea></label>
            </div>

            <button type="submit">Mettre a jour</button>
        </form>
    </article>

<?php elseif ($editingRecord): ?>
    <!-- Fiche collaborateur (lecture seule) -->
    <article class="card stack collab-preview">
        <div class="section-header">
            <h2>
                <?php
                    $typeLabel = ['interne' => 'Interne', 'externe-pm' => 'PM', 'externe-pp' => 'PP'][$formData['collaborateur_type'] ?? 'externe-pp'] ?? '-';
                    $typeClass = ['interne' => 'badge-info', 'externe-pm' => 'badge-secondary', 'externe-pp' => 'badge-warning'][$formData['collaborateur_type'] ?? 'externe-pp'] ?? 'badge';
                    $roleNom = $formData['role_id'] ? ($roles[array_search($formData['role_id'], array_column($roles, 'id'))]['nom'] ?? '-') : '';
                ?>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span><?= e($formData['nom_complet'] ?: 'Collaborateur') ?></span>
                    <span class="badge <?= $typeClass ?>"><?= e($typeLabel) ?></span>
                    <?php if ($roleNom): ?><span class="badge badge-secondary" style="font-weight:400;"><?= e($roleNom) ?></span><?php endif; ?>
                    <?php if ($formData['can_login']): ?><span class="badge badge-success" style="font-weight:400;">Connectable</span><?php endif; ?>
                </div>
            </h2>
        </div>

        <?php $hasIdentite = $formData['den_ste'] || $formData['fonction'] || $formData['nom_complet']; ?>
        <?php $hasLegal = $formData['collaborateur_ice'] || $formData['collaborateur_rc'] || $formData['collaborateur_if'] || $formData['collaborateur_tp']; ?>
        <?php $hasContact = $formData['collaborateur_email'] || $formData['email'] || $formData['collaborateur_tel_mobile'] || $formData['collaborateur_tel_fixe'] || $formData['telephone'] || $formData['collaborateur_adresse']; ?>
        <?php $hasInfo = $formData['statut'] || $formData['date_debut'] || $formData['last_login']; ?>

        <div class="info-grid">
            <?php if ($hasIdentite): ?>
            <h3 class="section-title">Identite</h3>
            <?php if ($formData['den_ste']): ?><div><strong>Raison sociale</strong><span><?= e($formData['den_ste']) ?></span></div><?php endif; ?>
            <?php if ($formData['nom_complet']): ?><div><strong>Nom complet</strong><span><?= e($formData['nom_complet']) ?></span></div><?php endif; ?>
            <?php if ($formData['fonction']): ?><div><strong>Fonction</strong><span><?= e($formData['fonction']) ?></span></div><?php endif; ?>
            <?php endif; ?>

            <?php if ($hasLegal): ?>
            <h3 class="section-title">Identifiants legaux</h3>
            <?php if ($formData['collaborateur_ice']): ?><div><strong>ICE</strong><span><?= e($formData['collaborateur_ice']) ?></span></div><?php endif; ?>
            <?php if ($formData['collaborateur_rc']): ?><div><strong>RC</strong><span><?= e($formData['collaborateur_rc']) ?></span></div><?php endif; ?>
            <?php if ($formData['collaborateur_if']): ?><div><strong>IF</strong><span><?= e($formData['collaborateur_if']) ?></span></div><?php endif; ?>
            <?php if ($formData['collaborateur_tp']): ?><div><strong>TP</strong><span><?= e($formData['collaborateur_tp']) ?></span></div><?php endif; ?>
            <?php endif; ?>

            <?php if ($hasContact): ?>
            <h3 class="section-title">Contact</h3>
            <div><strong>Email</strong><span><?= e($formData['collaborateur_email'] ?: $formData['email'] ?: '-') ?></span></div>
            <div><strong>Telephone</strong><span><?= e($formData['collaborateur_tel_mobile'] ?: $formData['collaborateur_tel_fixe'] ?: $formData['telephone'] ?: '-') ?></span></div>
            <?php if ($formData['collaborateur_adresse']): ?><div class="full"><strong>Adresse</strong><span><?= e($formData['collaborateur_adresse']) ?></span></div><?php endif; ?>
            <?php endif; ?>

            <?php if ($hasInfo): ?>
            <h3 class="section-title">Informations</h3>
            <div><strong>Statut</strong><span><?= e($formData['statut'] ?: '-') ?></span></div>
            <?php if ($formData['date_debut']): ?><div><strong>Date debut</strong><span><?= e(format_date($formData['date_debut'] ?? null)) ?></span></div><?php endif; ?>
            <?php if ($formData['last_login']): ?><div><strong>Derniere connexion</strong><span><?= e(format_date($formData['last_login'])) ?></span></div><?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($formData['notes']): ?>
            <div class="field">
                <span>Notes</span>
                <p style="margin:0;"><?= e($formData['notes']) ?></p>
            </div>
        <?php endif; ?>

        <div class="table-actions" style="justify-content:flex-end;padding-top:12px;border-top:1px solid var(--line);">
            <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            <a class="btn btn-next" href="<?= e(app_url('collaborateur', ['id' => $editingId, 'edit' => 1])) ?>"><span class="material-symbols-outlined">edit</span> Modifier</a>
        </div>
    </article>
<?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.querySelector('[data-toggle-password]');
    if (toggle) {
        toggle.addEventListener('change', function() {
            document.querySelectorAll('.password-field').forEach(function(el) {
                el.style.display = this.checked ? '' : 'none';
            }.bind(this));
        });
    }
});
</script>
