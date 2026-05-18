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
            WHERE societe_raison_sociale LIKE :term OR societe_forme_juridique LIKE :term OR societe_ice LIKE :term OR societe_ville LIKE :term
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
                $societe['societe_raison_sociale'],
                $societe['societe_dossier'],
                $societe['societe_forme_juridique'],
                $societe['societe_ice'],
                $societe['societe_date_ice'],
                $societe['societe_rc'],
                $societe['societe_if'],
                $societe['societe_activites_statuts'] ?? '',
                $societe['societe_activites_ompic'] ? fetch_activites_ompic_display($pdo ?? null, (string) $societe['societe_activites_ompic']) : '',
                $societe['societe_tribunal'],
                $societe['societe_ville'],
                $societe['societe_email'],
                $societe['societe_telephone'],
                $societe['societe_capital'],
            ];
        }, $societes);

        export_csv('societes.csv', [
            'ID',
            'Raison sociale',
            'Dossier domiciliation',
            'Forme juridique',
            'ICE',
            'Date cert. negatif',
            'RC',
            'IF',
            'Activites (Statuts)',
            'Activites (OMPIC)',
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
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="mdi mdi-table-column"></span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
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
                    <th data-col="date-ompic">Date cert. OMPIC</th>
                    <th data-col="rc">RC</th>
                    <th data-col="if">IF</th>
                    <th data-col="activites-statuts">Activites (Statuts)</th>
                    <th data-col="activites-ompic">Activites (OMPIC)</th>
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
                        <td><?= e($societe['societe_dossier'] ?? '-') ?></td>
                        <td><?= e($societe['societe_raison_sociale']) ?></td>
                        <td><?= e($societe['societe_forme_juridique']) ?></td>
                        <td><?= e($societe['societe_ice'] ?? '-') ?></td>
                        <td><?= e(format_date($societe['societe_date_ice'] ?? null)) ?></td>
                        <td><?= e($societe['societe_rc'] ?? '-') ?></td>
                        <td><?= e($societe['societe_if'] ?? '-') ?></td>
                        <td><?= e(!empty($societe['societe_activites_statuts']) ? (string) $societe['societe_activites_statuts'] : '-') ?></td>
                        <td><?= e(!empty($societe['societe_activites_ompic']) ? fetch_activites_ompic_display($pdo ?? null, (string) $societe['societe_activites_ompic']) : '-') ?></td>
                        <td><?= $societe['societe_capital'] !== null ? e(number_format((float) $societe['societe_capital'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= e($societe['societe_ville']) ?></td>
                        <td><?= e($societe['societe_tribunal'] ?? '-') ?></td>
                        <td><?= e($societe['societe_telephone']) ?></td>
                        <td><?= e($societe['societe_email'] ?? '-') ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $societe['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $societe['updated_at']))) ?></td>
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
