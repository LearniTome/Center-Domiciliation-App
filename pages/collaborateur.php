<?php

declare(strict_types=1);

$editingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingRecord = $editingId > 0 ? fetch_record($pdo ?? null, 'collaborateurs', $editingId) : null;

// Fetch roles from DB
$roles = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, nom, is_internal FROM roles ORDER BY sort_order ASC, nom ASC');
    $roles = $stmt->fetchAll();
}

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();

    $nomComplet = field_value($_POST, 'nom_complet');
    if ($nomComplet === '') {
        set_flash('error', 'Le nom complet est obligatoire.');
        redirect_to('collaborateur', $editingId ? ['id' => $editingId] : []);
    }

    $roleId = int_value($_POST, 'role_id');
    $canLogin = (int) ($_POST['can_login'] ?? 0);
    $password = field_value($_POST, 'password');
    $passwordConfirm = field_value($_POST, 'password_confirm');

    // Password validation
    if ($canLogin && $password !== '') {
        if (strlen($password) < 6) {
            set_flash('error', 'Le mot de passe doit contenir au moins 6 caracteres.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : []);
        }
        if ($password !== $passwordConfirm) {
            set_flash('error', 'Les mots de passe ne correspondent pas.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : []);
        }
    }

    // Duplicate email check
    $checkEmail = field_value($_POST, 'collaborateur_email') ?: field_value($_POST, 'email');
    if ($checkEmail !== '') {
        $dupSql = 'SELECT id FROM collaborateurs WHERE (collaborateur_email = :email OR email = :email2) AND statut != \'archive\'';
        $dupParams = ['email' => $checkEmail, 'email2' => $checkEmail];
        if ($editingId > 0) {
            $dupSql .= ' AND id != :exclude_id';
            $dupParams['exclude_id'] = $editingId;
        }
        $dupSql .= ' LIMIT 1';
        $dupStmt = $pdo->prepare($dupSql);
        $dupStmt->execute($dupParams);
        if ($dupStmt->fetch()) {
            set_flash('error', 'Un collaborateur avec cet email existe deja.');
            redirect_to('collaborateur', $editingId ? ['id' => $editingId] : []);
        }
    }

    $payload = [
        'den_ste' => field_value($_POST, 'den_ste'),
        'nom_complet' => $nomComplet,
        'fonction' => field_value($_POST, 'fonction'),
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
        'role_id' => $roleId,
        'can_login' => $canLogin,
    ];

    try {
        if ($editingId > 0 && $editingRecord) {
            $sql = '
                UPDATE collaborateurs
                SET den_ste = :den_ste,
                    nom_complet = :nom_complet,
                    fonction = :fonction,
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
                    notes = :notes,
                    role_id = :role_id,
                    can_login = :can_login
            ';
            $params = $payload;
            $params['id'] = $editingId;

            // Only update password if provided
            if ($canLogin && $password !== '') {
                $sql .= ', password_hash = :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            set_flash('success', 'Collaborateur mis a jour.');
        } else {
            $sql = '
                INSERT INTO collaborateurs
                    (den_ste, nom_complet, fonction, collaborateur_code,
                     collaborateur_nom, collaborateur_ice, collaborateur_tp, collaborateur_rc, collaborateur_if,
                     collaborateur_tel_fixe, collaborateur_tel_mobile, collaborateur_adresse, collaborateur_email,
                     email, telephone, date_debut, statut, notes, role_id, can_login
            ';

            $insertCols = '
                    :den_ste, :nom_complet, :fonction, :collaborateur_code,
                    :collaborateur_nom, :collaborateur_ice, :collaborateur_tp, :collaborateur_rc, :collaborateur_if,
                    :collaborateur_tel_fixe, :collaborateur_tel_mobile, :collaborateur_adresse, :collaborateur_email,
                    :email, :telephone, :date_debut, :statut, :notes, :role_id, :can_login
            ';

            // Allow password on creation
            $params = $payload;
            if ($canLogin && $password !== '') {
                $sql .= ', password_hash';
                $insertCols .= ', :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= ') VALUES (' . $insertCols . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $newId = (int) $pdo->lastInsertId();
            $who = '';
            $cu = current_user();
            if ($cu) $who = $cu['nom_complet'] ?? '';
            $logStmt = $pdo->prepare('INSERT INTO collaborateur_log (action, collaborateur_nom, collaborateur_email, collaborateur_id, done_by) VALUES (\'ajout\', :nom, :email, :cid, :done_by)');
            $logStmt->execute(['nom' => $nomComplet, 'email' => field_value($_POST, 'collaborateur_email') ?: field_value($_POST, 'email'), 'cid' => $newId, 'done_by' => $who]);
            set_flash('success', 'Collaborateur ajoute.');
        }

        clear_user_cache();
    } catch (PDOException $e) {
        set_flash('error', 'Erreur : ' . $e->getMessage());
    }

    redirect_to('collaborateurs');
}

$formData = $editingRecord ?? [
    'id' => '',
    'den_ste' => '',
    'nom_complet' => '',
    'fonction' => '',
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
    'role_id' => null,
    'can_login' => 0,
    'password_hash' => '',
];

$isCurrentUser = is_logged_in() && $editingId > 0 && (int) ($_SESSION['user_id'] ?? 0) === $editingId;
?>
<section class="grid two">
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2><?= $editingRecord ? 'Modifier un collaborateur' : 'Nouveau collaborateur' ?></h2>
                <p class="help-text">Expert-comptable, comptable, admin, employe, etc.</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>">Retour</a>
            </div>
        </div>

        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">

            <div class="form-grid">
                <h3 class="section-title">Identite & Role</h3>

                <label class="field">
                    <span>Role / Type</span>
                    <select name="role_id">
                        <option value="">Selectionner...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= (string) ($formData['role_id'] ?? '') === (string) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['nom']) ?>
                                <?= ((int) ($r['is_internal'] ?? 0)) ? '(Interne)' : '(Externe)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span>Nom complet *</span>
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>" placeholder="Nom et prenom">
                </label>

                <label class="field">
                    <span>Fonction</span>
                    <input name="fonction" value="<?= e((string) $formData['fonction']) ?>" placeholder="ex: Gerant, Associe">
                </label>

                <label class="field">
                    <span>Cabinet / Societe</span>
                    <input name="den_ste" value="<?= e((string) $formData['den_ste']) ?>" placeholder="Raison sociale du cabinet">
                </label>

                <h3 class="section-title">Acces au systeme</h3>

                <label class="field" style="grid-column: 1 / -1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="can_login" value="1" data-toggle-password <?= (int) ($formData['can_login'] ?? 0) ? 'checked' : '' ?>>
                        <span>Peut se connecter a l application</span>
                    </label>
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Mot de passe <?= $editingRecord ? '(laisser vide pour conserver)' : '' ?></span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Minimum 6 caracteres" <?= $editingRecord ? '' : '' ?>>
                </label>

                <label class="field password-field" <?= (int) ($formData['can_login'] ?? 0) ? '' : 'style="display:none"' ?>>
                    <span>Confirmer le mot de passe</span>
                    <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Retapez le mot de passe">
                </label>

                <?php if ($isCurrentUser): ?>
                    <p class="help-text" style="grid-column:1/-1;color:var(--info);">
                        <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">info</span>
                        Vous modifiez votre propre compte.
                    </p>
                <?php endif; ?>

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
                <div><strong>Role</strong><span><?= e($formData['role_id'] ? ($roles[array_search($formData['role_id'], array_column($roles, 'id'))]['nom'] ?? '-') : '-') ?></span></div>
                <div><strong>Acces app</strong><span><?= (int) ($formData['can_login'] ?? 0) ? 'Oui' : 'Non' ?></span></div>
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
                <div><strong>Date debut</strong><span><?= e(format_date($formData['date_debut'] ?? null)) ?></span></div>
                <?php if ($formData['last_login']): ?>
                <div><strong>Derniere connexion</strong><span><?= e(format_date($formData['last_login'])) ?></span></div>
                <?php endif; ?>
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