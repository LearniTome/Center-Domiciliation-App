<?php

declare(strict_types=1);

$query = search_term();

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM collaborateurs WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Collaborateur supprime avec succes.');
        redirect_to('collaborateurs');
    }
}

if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM collaborateurs
            WHERE nom_complet LIKE :term
               OR collaborateur_type LIKE :term
               OR den_ste LIKE :term
               OR collaborateur_ice LIKE :term
               OR fonction LIKE :term
            ORDER BY id DESC
        ");
        $stmt->execute(['term' => like_term($query)]);
        $collaborateurs = $stmt->fetchAll();
    } else {
        $collaborateurs = fetch_all_records($pdo, 'collaborateurs');
    }

    if (($_GET['export'] ?? '') === 'csv') {
        $rows = array_map(static function (array $c): array {
            return [
                $c['id'],
                $c['collaborateur_type'],
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
            'Type',
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
} else {
    $collaborateurs = [];
}
?>
<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Collaborateurs</h2>
                <p class="help-text"><?= count($collaborateurs) ?> enregistrement(s)</p>
            </div>
            <div class="table-actions">
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="mdi mdi-table-column"></span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-next" href="<?= e(app_url('collaborateur')) ?>"><span class="mdi mdi-account-plus"></span> Nouveau collaborateur</a>
                <a class="btn btn-info" href="<?= e(app_url('collaborateurs', ['export' => 'csv', 'q' => $query])) ?>"><span class="mdi mdi-download"></span> Exporter CSV</a>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="collaborateurs">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par nom, type, ICE ou cabinet"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="mdi mdi-magnify"></span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('collaborateurs')) ?>"><span class="mdi mdi-close"></span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$collaborateurs): ?>
            <p class="table-empty">Aucun collaborateur pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle>
                <thead>
                <tr>
                    <th data-col="type">Type</th>
                    <th data-col="cabinet">Cabinet</th>
                    <th data-col="nom-complet">Nom complet</th>
                    <th data-col="fonction">Fonction</th>
                    <th data-col="ice">ICE</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="creation">Creation</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($collaborateurs as $c): ?>
                    <tr>
                        <td><?= e($c['collaborateur_type'] ?? '-') ?></td>
                        <td><?= e($c['den_ste'] ?? '-') ?></td>
                        <td><?= e($c['nom_complet']) ?></td>
                        <td><?= e($c['fonction'] ?? '-') ?></td>
                        <td><?= e($c['collaborateur_ice'] ?? '-') ?></td>
                        <td><?= e($c['collaborateur_tel_mobile'] ?: $c['collaborateur_tel_fixe'] ?: $c['telephone'] ?: '-') ?></td>
                        <td><?= e($c['statut']) ?></td>
                        <td><?= e(substr($c['created_at'], 0, 10)) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" title="Voir"><span class="mdi mdi-eye"></span></a>
                            <a class="btn-icon" href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" title="Modifier"><span class="mdi mdi-pencil"></span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $c['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce collaborateur ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
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
