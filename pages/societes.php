<?php

declare(strict_types=1);

$query = search_term();

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM societes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Societe supprimee avec succes.');
        redirect_to('societes');
    }
}

if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $stmt = $pdo->prepare('
            SELECT *
            FROM societes
            WHERE raison_sociale LIKE :term OR forme_juridique LIKE :term OR ice LIKE :term OR ville LIKE :term
            ORDER BY id DESC
        ');
        $stmt->execute(['term' => like_term($query)]);
        $societes = $stmt->fetchAll();
    } else {
        $societes = fetch_all_records($pdo, 'societes');
    }

    if (($_GET['export'] ?? '') === 'csv') {
        $rows = array_map(static function (array $societe): array {
            return [
                $societe['id'],
                $societe['raison_sociale'],
                $societe['dossier_domiciliation'],
                $societe['den_ste'],
                $societe['forme_juridique'],
                $societe['ice'],
                $societe['date_ice'],
                $societe['rc'],
                $societe['if_number'],
                $societe['tribunal'],
                $societe['ville'],
                $societe['email'],
                $societe['telephone'],
                $societe['capital'],
            ];
        }, $societes);

        export_csv('societes.csv', [
            'ID',
            'Raison sociale',
            'Dossier domiciliation',
            'Denomination interne',
            'Forme juridique',
            'ICE',
            'Date ICE',
            'RC',
            'IF',
            'Tribunal',
            'Ville',
            'Email',
            'Telephone',
            'Capital',
        ], $rows);
    }
} else {
    $societes = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Societes enregistrees</h2>
                <p class="help-text"><?= count($societes) ?> enregistrement(s)</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('societes', ['export' => 'csv', 'q' => $query])) ?>">Exporter CSV</a>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="societes">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par societe, ICE, forme ou ville"
                    value="<?= e($query) ?>"
                >
                <button type="submit">Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>">Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$societes): ?>
            <p class="table-empty">Aucune societe pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Raison sociale</th>
                    <th>Forme</th>
                    <th>Ville</th>
                    <th>Contact</th>
                    <th>Creation</th>
                    <th>Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($societes as $societe): ?>
                    <tr>
                        <td><?= e($societe['raison_sociale']) ?></td>
                        <td><?= e($societe['forme_juridique']) ?></td>
                        <td><?= e($societe['ville']) ?></td>
                        <td><?= e($societe['telephone']) ?></td>
                        <td><?= e(substr($societe['created_at'], 0, 10)) ?></td>
                        <td><?= e(substr($societe['updated_at'], 0, 10)) ?></td>
                        <td class="table-actions">
                            <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>">Voir</a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $societe['id']) ?>">
                                <button class="btn-danger" type="submit" data-confirm="Supprimer cette societe ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>
