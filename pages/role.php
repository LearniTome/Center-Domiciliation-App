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

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) {
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}

$avatarColors = ['avatar-info', 'avatar-success', 'avatar-warning', 'avatar-danger', 'avatar-secondary'];
function avatar_color(string $name, array $colors): string
{
    $idx = abs(crc32($name)) % count($colors);
    return $colors[$idx];
}

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
<section style="max-width:1200px">

    <?php if ($role): ?>
    <div class="role-header">
        <div class="role-header-icon">
            <span class="material-symbols-outlined">admin_panel_settings</span>
        </div>
        <div class="role-header-info">
            <h2><?= e($role['nom']) ?></h2>
            <div class="role-meta">
                <span class="badge <?= (int) ($role['is_internal'] ?? 0) ? 'badge-info' : 'badge-secondary' ?>">
                    <?= (int) ($role['is_internal'] ?? 0) ? 'Interne' : 'Externe' ?>
                </span>
                <?php if ($isSystem): ?>
                    <span class="badge badge-warning">Protege</span>
                <?php endif; ?>
                <span class="stat-pill">
                    <span class="material-symbols-outlined">work</span>
                    <?= count($roleUsers) ?> collaborateur(s)
                </span>
                <span class="stat-pill">
                    <span class="material-symbols-outlined">checklist</span>
                    <?= $selectedCount ?>/<?= $totalPerms ?> permissions
                </span>
                <?php if ($role['description']): ?>
                    <span style="color:var(--text-muted);font-size:0.75rem">— <?= e($role['description']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="role-header-actions">
            <a class="btn btn-secondary" href="<?= e(app_url('roles')) ?>"><span class="material-symbols-outlined">arrow_back</span> Roles</a>
        </div>
    </div>
    <?php else: ?>
    <div class="role-header">
        <div class="role-header-icon">
            <span class="material-symbols-outlined">add</span>
        </div>
        <div class="role-header-info">
            <h2>Nouveau role</h2>
            <div class="role-meta">
                <span style="color:var(--text-muted);font-size:0.75rem">Creer un nouveau role avec ses permissions</span>
            </div>
        </div>
        <div class="role-header-actions">
            <a class="btn btn-secondary" href="<?= e(app_url('roles')) ?>"><span class="material-symbols-outlined">arrow_back</span> Roles</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isSystem): ?>
    <div style="background:rgba(255,107,53,0.08);border:1px solid rgba(255,107,53,0.2);border-radius:8px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:var(--warning);font-size:0.85rem;">
        <span class="material-symbols-outlined" style="font-size:1.2rem">warning</span>
        <span>Les roles systeme sont proteges et ne peuvent pas etre modifies. Creez un nouveau role si vous avez besoin d un role personnalise.</span>
    </div>
    <?php endif; ?>

    <div class="tabs">
        <button type="button" class="tab active" data-tab="permissions">
            <span class="material-symbols-outlined" style="font-size:1rem">checklist</span>
            Permissions
            <span class="badge badge-info" id="total-perm-badge"><?= $selectedCount ?>/<?= $totalPerms ?></span>
        </button>
        <?php if ($role): ?>
        <button type="button" class="tab" data-tab="users">
            <span class="material-symbols-outlined" style="font-size:1rem">work</span>
            Collaborateurs
            <span class="badge badge-info"><?= count($roleUsers) ?></span>
        </button>
        <?php endif; ?>
        <?php if ($role && !$isSystem): ?>
        <button type="button" class="tab" data-tab="settings" style="opacity:0.5;pointer-events:none;">
            <span class="material-symbols-outlined" style="font-size:1rem">settings</span>
            Parametres
        </button>
        <?php endif; ?>
    </div>

    <div id="tab-permissions">
        <form method="post">
            <?= csrf_input() ?>

            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;background:var(--panel);border:1px solid var(--line);border-radius:6px;padding:12px 14px;margin-bottom:16px;">
                <label class="field" style="margin:0">
                    <span>Nom du role</span>
                    <input name="nom" required value="<?= e($role['nom'] ?? field_value($_POST, 'nom')) ?>" placeholder="ex: Chef d equipe" <?= $isSystem ? 'disabled' : '' ?>>
                </label>
                <label class="field" style="margin:0">
                    <span>Description</span>
                    <input name="description" value="<?= e($role['description'] ?? field_value($_POST, 'description')) ?>" placeholder="Courte description du role" <?= $isSystem ? 'disabled' : '' ?>>
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding-bottom:4px;">
                    <input type="checkbox" name="is_internal" value="1"
                        <?= ($role && (int) ($role['is_internal'] ?? 0)) ? 'checked' : '' ?>
                        <?= $isSystem ? 'disabled' : '' ?>>
                    <span style="font-size:0.82rem;white-space:nowrap">Role interne</span>
                </label>
            </div>

            <div class="perms-scroll">
                <table class="perms-table">
                    <?php foreach ($permissionsByCategory as $cat => $perms): ?>
                        <?php
                            $catChecked = 0;
                            foreach ($perms as $p) {
                                if (in_array((int) $p['id'], $rolePermIds, true)) $catChecked++;
                            }
                            $catTotal = count($perms);
                            $allChecked = $catChecked === $catTotal;
                        ?>
                        <thead>
                            <tr class="cat-head">
                                <th colspan="3">
                                    <span class="cat-head-left">
                                        <?php if (isset($categoryIcons[$cat])): ?>
                                            <span class="material-symbols-outlined"><?= e($categoryIcons[$cat]) ?></span>
                                        <?php endif; ?>
                                        <?= e($categoryLabels[$cat] ?? $cat) ?>
                                    </span>
                                    <span class="cat-head-right">
                                        <span class="badge badge-info"><?= $catChecked ?>/<?= $catTotal ?></span>
                                        <?php if (!$isSystem): ?>
                                            <button type="button" class="select-all-toggle" data-toggle-cat="<?= e($cat) ?>"
                                                data-state="<?= $allChecked ? 'checked' : 'unchecked' ?>">
                                                <?= $allChecked ? 'Tout deselect' : 'Tout select' ?>
                                            </button>
                                        <?php endif; ?>
                                    </span>
                                </th>
                            </tr>
                            <tr class="cat-cols">
                                <th style="width:28px"></th>
                                <th data-col="perm">Permission</th>
                                <th data-col="key">Cle</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($perms as $p): ?>
                            <?php $checked = in_array((int) $p['id'], $rolePermIds, true); ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="permissions[]" value="<?= (int) $p['id'] ?>"
                                        data-cat="<?= e($cat) ?>"
                                        <?= $checked ? 'checked' : '' ?>
                                        <?= $isSystem ? 'disabled' : '' ?>>
                                </td>
                                <td class="perm-name"><?= e($p['nom']) ?></td>
                                <td><code class="perm-key"><?= e($p['permission_key']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php if (!$isSystem): ?>
                <div style="display:flex;gap:10px;align-items:center;padding-top:14px;border-top:1px solid var(--line);margin-top:16px;">
                    <button type="submit"><span class="material-symbols-outlined">save</span> <?= $role ? 'Enregistrer les modifications' : 'Creer le role' ?></button>
                    <a class="btn btn-cancel" href="<?= e(app_url('roles')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($role): ?>
    <div id="tab-users" style="display:none">
        <div style="background:var(--panel);border:1px solid var(--line);border-radius:8px;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--line);">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:1.1rem;color:var(--text-secondary)">work</span>
                    <strong style="font-size:0.85rem;">Collaborateurs</strong>
                    <span class="badge badge-info"><?= count($roleUsers) ?></span>
                </div>
                <a class="btn btn-next" href="<?= e(app_url('collaborateur', ['role_id' => $role['id']])) ?>" style="font-size:0.78rem;padding:4px 12px;">
                    <span class="material-symbols-outlined" style="font-size:0.9rem">person_add</span> Ajouter
                </a>
            </div>

            <?php if (!$roleUsers): ?>
                <p class="table-empty" style="margin:0;padding:40px 20px;">Aucun collaborateur n a ce role pour le moment.</p>
            <?php else: ?>
                <div class="table-scroll">
                <table data-sortable>
                    <thead>
                    <tr>
                        <th data-col="nom">Collaborateur</th>
                        <th data-col="cabinet">Cabinet</th>
                        <th data-col="fonction">Fonction</th>
                        <th data-col="statut">Statut</th>
                        <th data-col="acces">Acces app</th>
                        <th data-col="connexion">Derniere connexion</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($roleUsers as $u): ?>
                        <?php $initials = user_initials($u['nom_complet']); ?>
                        <?php $aColor = avatar_color($u['nom_complet'], $avatarColors); ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span class="user-avatar <?= $aColor ?>"><?= e($initials) ?></span>
                                    <div class="user-cell-info">
                                        <span class="user-cell-name"><?= e($u['nom_complet']) ?></span>
                                        <span class="user-cell-email"><?= e($u['email'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($u['den_ste'] ?? '-') ?></td>
                            <td><?= e($u['fonction'] ?? '-') ?></td>
                            <td><span class="badge <?= $u['statut'] === 'actif' ? 'badge-success' : 'badge-danger' ?>"><?= e($u['statut'] ?? '-') ?></span></td>
                            <td>
                                <?php if ((int) ($u['can_login'] ?? 0)): ?>
                                    <span class="badge badge-success">Connectable</span>
                                <?php else: ?>
                                    <span class="badge">Aucun acces</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.78rem;color:var(--text-secondary)">
                                <?= e($u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais') ?>
                            </td>
                            <td class="table-actions">
                                <a class="btn-icon" href="<?= e(app_url('collaborateur', ['id' => (int) $u['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tabs
    var tabs = document.querySelectorAll('.tab[data-tab]');
    var panes = { permissions: document.getElementById('tab-permissions'), users: document.getElementById('tab-users') };

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = this.getAttribute('data-tab');
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            if (panes.permissions) panes.permissions.style.display = target === 'permissions' ? '' : 'none';
            if (panes.users) panes.users.style.display = target === 'users' ? '' : 'none';
        });
    });

    <?php if (!$isSystem): ?>
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
        var totalCheckboxes = document.querySelectorAll('input[name="permissions[]"]').length;
        var totalChecked = document.querySelectorAll('input[name="permissions[]"]:checked').length;
        var totalBadge = document.getElementById('total-perm-badge');
        if (totalBadge) totalBadge.textContent = totalChecked + '/' + totalCheckboxes;

        document.querySelectorAll('[data-toggle-cat]').forEach(function(btn) {
            var cat = btn.getAttribute('data-toggle-cat');
            var catCbs = document.querySelectorAll('input[data-cat="' + cat + '"]');
            var catChecked = 0;
            catCbs.forEach(function(cb) { if (cb.checked) catChecked++; });
            var catTotal = catCbs.length;
            var allCatChecked = catChecked === catTotal;
            btn.setAttribute('data-state', allCatChecked ? 'checked' : 'unchecked');
            btn.textContent = allCatChecked ? 'Tout deselect' : 'Tout select';

            var catHead = btn.closest('.cat-head');
            if (catHead) {
                var catBadge = catHead.querySelector('.badge');
                if (catBadge) catBadge.textContent = catChecked + '/' + catTotal;
            }
        });
    }

    updateSummary();
    <?php endif; ?>
});
</script>