<?php

declare(strict_types=1);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM roles WHERE id = :id AND is_system = 0');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Role supprime.');
        redirect_to('roles');
    }
}

$roles = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('
        SELECT r.*, COUNT(c.id) AS nb_collaborateurs
        FROM roles r
        LEFT JOIN collaborateurs c ON c.role_id = r.id
        GROUP BY r.id
        ORDER BY r.sort_order ASC, r.nom ASC
    ');
    $roles = $stmt->fetchAll();
}
?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($roles) ?> role(s)</span>
            <div class="table-actions">
                <a class="btn btn-next" href="<?= e(app_url('role')) ?>"><span class="material-symbols-outlined">add</span> Nouveau role</a>
            </div>
        </div>

        <?php if (!$roles): ?>
            <p class="table-empty">Aucun role defini.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-sortable>
                <thead>
                <tr>
                    <th data-col="nom">Role</th>
                    <th data-col="type">Type</th>
                    <th data-col="nb">Collaborateurs</th>
                    <th data-col="systeme">Systeme</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($roles as $r): ?>
                    <tr>
                        <td><strong><?= e($r['nom'] ?? '?') ?></strong></td>
                        <td><?= ((int) ($r['is_internal'] ?? 0)) ? '<span class="badge badge-info">Interne</span>' : '<span class="badge badge-secondary">Externe</span>' ?></td>
                        <td><?= (int) ($r['nb_collaborateurs'] ?? 0) ?></td>
                        <td><?= ((int) ($r['is_system'] ?? 0)) ? '<span class="badge badge-success">Oui</span>' : '<span class="badge">Non</span>' ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('role', ['id' => (int) $r['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <?php if (!(int) ($r['is_system'] ?? 0)): ?>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $r['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce role ? Les collaborateurs avec ce role perdront leur acces." title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>