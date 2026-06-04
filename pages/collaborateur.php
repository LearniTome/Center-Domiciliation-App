<?php

declare(strict_types=1);

$editingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingRecord = $editingId > 0 ? fetch_record($pdo ?? null, 'collaborateurs', $editingId) : null;
$showEdit = $editingRecord && isset($_GET['edit']);

// Determine type
$collabType = '';
$overrideType = field_value($_GET, 'type', '');
if ($editingRecord) {
    $collabType = $overrideType ?: ($editingRecord['collaborateur_type'] ?? '');
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

    $password = field_value($_POST, 'password');
    $passwordConfirm = field_value($_POST, 'password_confirm');

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
            'can_login' => (int) ($_POST['can_login'] ?? 0),
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
            'can_login' => (int) ($_POST['can_login'] ?? 0),
            'collaborateur_type' => $savedType,
        ];
    }

    // When can_login is enabled, validate email + password + role
    if ($payload['can_login']) {
        $loginEmail = $payload['collaborateur_email'] ?: $payload['email'];
        if ($loginEmail === '') {
            set_flash('error', 'Un email est requis pour l\'acces a l\'application.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => $savedType]);
        }
        if ($password !== '') {
            if (strlen($password) < 6) { set_flash('error', 'Le mot de passe doit contenir au moins 6 caracteres.'); redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => $savedType]); }
            if ($password !== $passwordConfirm) { set_flash('error', 'Les mots de passe ne correspondent pas.'); redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => $savedType]); }
        } elseif (!$editingId) {
            set_flash('error', 'Un mot de passe est requis pour l\'acces a l\'application.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => $savedType]);
        }
        if (empty($roleId)) {
            set_flash('error', 'Un role est requis pour l\'acces a l\'application.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : ['type' => $savedType]);
        }
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

// Fetch permissions data (needed for both edit form and detail view)
$rolePermKeys = [];
$allPermsByCat = [];
if (($pdo ?? null) instanceof PDO && ($formData['role_id'] ?? 0)) {
    $stmt = $pdo->query('SELECT * FROM permissions ORDER BY category, id');
    $allPerms = $stmt->fetchAll();
    foreach ($allPerms as $p) {
        $allPermsByCat[$p['category']][] = $p;
    }
    $stmt = $pdo->prepare('SELECT p.permission_key FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = :rid');
    $stmt->execute(['rid' => (int) $formData['role_id']]);
    $rolePermKeys = $stmt->fetchAll(\PDO::FETCH_COLUMN);
}
$catIcons = ['dashboard'=>'dashboard','societes'=>'business','associes'=>'group','contrats'=>'description','collaborateurs'=>'work','wizard'=>'note_add','templates'=>'edit_note','generation'=>'sync','documents'=>'article','configuration'=>'settings','analyse'=>'bar_chart','variables'=>'code','defaults'=>'tune','convert'=>'picture_as_pdf','ai'=>'smart_toy','roles'=>'admin_panel_settings'];
$catLabels = ['dashboard'=>'Tableau de bord','societes'=>'Societes','associes'=>'Associes','contrats'=>'Contrats','collaborateurs'=>'Collaborateurs','wizard'=>'Assistant de creation','templates'=>'Templates','generation'=>'Generation','documents'=>'Documents','configuration'=>'Configuration','analyse'=>'Analyse de couverture','variables'=>'Variables','defaults'=>'Valeurs par defaut','convert'=>'Conversion Word→PDF','ai'=>'Assistant IA','roles'=>'Gestion des roles'];
$actionLabels = ['view'=>'Voir','create'=>'Creer','edit'=>'Modifier','delete'=>'Supprimer','export'=>'Exporter','use'=>'Utiliser','download'=>'Telecharger','manage'=>'Gerer'];
$actionOrder = ['view','create','edit','delete','export','use','download','manage'];
$permMatrix = [];
$activeActions = [];
foreach ($allPermsByCat as $cat => $perms) {
    foreach ($perms as $p) {
        $parts = explode('.', $p['permission_key']);
        $actionKey = end($parts);
        $permMatrix[$actionKey][$cat] = $p;
        $activeActions[$actionKey] = true;
    }
}
$orderedActions = array_values(array_intersect($actionOrder, array_keys($activeActions)));
foreach (array_keys($activeActions) as $k) {
    if (!in_array($k, $actionOrder)) $orderedActions[] = $k;
}

$isCurrentUser = is_logged_in() && $editingId > 0 && (int) ($_SESSION['user_id'] ?? 0) === $editingId;
$isNew = !$editingRecord;
?>

<style>
form.stack > article.card + article.card { margin-top: 0; }
.collab-preview .section-header { margin-bottom: 0; padding-bottom: 16px; border-bottom: 1px solid var(--line); }
.collab-preview .section-title { padding: 16px 0 6px; margin:0; }
.collab-preview .info-grid { gap: 12px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
.collab-preview .info-grid > div {
    display: flex; flex-direction: column; gap: 4px;
    padding: 8px 0; border: none; background: none;
}
.collab-preview .info-grid span {
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em;
    color: var(--text-secondary); white-space: nowrap;
}
.collab-preview .info-grid strong {
    font-size: 0.85rem; color: var(--text); font-weight: 500;
    background: var(--panel); border: 1px solid var(--line);
    border-radius: var(--radius-sm); padding: 4px 10px;
}
.collab-preview .info-grid .full { grid-column: 1 / -1; }
.collab-preview .info-grid .notes-item { display: grid; gap: 2px; }
.collab-preview .info-grid .notes-item strong { font-weight: 400; line-height: 1.4; border: none; background: none; padding: 0; }
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
    <div style="grid-column:1/-1;">
        <div class="section-header" style="margin-bottom:4px;">
            <h2>Nouveau collaborateur</h2>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            </div>
        </div>

        <form method="post" class="stack" style="gap:4px;">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
        <input type="hidden" name="collaborateur_type" value="<?= e($collabType) ?>">

        <article class="card">
            <div class="section-header">
                <span class="material-symbols-outlined">badge</span>
                <h2>Identit&eacute; &amp; R&ocirc;le</h2>
            </div>
            <div class="form-grid">
                <label class="field">
                    <span>Type collaborateur</span>
                    <select onchange="location.href='?page=collaborateur<?= $editingId ? '&id=' . $editingId . '&edit' : '' ?>&type='+this.value">
                        <?php foreach (['interne', 'externe-pm', 'externe-pp'] as $t): ?>
                        <option value="<?= e($t) ?>" <?= $collabType === $t ? 'selected' : '' ?>><?= e(['interne' => 'Interne', 'externe-pm' => 'Personne Morale', 'externe-pp' => 'Personne Physique'][$t]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>R&ocirc;le / Type</span>
                    <select name="role_id">
                        <option value="">S&eacute;lectionner...</option>
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
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>" placeholder="Nom et pr&eacute;nom">
                </label>
                <label class="field">
                    <span>Fonction</span>
                    <select name="fonction">
                        <option value="">S&eacute;lectionner...</option>
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
                    <input name="den_ste" required value="<?= e((string) $formData['den_ste']) ?>" placeholder="D&eacute;nomination sociale">
                </label>
                <?php endif; ?>
            </div>
        </article>

        <article class="card">
            <div class="section-header">
                <span class="material-symbols-outlined">lock</span>
                <h2>Acc&egrave;s au syst&egrave;me</h2>
            </div>
            <div class="form-grid">
                <div style="grid-column:1/-1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;min-height:36px;">
                        <input type="checkbox" name="can_login" value="1" data-toggle-password style="width:auto;flex-shrink:0;" <?= (int) ($formData['can_login'] ?? 0) ? 'checked' : '' ?>>
                        <span style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-secondary);">Peut se connecter &agrave; l'application</span>
                    </label>
                </div>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Mot de passe</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="6 caract&egrave;res min">
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Confirmer le mot de passe</span>
                    <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Retapez le mot de passe">
                </label>
            </div>
        </article>

        <?php if ($collabType === 'externe-pm'): ?>
        <article class="card">
            <div class="section-header">
                <span class="material-symbols-outlined">assignment</span>
                <h2>Identifiants l&eacute;gaux</h2>
            </div>
            <div class="form-grid">
                <label class="field"><span>Code</span><input name="collaborateur_code" value="<?= e((string) $formData['collaborateur_code']) ?>"></label>
                <label class="field"><span>ICE *</span><input name="collaborateur_ice" value="<?= e((string) $formData['collaborateur_ice']) ?>" placeholder="Identifiant ICE"></label>
                <label class="field"><span>TP</span><input name="collaborateur_tp" value="<?= e((string) $formData['collaborateur_tp']) ?>"></label>
                <label class="field"><span>RC</span><input name="collaborateur_rc" value="<?= e((string) $formData['collaborateur_rc']) ?>"></label>
                <label class="field"><span>IF</span><input name="collaborateur_if" value="<?= e((string) $formData['collaborateur_if']) ?>"></label>
            </div>
        </article>
        <?php endif; ?>

        <article class="card">
            <div class="section-header">
                <span class="material-symbols-outlined">contact_mail</span>
                <h2>Contact</h2>
            </div>
            <div class="form-grid">
                <label class="field"><span>Email professionnel</span><input type="email" name="collaborateur_email" value="<?= e((string) $formData['collaborateur_email']) ?>"></label>
                <?php if ($collabType === 'interne'): ?>
                <label class="field"><span>Email secondaire</span><input type="email" name="email" value="<?= e((string) $formData['email']) ?>"></label>
                <?php endif; ?>
                <?php if ($collabType === 'externe-pm'): ?>
                <label class="field"><span>T&eacute;l&eacute;phone fixe</span><input name="collaborateur_tel_fixe" value="<?= e((string) $formData['collaborateur_tel_fixe']) ?>"></label>
                <?php endif; ?>
                <label class="field"><span>T&eacute;l&eacute;phone mobile</span><input name="collaborateur_tel_mobile" value="<?= e((string) $formData['collaborateur_tel_mobile']) ?>"></label>
                <?php if ($collabType === 'externe-pm' || $collabType === 'externe-pp'): ?>
                <label class="field full"><span>Adresse</span><textarea name="collaborateur_adresse"><?= e((string) $formData['collaborateur_adresse']) ?></textarea></label>
                <?php endif; ?>
            </div>
        </article>

        <article class="card">
            <div class="section-header">
                <span class="material-symbols-outlined">info</span>
                <h2>Informations</h2>
            </div>
            <div class="form-grid">
                <?php if ($collabType === 'interne'): ?>
                <label class="field"><span>Date d&eacute;but</span><input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>"></label>
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
        </article>

        <div class="table-actions" style="justify-content:flex-end;">
            <a class="btn btn-cancel" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
            <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">save</span> Enregistrer</button>
        </div>
    </form>
</div>
<?php elseif ($editingRecord && $showEdit): ?>
    <article class="card stack">
        <div class="section-header">
            <h2>Modifier un collaborateur</h2>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateur', ['id' => $editingId])) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
            </div>
        </div>

        <form method="post" class="stack" style="gap:4px;">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
            <input type="hidden" name="collaborateur_type" value="<?= e($collabType) ?>">

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">badge</span>
                    <h2>Identit&eacute; &amp; R&ocirc;le</h2>
                </div>
                <div class="form-grid">
                    <label class="field">
                        <span>Type collaborateur</span>
                        <select onchange="location.href='?page=collaborateur&id=<?= $editingId ?>&edit&type='+this.value">
                            <?php foreach (['interne', 'externe-pm', 'externe-pp'] as $t): ?>
                            <option value="<?= e($t) ?>" <?= $collabType === $t ? 'selected' : '' ?>><?= e(['interne' => 'Interne', 'externe-pm' => 'Personne Morale', 'externe-pp' => 'Personne Physique'][$t]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>R&ocirc;le / Type</span>
                        <select name="role_id">
                            <option value="">S&eacute;lectionner...</option>
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
                        <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>" placeholder="Nom et pr&eacute;nom">
                    </label>
                    <label class="field">
                        <span>Fonction</span>
                        <select name="fonction">
                            <option value="">S&eacute;lectionner...</option>
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
                        <input name="den_ste" required value="<?= e((string) $formData['den_ste']) ?>" placeholder="D&eacute;nomination sociale">
                    </label>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">lock</span>
                    <h2>Acc&egrave;s au syst&egrave;me</h2>
                </div>
                <div class="form-grid">
                    <div style="grid-column:1/-1;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;min-height:36px;">
                            <input type="checkbox" name="can_login" value="1" data-toggle-password style="width:auto;flex-shrink:0;" <?= (int) ($formData['can_login'] ?? 0) ? 'checked' : '' ?>>
                            <span style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-secondary);">Peut se connecter &agrave; l'application</span>
                        </label>
                    </div>

                    <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                        <span>Mot de passe <?= $editingRecord ? '(laisser vide pour conserver)' : '' ?></span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="6 caract&egrave;res min">
                    </label>

                    <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                        <span>Confirmer le mot de passe</span>
                        <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Retapez le mot de passe">
                    </label>

                    <?php if ($isCurrentUser): ?>
                        <p class="help-text" style="grid-column:1/-1;color:var(--info);font-size:0.8rem;margin:0;">
                            <span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;">info</span>
                            Vous modifiez votre propre compte.
                        </p>
                    <?php endif; ?>
                </div>
            </article>

            <?php if ($collabType === 'externe-pm'): ?>
            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">assignment</span>
                    <h2>Identifiants l&eacute;gaux</h2>
                </div>
                <div class="form-grid">
                    <label class="field"><span>Code</span><input name="collaborateur_code" value="<?= e((string) $formData['collaborateur_code']) ?>"></label>
                    <label class="field"><span>ICE *</span><input name="collaborateur_ice" value="<?= e((string) $formData['collaborateur_ice']) ?>" placeholder="Identifiant ICE"></label>
                    <label class="field"><span>TP</span><input name="collaborateur_tp" value="<?= e((string) $formData['collaborateur_tp']) ?>"></label>
                    <label class="field"><span>RC</span><input name="collaborateur_rc" value="<?= e((string) $formData['collaborateur_rc']) ?>"></label>
                    <label class="field"><span>IF</span><input name="collaborateur_if" value="<?= e((string) $formData['collaborateur_if']) ?>"></label>
                </div>
            </article>
            <?php endif; ?>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">contact_mail</span>
                    <h2>Contact</h2>
                </div>
                <div class="form-grid">
                    <label class="field"><span>Email professionnel</span><input type="email" name="collaborateur_email" value="<?= e((string) $formData['collaborateur_email']) ?>"></label>
                    <?php if ($collabType === 'interne'): ?>
                    <label class="field"><span>Email secondaire</span><input type="email" name="email" value="<?= e((string) $formData['email']) ?>"></label>
                    <?php endif; ?>
                    <?php if ($collabType === 'externe-pm'): ?>
                    <label class="field"><span>T&eacute;l&eacute;phone fixe</span><input name="collaborateur_tel_fixe" value="<?= e((string) $formData['collaborateur_tel_fixe']) ?>"></label>
                    <?php endif; ?>
                    <label class="field"><span>T&eacute;l&eacute;phone mobile</span><input name="collaborateur_tel_mobile" value="<?= e((string) $formData['collaborateur_tel_mobile']) ?>"></label>
                    <?php if ($collabType === 'externe-pm' || $collabType === 'externe-pp'): ?>
                    <label class="field full"><span>Adresse</span><textarea name="collaborateur_adresse"><?= e((string) $formData['collaborateur_adresse']) ?></textarea></label>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">info</span>
                    <h2>Informations</h2>
                </div>
                <div class="form-grid">
                    <?php if ($collabType === 'interne'): ?>
                    <label class="field"><span>Date d&eacute;but</span><input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>"></label>
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
            </article>

            <div class="table-actions" style="justify-content:flex-end;">
                <a class="btn btn-cancel" href="<?= e(app_url('collaborateur', ['id' => $editingId])) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">save</span> Mettre &agrave; jour</button>
            </div>
        </form>
    </article>

<?php elseif ($editingRecord): ?>
    <article class="card stack collab-preview" style="gap:0;">
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

        <div class="info-grid">
            <h3 class="section-title">Identit&eacute;</h3>
            <?php if ($formData['den_ste']): ?><div><span>Raison sociale</span><strong><?= e($formData['den_ste']) ?></strong></div><?php endif; ?>
            <?php if ($formData['nom_complet']): ?><div><span>Nom complet</span><strong><?= e($formData['nom_complet']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_nom']): ?><div><span>Nom</span><strong><?= e($formData['collaborateur_nom']) ?></strong></div><?php endif; ?>
            <?php if ($formData['fonction']): ?><div><span>Fonction</span><strong><?= e($formData['fonction']) ?></strong></div><?php endif; ?>

            <?php if ($formData['collaborateur_ice'] || $formData['collaborateur_rc'] || $formData['collaborateur_if'] || $formData['collaborateur_tp'] || $formData['collaborateur_code']): ?>
            <h3 class="section-title">Identifiants l&eacute;gaux</h3>
            <?php if ($formData['collaborateur_code']): ?><div><span>Code</span><strong><?= e($formData['collaborateur_code']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_ice']): ?><div><span>ICE</span><strong><?= e($formData['collaborateur_ice']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_tp']): ?><div><span>TP</span><strong><?= e($formData['collaborateur_tp']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_rc']): ?><div><span>RC</span><strong><?= e($formData['collaborateur_rc']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_if']): ?><div><span>IF</span><strong><?= e($formData['collaborateur_if']) ?></strong></div><?php endif; ?>
            <?php endif; ?>

            <?php if ($formData['collaborateur_email'] || $formData['email'] || $formData['collaborateur_tel_mobile'] || $formData['collaborateur_tel_fixe'] || $formData['telephone'] || $formData['collaborateur_adresse']): ?>
            <h3 class="section-title">Contact</h3>
            <?php if ($formData['collaborateur_email']): ?><div><span>Email professionnel</span><strong><?= e($formData['collaborateur_email']) ?></strong></div><?php endif; ?>
            <?php if ($formData['email']): ?><div><span>Email secondaire</span><strong><?= e($formData['email']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_tel_fixe']): ?><div><span>T&eacute;l&eacute;phone fixe</span><strong><?= e($formData['collaborateur_tel_fixe']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_tel_mobile']): ?><div><span>T&eacute;l&eacute;phone mobile</span><strong><?= e($formData['collaborateur_tel_mobile']) ?></strong></div><?php endif; ?>
            <?php if (!$formData['collaborateur_tel_fixe'] && !$formData['collaborateur_tel_mobile'] && $formData['telephone']): ?><div><span>T&eacute;l&eacute;phone</span><strong><?= e($formData['telephone']) ?></strong></div><?php endif; ?>
            <?php if ($formData['collaborateur_adresse']): ?><div class="full"><span>Adresse</span><strong><?= e($formData['collaborateur_adresse']) ?></strong></div><?php endif; ?>
            <?php endif; ?>

            <h3 class="section-title">Informations</h3>
            <div><span>Statut</span><strong><?= e($formData['statut'] ?: '-') ?></strong></div>
            <?php if ($formData['date_debut']): ?><div><span>Date d&eacute;but</span><strong><?= e(format_date($formData['date_debut'] ?? null)) ?></strong></div><?php endif; ?>
            <?php if ($formData['notes']): ?><div class="full notes-item"><span>Notes</span><strong><?= e($formData['notes']) ?></strong></div><?php endif; ?>
            <?php if ($formData['last_login']): ?><div><span>Derni&egrave;re connexion</span><strong><?= e(format_date($formData['last_login'])) ?></strong></div><?php endif; ?>

            <?php if ($formData['can_login']): ?>
            <h3 class="section-title">Acc&egrave;s au syst&egrave;me</h3>
            <div><span>Connexion</span><strong>Activ&eacute;e</strong></div>
            <?php if ($formData['email']): ?><div><span>Email de connexion</span><strong><?= e($formData['email']) ?></strong></div><?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($allPermsByCat) && ($formData['role_id'] ?? 0)): ?>
            <h3 class="section-title">Permissions</h3>
            <div style="grid-column:1/-1;">
            <table class="perms-table">
                <thead>
                    <tr class="cat-row">
                        <th></th>
                        <?php foreach ($orderedActions as $actionKey): ?>
                        <th data-col="<?= e($actionKey) ?>"><?= e($actionLabels[$actionKey]) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allPermsByCat as $cat => $perms):
                        $catGranted = 0;
                        foreach ($perms as $p) { if (in_array($p['permission_key'], $rolePermKeys, true)) $catGranted++; }
                        $catTotal = count($perms);
                    ?>
                    <tr data-cat="<?= e($cat) ?>">
                        <td class="cat-label-cell">
                            <div class="cat-label-cell-inner">
                            <span class="cat-label-inner">
                                <?php if (isset($catIcons[$cat])): ?>
                                    <span class="material-symbols-outlined"><?= e($catIcons[$cat]) ?></span>
                                <?php endif; ?>
                                <?= e($catLabels[$cat] ?? $cat) ?>
                            </span>
                            <span class="badge badge-info cat-badge"><?= $catGranted ?>/<?= $catTotal ?></span>
                            </div>
                        </td>
                        <?php foreach ($orderedActions as $actionKey): ?>
                            <?php if (isset($permMatrix[$actionKey][$cat])): ?>
                                <?php $p = $permMatrix[$actionKey][$cat]; ?>
                                <?php $checked = in_array($p['permission_key'], $rolePermKeys, true); ?>
                                <td class="perm-cell">
                                    <?php if ($checked): ?>
                                    <span class="material-symbols-outlined" style="font-size:1.1rem;color:var(--success);">check_circle</span>
                                    <?php else: ?>
                                    <span class="material-symbols-outlined" style="font-size:1.1rem;color:var(--text-muted);opacity:0.35;">cancel</span>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td class="perm-cell empty"></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="table-actions" style="justify-content:flex-end;padding-top:16px;margin-top:8px;border-top:1px solid var(--line);">
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
