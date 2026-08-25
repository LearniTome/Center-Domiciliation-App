<?php

declare(strict_types=1);

$user = current_user();
if (!$user) redirect_to('connexion');

$filter = $_GET['f'] ?? 'unread';

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read' && isset($_POST['id'])) {
        mark_notification_read($pdo, (int) $_POST['id'], (int) $user['id']);
    } elseif ($action === 'mark_all_read') {
        mark_all_notifications_read($pdo, (int) $user['id'], (int) ($user['role_id'] ?? 0), $user['collaborateur_type'] ?? null);
    }

    if ($action === 'mark_read' || $action === 'mark_all_read') {
        redirect_to('notifications', $filter !== 'unread' ? ['f' => $filter] : []);
    }
}

$notifications = get_user_notifications(
    $pdo,
    (int) $user['id'],
    (int) ($user['role_id'] ?? 0),
    $user['collaborateur_type'] ?? null,
    50,
    $filter === 'unread'
);

// If requesting "all", fetch all including read
if ($filter === 'all') {
    $notifications = get_user_notifications(
        $pdo,
        (int) $user['id'],
        (int) ($user['role_id'] ?? 0),
        $user['collaborateur_type'] ?? null,
        100,
        false
    );
}

$unreadCount = count_unread_notifications($pdo, (int) $user['id'], (int) ($user['role_id'] ?? 0), $user['collaborateur_type'] ?? null);
?>
<section>
    <div class="notif-page-header">
        <form method="post" style="display:inline">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="mark_all_read">
            <?php if ($unreadCount > 0): ?>
                <button type="submit" class="btn btn-info">
                    <span class="material-symbols-outlined">done_all</span> Tout marquer comme lu
                </button>
            <?php endif; ?>
        </form>
    </div>

    <div class="notif-filters" style="margin-bottom:1rem;">
        <a href="<?= e(app_url('notifications', ['f' => 'unread'])) ?>" class="<?= $filter === 'unread' ? 'active' : '' ?>">
            Non lues <?php if ($unreadCount > 0): ?><span class="notif-badge" style="margin-left:4px;display:inline-flex;vertical-align:middle;"><?= $unreadCount ?></span><?php endif; ?>
        </a>
        <a href="<?= e(app_url('notifications', ['f' => 'all'])) ?>" class="<?= $filter === 'all' ? 'active' : '' ?>">Toutes</a>
    </div>

    <article class="card">
        <?php if (empty($notifications)): ?>
            <div class="notif-empty">
                <span class="material-symbols-outlined">notifications_off</span>
                <p>Aucune notification<?= $filter === 'unread' ? ' non lue' : '' ?>.</p>
            </div>
        <?php else: ?>
            <div class="notif-list">
                <?php foreach ($notifications as $n): ?>
                    <?php
                        $isUnread = !(int) ($n['is_read'] ?? 0);
                        $iconMap = ['info' => 'info', 'success' => 'check_circle', 'warning' => 'warning', 'danger' => 'error'];
                        $icon = $iconMap[$n['type']] ?? 'info';
                        $link = $n['link'] ? e($n['link']) : null;
                        $timeAgo = time_ago($n['created_at'] ?? '');
                    ?>
                    <div class="notif-item <?= e($isUnread ? 'unread' : '') ?>" data-notif-id="<?= e((string)(int) $n['id']) ?>">
                        <div class="notif-icon <?= e($n['type'] ?? 'info') ?>">
                            <span class="material-symbols-outlined"><?= e($icon) ?></span>
                        </div>
                        <div class="notif-body">
                            <strong><?= e($n['title'] ?? '') ?></strong>
                            <?php if ($n['message']): ?>
                                <p><?= e($n['message']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="notif-meta">
                            <span><?= e($timeAgo) ?></span>
                            <?php if ($link): ?>
                                <a href="<?= $link ?>" class="btn-icon info" title="Voir">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($isUnread): ?>
                                <form method="post" style="display:inline">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?= e((string)(int) $n['id']) ?>">
                                    <button type="submit" class="btn-icon" title="Marquer comme lu">
                                        <span class="material-symbols-outlined">mark_email_read</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
