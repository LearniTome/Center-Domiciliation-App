<?php

declare(strict_types=1);

$editingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();

    $nom = field_value($_POST, 'nom');
    $description = field_value($_POST, 'description');
    $isInternal = (int) ($_POST['is_internal'] ?? 0);
    $permissions = array_map('intval', (array) ($_POST['permissions'] ?? []));

    if ($nom === '') {
        set_flash('error', 'Le nom du role est obligatoire.');
        redirect_to('role', $editingId ? ['id' => $editingId] : []);
    }

    try {
        if ($editingId > 0) {
            $stmt = $pdo->prepare('SELECT is_system FROM roles WHERE id = :id');
            $stmt->execute(['id' => $editingId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                set_flash('error', 'Role introuvable.');
                redirect_to('roles');
            }

            if ((int) ($existing['is_system'] ?? 0)) {
                set_flash('error', 'Les roles systeme ne peuvent pas etre modifies.');
                redirect_to('roles');
            }

            $stmt = $pdo->prepare('UPDATE roles SET nom = :nom, description = :description, is_internal = :is_internal WHERE id = :id');
            $stmt->execute(['nom' => $nom, 'description' => $description, 'is_internal' => $isInternal, 'id' => $editingId]);

            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $editingId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO roles (nom, description, is_internal, is_system, sort_order) VALUES (:nom, :description, :is_internal, 0, 99)');
            $stmt->execute(['nom' => $nom, 'description' => $description, 'is_internal' => $isInternal]);
            $editingId = (int) $pdo->lastInsertId();
        }

        if (!empty($permissions)) {
            $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)');
            foreach ($permissions as $permId) {
                $stmt->execute(['role_id' => $editingId, 'perm_id' => $permId]);
            }
        }

        clear_user_cache();
        set_flash('success', 'Role ' . ($editingId > 0 ? 'mis a jour.' : 'cree.'));
        redirect_to('roles');
    } catch (PDOException $e) {
        set_flash('error', 'Erreur : ' . $e->getMessage());
        redirect_to('role', $editingId ? ['id' => $editingId] : []);
    }
}

$role = null;
if ($editingId > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('SELECT * FROM roles WHERE id = :id');
    $stmt->execute(['id' => $editingId]);
    $role = $stmt->fetch();
    if (!$role) {
        set_flash('error', 'Role introuvable.');
        redirect_to('roles');
    }
}

$isSystem = $role && (int) ($role['is_system'] ?? 0);

$permissionsByCategory = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT * FROM permissions ORDER BY category, id');
    while ($row = $stmt->fetch()) {
        $cat = $row['category'] ?? 'autres';
        if (!isset($permissionsByCategory[$cat])) {
            $permissionsByCategory[$cat] = [];
        }
        $permissionsByCategory[$cat][] = $row;
    }
}

$rolePermIds = [];
if ($editingId > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :role_id');
    $stmt->execute(['role_id' => $editingId]);
    $rolePermIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
}

$categoryLabels = [
    'dashboard' => 'Tableau de bord',
    'societes' => 'Societes',
    'associes' => 'Associes',
    'contrats' => 'Contrats',
    'collaborateurs' => 'Collaborateurs',
    'wizard' => 'Assistant de creation',
    'templates' => 'Templates',
    'generation' => 'Generation',
    'documents' => 'Documents',
    'configuration' => 'Configuration',
    'analyse' => 'Analyse de couverture',
    'variables' => 'Variables',
    'defaults' => 'Valeurs par defaut',
    'convert' => 'Conversion Word→PDF',
    'ai' => 'Assistant IA',
    'roles' => 'Gestion des roles',
];

$categoryIcons = [
    'dashboard' => 'dashboard',
    'societes' => 'business',
    'associes' => 'group',
    'contrats' => 'description',
    'collaborateurs' => 'work',
    'wizard' => 'note_add',
    'templates' => 'edit_note',
    'generation' => 'sync',
    'documents' => 'article',
    'configuration' => 'settings',
    'analyse' => 'bar_chart',
    'variables' => 'code',
    'defaults' => 'tune',
    'convert' => 'picture_as_pdf',
    'ai' => 'smart_toy',
    'roles' => 'admin_panel_settings',
];

