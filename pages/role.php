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
            // Check if editing system role
            $stmt = $pdo->prepare('SELECT is_system FROM roles WHERE id = :id');
            $stmt->execute(['id' => $editingId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                set_flash('error', 'Role introuvable.');
                redirect_to('roles');
            }

            $stmt = $pdo->prepare('UPDATE roles SET nom = :nom, description = :description, is_internal = :is_internal WHERE id = :id');
            $stmt->execute(['nom' => $nom, 'description' => $description, 'is_internal' => $isInternal, 'id' => $editingId]);

            // Update permissions (delete all, re-insert)
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $editingId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO roles (nom, description, is_internal, is_system, sort_order) VALUES (:nom, :description, :is_internal, 0, 99)');
            $stmt->execute(['nom' => $nom, 'description' => $description, 'is_internal' => $isInternal]);
            $editingId = (int) $pdo->lastInsertId();
        }

        // Insert permissions
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

// Fetch role data
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

// Fetch all permissions grouped by category
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

// Fetch role's current permissions
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
?>
<section>
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2><?= $role ? 'Modifier le role' : 'Nouveau role' ?></h2>
                <p class="help-text"><?= $role ? e($role['nom']) : 'Creer un nouveau role avec ses permissions' ?></p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('roles')) ?>">Retour</a>
            </div>
        </div>

        <form method="post" class="stack">
            <?= csrf_input() ?>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <label class="field">
                    <span>Nom du role *</span>
                    <input name="nom" required value="<?= e($role['nom'] ?? field_value($_POST, 'nom')) ?>" placeholder="ex: Chef d equipe">
                </label>

                <label class="field">
                    <span>Description</span>
                    <input name="description" value="<?= e($role['description'] ?? field_value($_POST, 'description')) ?>" placeholder="Courte description">
                </label>

                <label class="field" style="grid-column: 1 / -1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_internal" value="1" <?= ($role && (int) ($role['is_internal'] ?? 0)) ? 'checked' : '' ?>>
                        <span>Role interne (collaborateur du centre)</span>
                    </label>
                </label>
            </div>

            <h3 class="section-title">Permissions</h3>

            <div class="form-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach ($permissionsByCategory as $cat => $perms): ?>
                    <div class="card" style="padding:0.8rem;">
                        <h4 style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-secondary);margin-bottom:0.5rem;">
                            <?= e($categoryLabels[$cat] ?? $cat) ?>
                        </h4>
                        <?php foreach ($perms as $p): ?>
                            <label style="display:flex;align-items:flex-start;gap:8px;padding:4px 0;cursor:pointer;">
                                <input type="checkbox" name="permissions[]" value="<?= (int) $p['id'] ?>"
                                    style="margin-top:2px;"
                                    <?= in_array((int) $p['id'], $rolePermIds, true) ? 'checked' : '' ?>
                                    <?= ($role && (int) ($role['is_system'] ?? 0)) ? 'disabled' : '' ?>>
                                <span style="font-size:0.85rem;line-height:1.3;">
                                    <?= e($p['nom']) ?>
                                    <small style="display:block;color:var(--text-muted);font-size:0.7rem;"><?= e($p['permission_key']) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($role && (int) ($role['is_system'] ?? 0)): ?>
                <p class="help-text" style="color:var(--warning);">Les roles systeme ne peuvent pas etre modifies. Creez un nouveau role si necessaire.</p>
            <?php endif; ?>

            <button type="submit" <?= ($role && (int) ($role['is_system'] ?? 0)) ? 'disabled' : '' ?>>
                <span class="material-symbols-outlined">save</span> <?= $role ? 'Mettre a jour' : 'Creer le role' ?>
            </button>
        </form>
    </article>
</section>