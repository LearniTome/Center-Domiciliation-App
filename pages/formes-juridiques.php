<?php

declare(strict_types=1);

$formes = fetch_reference_options($pdo ?? null, 'ref_formes_juridiques', 'forme_juridique');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $forme = field_value($_POST, 'forme_juridique');
        if ($forme !== '') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_formes_juridiques (forme_juridique) VALUES (:forme)');
            $stmt->execute(['forme' => $forme]);
            set_flash('success', 'Forme juridique ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('formes-juridiques');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $forme = field_value($_POST, 'forme_juridique');
        if ($forme !== '') {
            $stmt = $pdo->prepare('DELETE FROM ref_formes_juridiques WHERE forme_juridique = :forme');
            $stmt->execute(['forme' => $forme]);
            set_flash('success', 'Forme juridique supprimee.');
        }
        redirect_to('formes-juridiques');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Gestion des formes juridiques</h2>
            <p class="help-text">Ajoutez ou supprimez des formes juridiques.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(app_url('creation')) ?>">Retour</a>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">
            <span>Nouvelle forme juridique</span>
            <div style="display:flex;gap:8px">
                <input name="forme_juridique" required>
                <button type="submit" class="btn">Ajouter</button>
            </div>
        </label>
    </form>

    <?php if (count($formes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Forme juridique</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($formes as $forme): ?>
                    <tr>
                        <td><?= e($forme) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="forme_juridique" value="<?= e($forme) ?>">
                                <button type="submit" class="btn btn-secondary" data-confirm="Supprimer <?= e($forme) ?> ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucune forme juridique.</p>
    <?php endif; ?>
</section>
