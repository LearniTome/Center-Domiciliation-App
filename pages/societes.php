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
            'Date de cert. negatif',
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
                <button class="btn-icon" type="button" data-col-toggle-btn title="Colonnes a afficher"><span class="mdi mdi-table-column"></span></button>
                <a class="btn btn-info" href="<?= e(app_url('societes', ['export' => 'csv', 'q' => $query])) ?>"><span class="mdi mdi-download"></span> Exporter CSV</a>
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
                <button type="submit"><span class="mdi mdi-magnify"></span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('societes')) ?>"><span class="mdi mdi-close"></span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$societes): ?>
            <p class="table-empty">Aucune societe pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle>
                <thead>
                <tr>
                    <th data-col="dossier">Dossier</th>
                    <th data-col="raison-sociale">Raison sociale</th>
                    <th data-col="forme">Forme</th>
                    <th data-col="ice">ICE</th>
                    <th data-col="date-cert-neg">Date de cert. negatif</th>
                    <th data-col="rc">RC</th>
                    <th data-col="if">IF</th>
                    <th data-col="capital">Capital</th>
                    <th data-col="ville">Ville</th>
                    <th data-col="tribunal">Tribunal</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="email">Email</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($societes as $societe): ?>
                    <tr>
                        <td><?= e($societe['dossier_domiciliation'] ?? '-') ?></td>
                        <td><?= e($societe['raison_sociale']) ?></td>
                        <td><?= e($societe['forme_juridique']) ?></td>
                        <td><?= e($societe['ice'] ?? '-') ?></td>
                        <td><?= e($societe['date_ice'] ?? '-') ?></td>
                        <td><?= e($societe['rc'] ?? '-') ?></td>
                        <td><?= e($societe['if_number'] ?? '-') ?></td>
                        <td><?= $societe['capital'] !== null ? e(number_format((float) $societe['capital'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= e($societe['ville']) ?></td>
                        <td><?= e($societe['tribunal'] ?? '-') ?></td>
                        <td><?= e($societe['telephone']) ?></td>
                        <td><?= e($societe['email'] ?? '-') ?></td>
                        <td><?= e(substr($societe['created_at'], 0, 10)) ?></td>
                        <td><?= e(substr($societe['updated_at'], 0, 10)) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" title="Voir"><span class="mdi mdi-eye"></span></a>
                            <a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $societe['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="mdi mdi-pencil"></span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $societe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette societe ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
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
