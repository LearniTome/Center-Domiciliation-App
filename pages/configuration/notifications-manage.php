<?php

declare(strict_types=1);

$user = current_user();
if (!$user || (int) ($user['role_id'] ?? 0) !== 1) {
    redirect_to('dashboard');
}

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'info';
        $targetRole = $_POST['target_role'] ?? '';
        $targetType = $_POST['target_type'] ?? '';

        if ($title !== '') {
            $data = [
                'type' => in_array($type, ['info', 'success', 'warning', 'danger'], true) ? $type : 'info',
                'title' => $title,
                'message' => $message ?: null,
                'link' => $_POST['link'] ?: null,
                'entity_type' => $_POST['entity_type'] ?: null,
                'entity_id' => $_POST['entity_id'] ? (int) $_POST['entity_id'] : null,
                'created_by' => (int) $user['id'],
                'is_global' => 0,
                'target_user_id' => null,
                'target_role_id' => null,
                'target_type' => null,
            ];

            switch ($targetRole) {
                case 'super_admin':
                    $data['target_role_id'] = 1;
                    break;
                case 'admin':
                    $data['target_role_id'] = 2;
                    break;
                case 'all_internal':
                    $data['target_type'] = 'interne';
                    break;
                case 'all_external':
                    $data['target_type'] = 'externe-pp';
                    // Also create for externe-pm
                    $data2 = $data;
                    $data2['target_type'] = 'externe-pm';
                    create_notification($pdo, $data2);
                    break;
                case 'all':
                    $data['is_global'] = 1;
                    break;
                default:
                    $data['is_global'] = 1;
                    break;
            }

            create_notification($pdo, $data);
            log_activity($pdo, 'create', 'notification', null, $title);
            set_flash('success', 'Notification creee avec succes.');
            redirect_to('notifications-manage');
        }
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Notification supprimee.');
        redirect_to('notifications-manage');
    }

    if ($action === 'generate_auto') {
        $generated = generate_auto_notifications($pdo, (int) $user['id']);
        $count = count($generated);
        set_flash('success', "$count notification(s) automatique(s) genere(e)s.");
        redirect_to('notifications-manage');
    }
}

$allNotifications = [];
if ($pdo) {
    $stmt = $pdo->query('
        SELECT n.*, c.nom_complet AS creator_name
        FROM notifications n
        LEFT JOIN collaborateurs c ON c.id = n.created_by
        ORDER BY n.created_at DESC
        LIMIT 100
    ');
    $allNotifications = $stmt->fetchAll();

    $unreadCount = $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
    $totalCount = $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
}
?>
<section>
    <div class="section-header" style="margin-bottom:1rem;">
        <h2>Gestion des notifications</h2>
        <div class="table-actions">
            <span style="font-size:0.85rem;color:var(--text-secondary);margin-right:8px;">
                <?= (int) $totalCount ?> totale(s) &middot; <?= (int) $unreadCount ?> non lue(s)
            </span>
            <form method="post" style="display:inline">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="generate_auto">
                <button type="submit" class="btn btn-info">
                    <span class="material-symbols-outlined">sync</span> Generer automatiquement
                </button>
            </form>
            <button type="button" class="btn btn-next" data-toggle="create-form" onclick="document.getElementById('create-form').classList.toggle('hidden')">
                <span class="material-symbols-outlined">add</span> Nouvelle notification
            </button>
        </div>
    </div>

    <article class="card stack hidden" id="create-form" style="margin-bottom:1rem;">
        <h3>Creer une notification</h3>
        <form method="post" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create">

            <label class="field">
                <span>Titre *</span>
                <input type="text" name="title" required maxlength="255" placeholder="Titre de la notification">
            </label>

            <label class="field">
                <span>Message</span>
                <textarea name="message" rows="3" placeholder="Description optionnelle"></textarea>
            </label>

            <label class="field">
                <span>Type</span>
                <select name="type">
                    <option value="info">Information</option>
                    <option value="success">Succes</option>
                    <option value="warning">Avertissement</option>
                    <option value="danger">Urgent</option>
                </select>
            </label>

            <label class="field">
                <span>Cible</span>
                <select name="target_role">
                    <option value="all">Tous les collaborateurs</option>
                    <option value="super_admin">Super Admin uniquement</option>
                    <option value="admin">Administrateurs</option>
                    <option value="all_internal">Tous les internes</option>
                    <option value="all_external">Tous les externes</option>
                </select>
            </label>

            <label class="field">
                <span>Lien (optionnel)</span>
                <input type="text" name="link" placeholder="ex: index.php?page=societe&id=1">
            </label>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <label class="field">
                    <span>Entite (optionnel)</span>
                    <input type="text" name="entity_type" placeholder="societe, contrat, etc.">
                </label>
                <label class="field">
                    <span>ID entite</span>
                    <input type="number" name="entity_id" placeholder="ID">
                </label>
            </div>

            <button type="submit" class="btn btn-next" style="justify-self:start;">
                <span class="material-symbols-outlined">send</span> Creer la notification
            </button>
        </form>
    </article>

    <article class="card">
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="date">Date</th>
                        <th data-col="type">Type</th>
                        <th data-col="title">Titre</th>
                        <th data-col="target">Cible</th>
                        <th data-col="read">Lues</th>
                        <th data-col="creator">Cree par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allNotifications)): ?>
                        <tr><td colspan="7" class="table-empty">Aucune notification.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allNotifications as $n): ?>
                            <?php
                                $readCount = 0;
                                try {
                                    $rc = $pdo->prepare('SELECT COUNT(*) FROM notifications n2 WHERE n2.id = :id AND n2.is_read = 1');
                                    $rc->execute(['id' => $n['id']]);
                                    $readCount = (int) $rc->fetchColumn();
                                } catch (PDOException) {}
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></td>
                                <td>
                                    <span class="statut-badge" style="background:rgba(<?php
                                        $c = match ($n['type'] ?? 'info') {
                                            'success' => '0,210,91',
                                            'warning' => '255,171,0',
                                            'danger' => '252,66,74',
                                            default => '0,144,231',
                                        };
                                        echo $c;
                                    ?>,0.12);color:var(--<?= $n['type'] ?? 'info' ?>);">
                                        <?= e($n['type'] ?? 'info') ?>
                                    </span>
                                </td>
                                <td><strong><?= e($n['title'] ?? '') ?></strong></td>
                                <td style="font-size:0.8rem;">
                                    <?php if ($n['is_global']): ?>
                                        <span class="statut-badge" style="background:rgba(0,210,91,0.12);color:var(--success);">Tous</span>
                                    <?php elseif ($n['target_user_id']): ?>
                                        Utilisateur #<?= (int) $n['target_user_id'] ?>
                                    <?php elseif ($n['target_role_id']): ?>
                                        Role #<?= (int) $n['target_role_id'] ?>
                                    <?php elseif ($n['target_type']): ?>
                                        <?= e($n['target_type']) ?>
                                    <?php else: ?>
                                        Tous
                                    <?php endif; ?>
                                </td>
                                <td><?= $readCount ?></td>
                                <td><?= e($n['creator_name'] ?? '-') ?></td>
                                <td class="table-actions">
                                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette notification ?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                        <button type="submit" class="btn-icon danger">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
