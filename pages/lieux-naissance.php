<?php

declare(strict_types=1);

$lieux = fetch_reference_options($pdo ?? null, 'ref_lieux_naissance', 'lieu_naissance');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $lieu = field_value($_POST, 'lieu_naissance');
        if ($lieu !== '') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_lieux_naissance (lieu_naissance) VALUES (:lieu)');
            $stmt->execute(['lieu' => $lieu]);
            set_flash('success', 'Lieu de naissance ajoute.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('lieux-naissance');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $lieu = field_value($_POST, 'lieu_naissance');
        if ($lieu !== '') {
            $stmt = $pdo->prepare('DELETE FROM ref_lieux_naissance WHERE lieu_naissance = :lieu');
            $stmt->execute(['lieu' => $lieu]);
            set_flash('success', 'Lieu de naissance supprime.');
        }
        redirect_to('lieux-naissance');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Gestion des lieux de naissance</h2>
            <p class="help-text">Ajoutez ou supprimez des lieux de naissance.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(app_url('creation')) ?>">Retour</a>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">
            <span>Nouveau lieu de naissance</span>
            <div style="display:flex;gap:8px">
                <input name="lieu_naissance" required>
                <button type="submit" class="btn">Ajouter</button>
            </div>
        </label>
    </form>

    <?php if (count($lieux) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Lieu de naissance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lieux as $lieu): ?>
                    <tr>
                        <td><?= e($lieu) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="lieu_naissance" value="<?= e($lieu) ?>">
                                <button type="submit" class="btn btn-secondary" data-confirm="Supprimer <?= e($lieu) ?> ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucun lieu de naissance.</p>
    <?php endif; ?>
</section>
