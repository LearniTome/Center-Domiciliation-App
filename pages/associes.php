<?php

declare(strict_types=1);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM associes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Associe supprime avec succes.');
        redirect_to('associes');
    }
}

$associes = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT associes.*, societes.raison_sociale
        FROM associes
        INNER JOIN societes ON societes.id = associes.societe_id
        ORDER BY associes.id DESC
    ')->fetchAll()
    : [];

?>
<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Associes</h2>
                <p class="help-text"><?= count($associes) ?> enregistrement(s)</p>
            </div>
        </div>
        <?php if (!$associes): ?>
            <p class="table-empty">Aucun associe pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom complet</th>
                    <th>Societe</th>
                    <th>CIN</th>
                    <th>Date naissance</th>
                    <th>Lieu naissance</th>
                    <th>Nationalite</th>
                    <th>Telephone</th>
                    <th>Email</th>
                    <th>Qualite</th>
                    <th>Parts</th>
                    <th>Gerant</th>
                    <th>Creation</th>
                    <th>Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['raison_sociale']) ?></td>
                        <td><?= e($associe['cin'] ?? '-') ?></td>
                        <td><?= e($associe['date_naiss'] ?? '-') ?></td>
                        <td><?= e($associe['lieu_naiss'] ?? '-') ?></td>
                        <td><?= e($associe['nationalite'] ?? '-') ?></td>
                        <td><?= e($associe['phone'] ?? '-') ?></td>
                        <td><?= e($associe['email'] ?? '-') ?></td>
                        <td><?= e($associe['qualite_associe'] ?? '-') ?></td>
                        <td><?= $associe['parts'] !== null ? e((string) $associe['parts']) : '-' ?></td>
                        <td><?= (int) $associe['is_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td><?= e(substr($associe['created_at'], 0, 10)) ?></td>
                        <td><?= e(substr($associe['updated_at'], 0, 10)) ?></td>
                        <td class="table-actions">
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $associe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet associe ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
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

