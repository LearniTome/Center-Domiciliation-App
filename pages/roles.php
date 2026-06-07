<?php

declare(strict_types=1);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM roles WHERE id = :id AND is_system = 0');
        $stmt->execute(['id' => (int) $_POST['id']]);
        clear_user_cache();
        log_activity($pdo, 'delete', 'role', (int) $_POST['id']);
        set_flash('success', 'Role supprime.');
        redirect_to('roles');
    }
}

$roles = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('
        SELECT r.*,
               COUNT(c.id) AS nb_collaborateurs,
               COUNT(rp.permission_id) AS nb_permissions
        FROM roles r
        LEFT JOIN collaborateurs c ON c.role_id = r.id
        LEFT JOIN role_permissions rp ON rp.role_id = r.id
        GROUP BY r.id
        ORDER BY r.sort_order ASC, r.nom ASC
    ');
    $roles = $stmt->fetchAll();
}

$internes = array_filter($roles, fn($r) => (int) ($r['is_internal'] ?? 0));
$externes = array_filter($roles, fn($r) => !(int) ($r['is_internal'] ?? 0));
$totalAvecCompte = array_reduce($roles, fn($c, $r) => $c + (int) ($r['nb_collaborateurs'] ?? 0), 0);
?>
<section class="stack">
    <div class="section-header">
        <div>
            <h2>Gestion des roles</h2>
            <p class="help-text"><?= count($roles) ?> roles — <?= $totalAvecCompte ?> collaborateur(s) assigne(s)</p>
        </div>
        <div class="table-actions">
            <a class="btn btn-next" href="<?= e(app_url('role')) ?>"><span class="material-symbols-outlined">add</span> Nouveau role</a>
        </div>
    </div>

    <section class="stats small">
        <article class="stat primary">
            <span>Total roles</span>
            <strong><?= count($roles) ?></strong>
        </article>
        <article class="stat success">
            <span>Internes</span>
            <strong><?= count($internes) ?></strong>
        </article>
        <article class="stat warning">
            <span>Externes</span>
            <strong><?= count($externes) ?></strong>
        </article>
        <article class="stat">
            <span>Collaborateurs assignes</span>
            <strong><?= $totalAvecCompte ?></strong>
        </article>
    </section>

    <?php if (!$roles): ?>
        <p class="table-empty">Aucun role defini.</p>
    <?php else: ?>

        <?php foreach ([
            ['label' => 'Roles internes', 'icon' => 'badge', 'list' => $internes],
            ['label' => 'Roles externes', 'icon' => 'business', 'list' => $externes],
        ] as $group): ?>
            <?php if (empty($group['list'])) { continue; } ?>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined" style="color:var(--text-secondary)"><?= e($group['icon']) ?></span>
                    <span><?= e($group['label']) ?> <small>(<?= count($group['list']) ?>)</small></span>
                </div>
                <div class="table-scroll">
                <table data-sortable>
                    <thead>
                    <tr>
                        <th data-col="nom">Role</th>
                        <th data-col="description">Description</th>
                        <th data-col="permissions">Permissions</th>
                        <th data-col="collaborateurs">Collaborateurs</th>
                        <th data-col="systeme">Systeme</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($group['list'] as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= e(app_url('role', ['id' => (int) $r['id']])) ?>" style="font-weight:600;color:var(--text);text-decoration:none;">
                                    <?= e($r['nom'] ?? '?') ?>
                                </a>
                            </td>
                            <td>
                                <span class="help-text"><?= e($r['description'] ?? '-') ?></span>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= (int) ($r['nb_permissions'] ?? 0) ?> / 38</span>
                            </td>
                            <td>
                                <?php $nb = (int) ($r['nb_collaborateurs'] ?? 0); ?>
                                <span class="badge <?= $nb > 0 ? 'badge-success' : '' ?>"><?= $nb ?></span>
                            </td>
                            <td>
                                <?php if ((int) ($r['is_system'] ?? 0)): ?>
                                    <span class="badge badge-warning">Protege</span>
                                <?php else: ?>
                                    <span class="badge">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <a class="btn-icon info" href="<?= e(app_url('role', ['id' => (int) $r['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php if (!(int) ($r['is_system'] ?? 0)): ?>
                                <form method="post" style="display:inline">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e((string) $r['id']) ?>">
                                    <button class="btn-icon danger" type="submit" data-confirm="Supprimer le role &quot;<?= e($r['nom']) ?>&quot; ? Les collaborateurs avec ce role perdront leur acces." title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</section>