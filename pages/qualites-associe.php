<?php

declare(strict_types=1);

$qualites = fetch_reference_options($pdo ?? null, 'ref_qualites_associe', 'qualite_associe');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $qualite = field_value($_POST, 'qualite_associe');
        if ($qualite !== '') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_qualites_associe (qualite_associe) VALUES (:qualite)');
            $stmt->execute(['qualite' => $qualite]);
            set_flash('success', 'Qualite ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('qualites-associe');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $qualite = field_value($_POST, 'qualite_associe');
        if ($qualite !== '') {
            $stmt = $pdo->prepare('DELETE FROM ref_qualites_associe WHERE qualite_associe = :qualite');
            $stmt->execute(['qualite' => $qualite]);
            set_flash('success', 'Qualite supprimee.');
        }
        redirect_to('qualites-associe');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Gestion des qualites d'associe</h2>
            <p class="help-text">Ajoutez ou supprimez des qualites d'associe.</p>
        </div>
        <a class="btn btn-back" href="<?= e(app_url('creation')) ?>"><span class="mdi mdi-arrow-left"></span> Retour</a>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">
            <span>Nouvelle qualite</span>
            <div style="display:flex;gap:8px">
                <input name="qualite_associe" required>
                <button type="submit" class="btn-icon" title="Ajouter"><span class="mdi mdi-plus"></span></button>
            </div>
        </label>
    </form>

    <?php if (count($qualites) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Qualite</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($qualites as $qualite): ?>
                    <tr>
                        <td><?= e($qualite) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="qualite_associe" value="<?= e($qualite) ?>">
                                <button type="submit" class="btn btn-danger" data-confirm="Supprimer <?= e($qualite) ?> ?"><span class="mdi mdi-delete"></span> Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucune qualite.</p>
    <?php endif; ?>
</section>
