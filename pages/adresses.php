<?php

declare(strict_types=1);

$adresses = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $adresse = field_value($_POST, 'ste_adresse');
        if ($adresse !== '') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_ste_adresses (ste_adresse) VALUES (:adresse)');
            $stmt->execute(['adresse' => $adresse]);
            set_flash('success', 'Adresse ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('adresses');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $adresse = field_value($_POST, 'ste_adresse');
        if ($adresse !== '') {
            $stmt = $pdo->prepare('DELETE FROM ref_ste_adresses WHERE ste_adresse = :adresse');
            $stmt->execute(['adresse' => $adresse]);
            set_flash('success', 'Adresse supprimee.');
        }
        redirect_to('adresses');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Gestion des adresses de reference</h2>
            <p class="help-text">Ajoutez ou supprimez des adresses de reference.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(app_url('creation')) ?>">Retour</a>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">
            <span>Nouvelle adresse</span>
            <div style="display:flex;gap:8px">
                <input name="ste_adresse" required>
                <button type="submit" class="btn">Ajouter</button>
            </div>
        </label>
    </form>

    <?php if (count($adresses) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Adresse</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($adresses as $adresse): ?>
                    <tr>
                        <td><?= e($adresse) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ste_adresse" value="<?= e($adresse) ?>">
                                <button type="submit" class="btn btn-secondary" data-confirm="Supprimer <?= e($adresse) ?> ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucune adresse.</p>
    <?php endif; ?>
</section>
