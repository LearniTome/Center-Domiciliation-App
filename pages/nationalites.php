<?php

declare(strict_types=1);

$nationalites = fetch_reference_options($pdo ?? null, 'ref_nationalites', 'nationalite');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $nationalite = field_value($_POST, 'nationalite');
        if ($nationalite !== '') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_nationalites (nationalite) VALUES (:nationalite)');
            $stmt->execute(['nationalite' => $nationalite]);
            set_flash('success', 'Nationalite ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('nationalites');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $nationalite = field_value($_POST, 'nationalite');
        if ($nationalite !== '') {
            $stmt = $pdo->prepare('DELETE FROM ref_nationalites WHERE nationalite = :nationalite');
            $stmt->execute(['nationalite' => $nationalite]);
            set_flash('success', 'Nationalite supprimee.');
        }
        redirect_to('nationalites');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Gestion des nationalites</h2>
            <p class="help-text">Ajoutez ou supprimez des nationalites.</p>
        </div>
        <a class="btn btn-back" href="<?= e(app_url('creation')) ?>"><span class="mdi mdi-arrow-left"></span> Retour</a>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">
            <span>Nouvelle nationalite</span>
            <div style="display:flex;gap:8px">
                <input name="nationalite" required>
                <button type="submit" class="btn-icon" title="Ajouter"><span class="mdi mdi-plus"></span></button>
            </div>
        </label>
    </form>

    <?php if (count($nationalites) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Nationalite</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nationalites as $nationalite): ?>
                    <tr>
                        <td><?= e($nationalite) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="nationalite" value="<?= e($nationalite) ?>">
                                <button type="submit" class="btn btn-danger" data-confirm="Supprimer <?= e($nationalite) ?> ?"><span class="mdi mdi-delete"></span> Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucune nationalite.</p>
    <?php endif; ?>
</section>