$totalPerms = array_sum(array_map('count', $permissionsByCategory));
$selectedCount = count($rolePermIds);

$roleUsers = [];
if ($editingId > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('
        SELECT c.id, c.nom_complet, c.email, c.statut, c.can_login, c.last_login, c.den_ste, c.fonction
        FROM collaborateurs c
        WHERE c.role_id = :role_id
        ORDER BY c.nom_complet ASC
    ');
    $stmt->execute(['role_id' => $editingId]);
    $roleUsers = $stmt->fetchAll();
}
?>
<section>
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2><?= $role ? 'Modifier le role' : 'Nouveau role' ?></h2>
                <p class="help-text">
                    <?php if ($role): ?>
                        <?= e($role['nom']) ?>
                        <?= (int) ($role['is_internal'] ?? 0) ? '<span class="badge badge-info">Interne</span>' : '<span class="badge badge-secondary">Externe</span>' ?>
                    <?php else: ?>
                        Creer un nouveau role avec ses permissions
                    <?php endif; ?>
                </p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('roles')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            </div>
        </div>

        <?php if ($isSystem): ?>
            <div class="alert" style="background:rgba(255,107,53,0.1);border:1px solid rgba(255,107,53,0.3);border-radius:var(--radius-sm);padding:12px;color:var(--warning);">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">warning</span>
                Les roles systeme sont proteges et ne peuvent pas etre modifies. Creez un nouveau role si vous avez besoin d un role personnalise.
            </div>
        <?php endif; ?>

        <form method="post" class="stack">
            <?= csrf_input() ?>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr auto;">
                <label class="field">
                    <span>Nom du role *</span>
                    <input name="nom" required value="<?= e($role['nom'] ?? field_value($_POST, 'nom')) ?>" placeholder="ex: Chef d equipe" <?= $isSystem ? 'disabled' : '' ?>>
                </label>

                <label class="field">
                    <span>Description</span>
                    <input name="description" value="<?= e($role['description'] ?? field_value($_POST, 'description')) ?>" placeholder="Courte description du role" <?= $isSystem ? 'disabled' : '' ?>>
                </label>

                <label class="field" style="display:flex;align-items:flex-end;padding-bottom:6px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_internal" value="1"
                            <?= ($role && (int) ($role['is_internal'] ?? 0)) ? 'checked' : '' ?>
                            <?= $isSystem ? 'disabled' : '' ?>>
                        <span style="font-size:0.85rem;">Role interne</span>
                    </label>
                </label>
            </div>

            <div class="section-title-row" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <h3 style="margin:0;">Permissions</h3>
                    <span class="badge badge-info"><?= $selectedCount ?> / <?= $totalPerms ?> selectionnee(s)</span>
                </div>
                <?php if (!$isSystem): ?>
                    <div class="perm-summary" id="permSummary">
                        <span class="help-text">Selectionnez les permissions de ce role :</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="perms-grid">
                <?php foreach ($permissionsByCategory as $cat => $perms): ?>
                    <?php
                        $catChecked = 0;
                        foreach ($perms as $p) {
                            if (in_array((int) $p['id'], $rolePermIds, true)) $catChecked++;
                        }
                        $catTotal = count($perms);
                    ?>
                    <div class="perms-card" data-category="<?= e($cat) ?>">
                        <h4>
                            <span>
                                <?php if (isset($categoryIcons[$cat])): ?>
                                    <span class="material-symbols-outlined" style="font-size:0.8rem;vertical-align:middle;"><?= e($categoryIcons[$cat]) ?></span>
                                <?php endif; ?>
                                <?= e($categoryLabels[$cat] ?? $cat) ?>
                            </span>
                            <?php if (!$isSystem): ?>
                                <button type="button" class="select-all-toggle" data-toggle-cat="<?= e($cat) ?>"
                                    data-state="<?= $catChecked === $catTotal ? 'checked' : 'unchecked' ?>">
                                    <?= $catChecked === $catTotal ? 'Tout deselect' : 'Tout select' ?>
                                </button>
                            <?php endif; ?>
                        </h4>
                        <?php foreach ($perms as $p): ?>
                            <?php $checked = in_array((int) $p['id'], $rolePermIds, true); ?>
                            <label>
                                <input type="checkbox" name="permissions[]" value="<?= (int) $p['id'] ?>"
                                    data-cat="<?= e($cat) ?>"
                                    <?= $checked ? 'checked' : '' ?>
                                    <?= $isSystem ? 'disabled' : '' ?>>
                                <span class="perm-label">
                                    <?= e($p['nom']) ?>
                                    <span class="perm-key"><?= e($p['permission_key']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$isSystem): ?>
                <div style="display:flex;gap:10px;align-items:center;padding-top:8px;">
                    <button type="submit"><span class="material-symbols-outlined">save</span> <?= $role ? 'Enregistrer les modifications' : 'Creer le role' ?></button>
                    <a class="btn btn-cancel" href="<?= e(app_url('roles')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            <?php endif; ?>
        </form>
    </article>

    <?php if ($role): ?>
    <article class="card">
        <div class="section-header">
            <span class="material-symbols-outlined" style="color:var(--text-secondary)">work</span>
            <span>Collaborateurs avec le role &quot;<?= e($role['nom']) ?>&quot;</span>
            <span class="badge badge-info"><?= count($roleUsers) ?></span>
        </div>

        <?php if (!$roleUsers): ?>
            <p class="table-empty">Aucun collaborateur n a ce role pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-sortable>
                <thead>
                <tr>
                    <th data-col="nom">Nom complet</th>
                    <th data-col="cabinet">Cabinet</th>
                    <th data-col="fonction">Fonction</th>
                    <th data-col="email">Email</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="acces">Acces app</th>
                    <th data-col="connexion">Derniere connexion</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($roleUsers as $u): ?>
                    <tr>
                        <td><strong><?= e($u['nom_complet']) ?></strong></td>
                        <td><?= e($u['den_ste'] ?? '-') ?></td>
                        <td><?= e($u['fonction'] ?? '-') ?></td>
                        <td><?= e($u['email'] ?? '-') ?></td>
                        <td><?= e($u['statut'] ?? '-') ?></td>
                        <td>
                            <?php if ((int) ($u['can_login'] ?? 0)): ?>
                                <span class="badge badge-success">Connectable</span>
                            <?php else: ?>
                                <span class="badge">Aucun acces</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais') ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('collaborateur', ['id' => (int) $u['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>
</section>

<?php if (!$isSystem): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all / deselect all per category
    document.querySelectorAll('[data-toggle-cat]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var cat = this.getAttribute('data-toggle-cat');
            var checkboxes = document.querySelectorAll('input[data-cat="' + cat + '"]');
            var allChecked = true;
            checkboxes.forEach(function(cb) { if (!cb.checked) allChecked = false; });

            checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
            this.setAttribute('data-state', allChecked ? 'unchecked' : 'checked');
            this.textContent = allChecked ? 'Tout select' : 'Tout deselect';
            updateSummary();
        });
    });

    // Update count on checkbox change
    document.querySelectorAll('input[name="permissions[]"]').forEach(function(cb) {
        cb.addEventListener('change', updateSummary);
    });

    function updateSummary() {
        var total = document.querySelectorAll('input[name="permissions[]"]').length;
        var checked = document.querySelectorAll('input[name="permissions[]"]:checked').length;
        var badge = document.querySelector('.badge.badge-info');
        if (badge) badge.textContent = checked + ' / ' + total + ' selectionnee(s)';

        // Update per-category toggle buttons
        document.querySelectorAll('[data-toggle-cat]').forEach(function(btn) {
            var cat = btn.getAttribute('data-toggle-cat');
            var catCbs = document.querySelectorAll('input[data-cat="' + cat + '"]');
            var allCatChecked = true;
            catCbs.forEach(function(cb) { if (!cb.checked) allCatChecked = false; });
            btn.setAttribute('data-state', allCatChecked ? 'checked' : 'unchecked');
            btn.textContent = allCatChecked ? 'Tout deselect' : 'Tout select';
        });
    }

    updateSummary();
});
</script>
<?php endif; ?>