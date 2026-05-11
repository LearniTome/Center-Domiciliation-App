<?php

declare(strict_types=1);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM contrats WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Contrat supprime avec succes.');
        redirect_to('contrats');
    }
}

$contrats = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT contrats.*, societes.raison_sociale
        FROM contrats
        INNER JOIN societes ON societes.id = contrats.societe_id
        ORDER BY contrats.id DESC
    ')->fetchAll()
    : [];

?>
<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Contrats</h2>
                <p class="help-text"><?= count($contrats) ?> enregistrement(s)</p>
            </div>
        </div>
        <?php if (!$contrats): ?>
            <p class="table-empty">Aucun contrat pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Societe</th>
                    <th>Type</th>
                    <th>Periode</th>
                    <th>Statut</th>
                    <th>Creation</th>
                    <th>Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['raison_sociale']) ?></td>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e(($contrat['date_debut'] ?: '-') . ' -> ' . ($contrat['date_fin'] ?: '-')) ?></td>
                        <td><?= e($contrat['statut']) ?></td>
                        <td><?= e(substr($contrat['created_at'], 0, 10)) ?></td>
                        <td><?= e(substr($contrat['updated_at'], 0, 10)) ?></td>
                        <td class="table-actions">
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $contrat['id']) ?>">
                                <button class="btn-danger" type="submit" data-confirm="Supprimer ce contrat ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

