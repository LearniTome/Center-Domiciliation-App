<?php

declare(strict_types=1);

$query = search_term();

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $delStmt = $pdo->prepare('SELECT nom_complet, collaborateur_email FROM collaborateurs WHERE id = :id');
        $delStmt->execute(['id' => (int) $_POST['id']]);
        $delRecord = $delStmt->fetch();
        $stmt = $pdo->prepare('DELETE FROM collaborateurs WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'collaborateur', (int) $_POST['id'], $delRecord['nom_complet'] ?? '');
        set_flash('success', 'Collaborateur supprime avec succes.');
        redirect_to('collaborateurs');
    }
}

$collaborateurs = [];
if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $stmt = $pdo->prepare("
            SELECT c.*, r.nom AS role_nom, r.is_internal
            FROM collaborateurs c
            LEFT JOIN roles r ON r.id = c.role_id
            WHERE c.nom_complet LIKE :term
               OR c.den_ste LIKE :term
               OR c.collaborateur_ice LIKE :term
               OR c.fonction LIKE :term
               OR r.nom LIKE :term
            ORDER BY c.id DESC
        ");
        $stmt->execute(['term' => like_term($query)]);
        $collaborateurs = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query('
            SELECT c.*, r.nom AS role_nom, r.is_internal
            FROM collaborateurs c
            LEFT JOIN roles r ON r.id = c.role_id
            ORDER BY c.id DESC
        ');
        $collaborateurs = $stmt->fetchAll();
    }

    if (($_GET['export'] ?? '') === 'csv') {
        $rows = array_map(static function (array $c): array {
            return [
                $c['id'],
                $c['role_nom'] ?? '-',
                (static function () use ($c): string {
    $t = $c['collaborateur_type'] ?? '';
    if (in_array($t, ['interne', 'externe-pm', 'externe-pp'], true)) return $t;
    $ds = $c['den_ste'] ?? '';
    return ((int) ($c['can_login'] ?? 0)) ? 'interne' : (($ds && $ds !== 'NULL') ? 'externe-pm' : 'externe-pp');
})(),
                (int) ($c['can_login'] ?? 0) ? 'Oui' : 'Non',
                $c['den_ste'],
                $c['nom_complet'],
                $c['fonction'],
                $c['collaborateur_code'],
                $c['collaborateur_ice'],
                $c['collaborateur_tp'],
                $c['collaborateur_rc'],
                $c['collaborateur_if'],
                $c['collaborateur_tel_fixe'],
                $c['collaborateur_tel_mobile'],
                $c['collaborateur_email'],
                $c['collaborateur_adresse'],
                $c['statut'],
            ];
        }, $collaborateurs);

        export_csv('collaborateurs.csv', [
            'ID',
            'Role',
            'Type',
            'Acces app',
            'Cabinet',
            'Nom complet',
            'Fonction',
            'Code',
            'ICE',
            'TP',
            'RC',
            'IF',
            'Tel fixe',
            'Tel mobile',
            'Email',
            'Adresse',
            'Statut',
        ], $rows);
    }
}
?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($collaborateurs) ?> enregistrement(s)</span>
            <div class="table-actions">
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-next" href="<?= e(app_url('collaborateur')) ?>"><span class="material-symbols-outlined">person_add</span> Nouveau collaborateur</a>
                <a class="btn btn-info" href="<?= e(app_url('collaborateurs', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> Exporter CSV</a>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="collaborateurs">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par nom, role, ICE ou cabinet"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$collaborateurs): ?>
            <p class="table-empty">Aucun collaborateur pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable>
                <thead>
                <tr>
                    <th data-col="role">Role</th>
                    <th data-col="type">Type</th>
                    <th data-col="acces">Acces app</th>
                    <th data-col="cabinet">Cabinet</th>
                    <th data-col="nom-complet">Nom complet</th>
                    <th data-col="fonction">Fonction</th>
                    <th data-col="ice">ICE</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="derniere-connexion">Derniere connexion</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($collaborateurs as $c): ?>
                    <tr>
                        <td>
                            <?php
                                $isInternal = (int) ($c['is_internal'] ?? 0);
                                $roleName = $c['role_nom'] ?: '—';
                            ?>
                            <span class="badge <?= $isInternal ? 'badge-info' : 'badge-secondary' ?>">
                                <?= e($roleName) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                                $ct = $c['collaborateur_type'] ?? '';
                                if (!in_array($ct, ['interne', 'externe-pm', 'externe-pp'], true)) {
                                    $denSte = $c['den_ste'] ?? '';
                                    $ct = ((int) ($c['can_login'] ?? 0)) ? 'interne' : (($denSte && $denSte !== 'NULL') ? 'externe-pm' : 'externe-pp');
                                }
                                $typeLabels = ['interne' => 'Interne', 'externe-pm' => 'PM', 'externe-pp' => 'PP'];
                                $typeClass = ['interne' => 'badge-info', 'externe-pm' => 'badge-secondary', 'externe-pp' => 'badge-warning'];
                            ?>
                            <span class="badge <?= $typeClass[$ct] ?? 'badge' ?>"><?= $typeLabels[$ct] ?? '-' ?></span>
                        </td>
                        <td>
                            <?php if ((int) ($c['can_login'] ?? 0)): ?>
                                <span class="badge badge-success" title="Derniere connexion: <?= e(format_date($c['last_login'] ?? null)) ?>">Connectable</span>
                            <?php else: ?>
                                <span class="badge">Aucun acces</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($c['den_ste'] ?? '-') ?></td>
                        <td><a href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500;"><?= e($c['nom_complet']) ?></a></td>
                        <td><?= e($c['fonction'] ?? '-') ?></td>
                        <td><?= e($c['collaborateur_ice'] ?? '-') ?></td>
                        <td><?= e($c['collaborateur_tel_mobile'] ?: $c['collaborateur_tel_fixe'] ?: $c['telephone'] ?: '-') ?></td>
                        <td><?= e($c['statut']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $c['created_at']))) ?></td>
                        <td><?= e($c['last_login'] ? date('d/m/Y H:i', strtotime($c['last_login'])) : '-') ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $c['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce collaborateur ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>