<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifie']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = (int) $user['id'];
$roleId = (int) ($user['role_id'] ?? 0);
$collabType = $user['collaborateur_type'] ?? null;

try {
    switch ($action) {
        case 'count':
            $count = count_unread_notifications($pdo, $userId, $roleId, $collabType);
            echo json_encode(['count' => $count]);
            break;

        case 'list':
            $count = count_unread_notifications($pdo, $userId, $roleId, $collabType);
            $notifs = get_user_notifications($pdo, $userId, $roleId, $collabType, 5, true);
            $html = '';
            $iconMap = ['info' => 'info', 'success' => 'check_circle', 'warning' => 'warning', 'danger' => 'error'];
            foreach ($notifs as $n) {
                $icon = $iconMap[$n['type']] ?? 'info';
                $link = $n['link'] ? e($n['link']) : '#';
                $timeAgo = time_ago($n['created_at'] ?? '');
                $title = e($n['title'] ?? '');
                $msg = $n['message'] ? e(mb_substr($n['message'], 0, 80)) : '';
                $html .= '<div class="notif-item unread" data-notif-id="' . (int) $n['id'] . '" data-link="' . $link . '">';
                $html .= '<div class="notif-icon ' . e($n['type'] ?? 'info') . '"><span class="material-symbols-outlined">' . $icon . '</span></div>';
                $html .= '<div class="notif-body"><strong>' . $title . '</strong>';
                if ($msg) $html .= '<p>' . $msg . '</p>';
                $html .= '</div>';
                $html .= '<div class="notif-meta">';
                $html .= '<span>' . $timeAgo . '</span>';
                $html .= '<button type="button" class="btn-icon notif-dropdown-mark" data-notif-mark="' . (int) $n['id'] . '" title="Marquer comme lu"><span class="material-symbols-outlined">mark_email_read</span></button>';
                $html .= '</div></div>';
            }
            echo json_encode(['count' => $count, 'html' => $html, 'total' => count($notifs)]);
            break;

        case 'mark_read':
            verify_csrf();
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0 && $pdo instanceof PDO) {
                mark_notification_read($pdo, $id, $userId);
            }
            $count = count_unread_notifications($pdo, $userId, $roleId, $collabType);
            echo json_encode(['success' => true, 'count' => $count]);
            break;

        case 'mark_all_read':
            verify_csrf();
            if ($pdo instanceof PDO) {
                mark_all_notifications_read($pdo, $userId, $roleId, $collabType);
            }
            $count = count_unread_notifications($pdo, $userId, $roleId, $collabType);
            echo json_encode(['success' => true, 'count' => $count]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action inconnue']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
