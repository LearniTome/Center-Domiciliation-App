<?php

declare(strict_types=1);

$villes = fetch_reference_options($pdo ?? null, 'ref_villes', 'ville');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $ville = field_value($_POST, 'ville');
        if ($ville !== '') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_villes (ville) VALUES (:ville)');
            $stmt->execute(['ville' => $ville]);
            set_flash('success', 'Ville ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('villes');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $ville = field_value($_POST, 'ville');
        if ($ville !== '') {
            $stmt = $pdo->prepare('DELETE FROM ref_villes WHERE ville = :ville');
            $stmt->execute(['ville' => $ville]);
            set_flash('success', 'Ville supprimee.');
        }
        redirect_to('villes');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Gestion des villes</h2>
            <p class="help-text">Ajoutez ou supprimez des villes.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(app_url('creation')) ?>">Retour</a>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">
            <span>Nouvelle ville</span>
            <div style="display:flex;gap:8px">
                <input name="ville" required>
                <button type="submit" class="btn-icon" title="Ajouter"><span class="mdi mdi-plus"></span></button>
            </div>
        </label>
    </form>

    <?php if (count($villes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Ville</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($villes as $ville): ?>
                    <tr>
                        <td><?= e($ville) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ville" value="<?= e($ville) ?>">
                                <button type="submit" class="btn btn-secondary" data-confirm="Supprimer <?= e($ville) ?> ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucune ville.</p>
    <?php endif; ?>
</section>
